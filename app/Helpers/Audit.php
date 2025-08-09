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
        AuditLog::create([
            'actor_id'   => $req->user()?->id,
            'actor_type' => $req->user() ? 'user' : 'system',
            'actor_guard'=> $req->route()?->getAction('middleware')[0] ?? 'web',

            'action'     => $action,

            'subject_type' => $subject ? $subject::class : null,
            'subject_id'   => $subject?->getKey(),

            'secondary_subject_type' => $secondary ? $secondary::class : null,
            'secondary_subject_id'   => $secondary?->getKey(),

            'before' => $changes['before'] ?? null,
            'after'  => $changes['after'] ?? null,
            'meta'   => $meta ?: null,

            'ip'         => $req->ip(),
            'user_agent' => substr((string)$req->userAgent(), 0, 255),
            'request_id' => $req->headers->get('X-Request-Id') ?? $req->attributes->get('request_id'),

            'succeeded'     => $succeeded,
            'error_message' => $errorMessage,
        ]);
    }
}
