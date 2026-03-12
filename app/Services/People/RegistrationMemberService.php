<?php

namespace App\Services\People;

use App\Enums\UserPersonLinkAction;
use App\Models\Person;
use App\Models\User;
use App\Models\UserPersonLinkAudit;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class RegistrationMemberService
{
    public function __construct(
        protected UserPersonLinkService $linkService,
    ) {
    }

    public function findCandidateForUser(User $user): ?Person
    {
        $email = $this->normalizeEmail($user->email);

        if (!$email) {
            return null;
        }

        return Person::query()
            ->with('memberProfile')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereHas('memberProfile', function ($query) {
                $query->where('can_auto_match_registration', true);
            })
            ->first();
    }

    /**
     * @throws Throwable
     */
    public function handleRegisteredUser(User $user): ?Person
    {
        if ($user->person_id) {
            return Person::query()->with('memberProfile')->find($user->person_id);
        }

        $candidate = $this->findCandidateForUser($user);

        if (!$candidate) {
            return null;
        }

        try {
            $this->linkService->link(
                user: $user,
                person: $candidate,
                action: UserPersonLinkAction::AutoMatched,
                matchStrategy: 'email',
                notes: 'Automatically linked during registration from roster email match.',
            );
        } catch (RuntimeException) {
            return null;
        }

        return $candidate;
    }

    public function buildStatusPayload(User $user): array
    {
        $linkedPerson = $user->person_id
            ? Person::query()->with('memberProfile')->find($user->person_id)
            : null;

        $candidate = $linkedPerson ? null : $this->findCandidateForUser($user);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name ?? null,
                'email' => $user->email,
                'person_id' => $user->person_id,
            ],
            'linked_person' => $this->serializePerson($linkedPerson),
            'automatic_candidate' => $this->serializePerson($candidate),
            'recent_audit' => UserPersonLinkAudit::query()
                ->where('user_id', $user->id)
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (UserPersonLinkAudit $audit) => [
                    'id' => $audit->id,
                    'action' => $audit->action?->value,
                    'match_strategy' => $audit->match_strategy,
                    'previous_person_id' => $audit->previous_person_id,
                    'current_person_id' => $audit->current_person_id,
                    'notes' => $audit->notes,
                    'created_by' => $audit->created_by,
                    'created_at' => optional($audit->created_at)?->toDateTimeString(),
                ])
                ->values(),
        ];
    }

    protected function serializePerson(?Person $person): ?array
    {
        if (! $person) {
            return null;
        }

        $person->loadMissing('memberProfile');

        return [
            'id' => $person->id,
            'display_name' => $person->display_name,
            'email' => $person->email,
            'phone' => $person->phone,
            'is_deceased' => $person->is_deceased,
            'death_date' => optional($person->death_date)?->toDateString(),
            'member_profile' => $person->memberProfile ? [
                'id' => $person->memberProfile->id,
                'member_number' => $person->memberProfile->member_number,
                'status' => $person->memberProfile->status,
                'can_auto_match_registration' => $person->memberProfile->can_auto_match_registration,
            ] : null,
        ];
    }

    protected function normalizeEmail(?string $email): ?string
    {
        $email = trim((string) $email);

        return $email === '' ? null : Str::lower($email);
    }
}
