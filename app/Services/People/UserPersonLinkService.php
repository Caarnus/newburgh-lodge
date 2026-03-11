<?php

namespace App\Services\People;

use App\Enums\UserPersonLinkAction;
use App\Models\Person;
use App\Models\User;
use App\Models\UserPersonLinkAudit;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class UserPersonLinkService
{
    protected string $memberRole = 'member';

    /**
     * @throws Throwable
     */
    public function link(
        User $user,
        Person $person,
        ?int $creatorId = null,
        UserPersonLinkAction $action = UserPersonLinkAction::ManualLinked,
        ?string $matchStrategy = null,
        ?string $notes = null,
        bool $assignMemberRole = true,
    ): User {
        return DB::transaction(function () use (
            $user,
            $person,
            $creatorId,
            $action,
            $matchStrategy,
            $notes,
            $assignMemberRole,
        ) {
            $user = $user->fresh() ?? $user;
            $person->loadMissing('memberProfile');

            if ((int) $user->person_id === (int) $person->id) {
                if ($assignMemberRole && $this->qualifiesForMemberRole($person)) {
                    $this->assignMemberRole($user);
                }
                return $user->fresh() ?? $user;
            }

            $this->ensurePersonIsAvailableForUser($user, $person);

            $previousPersonId = $user->person_id;

            $user->forceFill([
                'person_id' => $person->id,
            ])->save();

            if ($assignMemberRole && $this->qualifiesForMemberRole($person)) {
                $this->assignMemberRole($user);
            }

            UserPersonLinkAudit::create([
                'user_id' => $user->id,
                'previous_person_id' => $previousPersonId,
                'current_person_id' => $person->id,
                'action' => $action,
                'match_strategy' => $matchStrategy,
                'notes' => $notes,
                'created_by' => $creatorId,
            ]);

            return $user->fresh() ?? $user;
        });
    }

    /**
     * @throws Throwable
     */
    public function unlink(
        User    $user,
        ?int    $creatorId = null,
        ?string $notes = null,
        bool    $removeMemberRole = false,
    ): User {
        return DB::transaction(function () use ($user, $creatorId, $notes, $removeMemberRole) {
            $user = $user->fresh() ?? $user;
            $previousPersonId = $user->person_id;

            if (! $previousPersonId) {
                return $user;
            }

            $user->forceFill([
                'person_id' => null,
            ])->save();

            if ($removeMemberRole && method_exists($user, 'hasRole') && method_exists($user, 'removeRole')) {
                if ($user->hasRole($this->memberRole)) {
                    $user->removeRole($this->memberRole);
                }
            }

            UserPersonLinkAudit::create([
                'user_id' => $user->id,
                'previous_person_id' => $previousPersonId,
                'current_person_id' => null,
                'action' => UserPersonLinkAction::ManualUnlinked,
                'match_strategy' => null,
                'notes' => $notes,
                'created_by' => $creatorId,
            ]);

            return $user->fresh() ?? $user;
        });
    }

    public function qualifiesForMemberRole(Person $person): bool
    {
        $person->loadMissing('memberProfile');

        return $person->memberProfile !== null;
    }

    protected function assignMemberRole(User $user): void
    {
        if (! method_exists($user, 'hasRole') || ! method_exists($user, 'assignRole')) {
            return;
        }

        if (! $user->hasRole($this->memberRole)) {
            $user->assignRole($this->memberRole);
        }
    }

    protected function ensurePersonIsAvailableForUser(User $user, Person $person): void
    {
        $linkedUser = User::query()
            ->where('person_id', $person->id)
            ->whereKeyNot($user->getKey())
            ->first();

        if ($linkedUser) {
            throw new RuntimeException("Person #{$person->id} is already linked to user #{$linkedUser->id}.");
        }
    }
}
