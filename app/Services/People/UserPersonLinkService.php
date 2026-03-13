<?php

namespace App\Services\People;

use App\Enums\UserPersonLinkAction;
use App\Helpers\Audit;
use App\Models\Person;
use App\Models\User;
use App\Models\UserPersonLinkAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class UserPersonLinkService
{
    protected string $memberRole = 'member';

    /**
     * @var array<int, string>
     */
    protected array $disallowedMemberRoleStatuses = [
        'expelled',
        'suspended',
        'demitted',
    ];

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
            $memberRoleAssigned = false;

            if ((int) $user->person_id === (int) $person->id) {
                if ($assignMemberRole && $this->qualifiesForMemberRole($person)) {
                    $memberRoleAssigned = $this->assignMemberRole($user);

                    if ($memberRoleAssigned) {
                        $this->auditMemberRoleAssigned($creatorId, $user, $person, $action->value, $matchStrategy);
                    }
                }
                return $user->fresh() ?? $user;
            }

            $this->ensurePersonIsAvailableForUser($user, $person);

            $previousPersonId = $user->person_id;

            $user->forceFill([
                'person_id' => $person->id,
            ])->save();

            if ($assignMemberRole && $this->qualifiesForMemberRole($person)) {
                $memberRoleAssigned = $this->assignMemberRole($user);
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

            Audit::logForActor(
                actorId: $creatorId,
                action: 'user_person_link.'.$action->value,
                subject: $user,
                changes: [
                    'before' => [
                        'person_id' => $previousPersonId,
                    ],
                    'after' => [
                        'person_id' => $person->id,
                    ],
                ],
                meta: [
                    'match_strategy' => $matchStrategy,
                    'notes' => $notes,
                    'member_role_assigned' => $memberRoleAssigned,
                ],
                secondary: $person,
            );

            if ($memberRoleAssigned) {
                $this->auditMemberRoleAssigned($creatorId, $user, $person, $action->value, $matchStrategy);
            }

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

            Audit::logForActor(
                actorId: $creatorId,
                action: 'user_person_link.manual_unlinked',
                subject: $user,
                changes: [
                    'before' => [
                        'person_id' => $previousPersonId,
                    ],
                    'after' => [
                        'person_id' => null,
                    ],
                ],
                meta: [
                    'notes' => $notes,
                    'remove_member_role' => $removeMemberRole,
                ],
            );

            return $user->fresh() ?? $user;
        });
    }

    public function qualifiesForMemberRole(Person $person): bool
    {
        $person->loadMissing('memberProfile');

        if ($person->memberProfile === null) {
            return false;
        }

        $status = Str::lower(trim((string) ($person->memberProfile->status ?? '')));

        if ($status === '') {
            return true;
        }

        return ! in_array($status, $this->disallowedMemberRoleStatuses, true);
    }

    protected function assignMemberRole(User $user): bool
    {
        if (! method_exists($user, 'hasRole') || ! method_exists($user, 'assignRole')) {
            return false;
        }

        if (! $user->hasRole($this->memberRole)) {
            $user->assignRole($this->memberRole);

            return true;
        }

        return false;
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

    protected function auditMemberRoleAssigned(
        ?int $creatorId,
        User $user,
        Person $person,
        string $linkAction,
        ?string $matchStrategy,
    ): void {
        Audit::logForActor(
            actorId: $creatorId,
            action: 'user.role_auto_assigned',
            subject: $user,
            meta: [
                'role' => $this->memberRole,
                'reason' => 'linked_to_member_profile',
                'link_action' => $linkAction,
                'match_strategy' => $matchStrategy,
            ],
            secondary: $person,
        );
    }
}
