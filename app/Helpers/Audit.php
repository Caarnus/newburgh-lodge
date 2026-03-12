<?php

namespace App\Helpers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Audit
{
    public static function log(
        Request $req,
        string $action,
        ?Model $subject = null,
        array $changes = [],       // ['before' => [...], 'after' => [...]]
        array $meta = [],          // arbitrary details
        bool $succeeded = true,
        ?string $errorMessage = null,
        ?Model $secondary = null
    ): void {
        $middleware = $req->route()?->getAction('middleware');
        $guard = match (true) {
            is_array($middleware) => (string) ($middleware[0] ?? 'web'),
            is_string($middleware) => $middleware,
            default => 'web',
        };

        self::logForActor(
            actorId: $req->user()?->id,
            action: $action,
            subject: $subject,
            changes: $changes,
            meta: $meta,
            succeeded: $succeeded,
            errorMessage: $errorMessage,
            secondary: $secondary,
            actorType: $req->user() ? 'user' : 'system',
            actorGuard: $guard,
            ip: $req->ip(),
            userAgent: $req->userAgent(),
            requestId: $req->headers->get('X-Request-Id') ?? $req->attributes->get('request_id'),
        );
    }

    public static function logForActor(
        ?int $actorId,
        string $action,
        ?Model $subject = null,
        array $changes = [],
        array $meta = [],
        bool $succeeded = true,
        ?string $errorMessage = null,
        ?Model $secondary = null,
        ?string $actorType = null,
        ?string $actorGuard = 'web',
        ?string $ip = null,
        ?string $userAgent = null,
        ?string $requestId = null,
    ): void {
        AuditLog::create([
            'actor_id' => $actorId,
            'actor_type' => $actorType ?? ($actorId ? 'user' : 'system'),
            'actor_guard' => $actorGuard ?? 'web',

            'action' => $action,

            'subject_type' => $subject ? $subject::class : null,
            'subject_id'   => $subject?->getKey(),

            'secondary_subject_type' => $secondary ? $secondary::class : null,
            'secondary_subject_id'   => $secondary?->getKey(),

            'before' => $changes['before'] ?? null,
            'after'  => $changes['after'] ?? null,
            'meta'   => $meta ?: null,

            'ip' => $ip,
            'user_agent' => substr((string) $userAgent, 0, 255),
            'request_id' => $requestId,

            'succeeded'     => $succeeded,
            'error_message' => $errorMessage,
        ]);
    }
}
