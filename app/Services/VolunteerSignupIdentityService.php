<?php

namespace App\Services;

use App\Enums\UserPersonLinkAction;
use App\Models\Person;
use App\Models\User;
use App\Services\People\RegistrationMemberService;
use App\Services\People\UserPersonLinkService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class VolunteerSignupIdentityService
{
    public function __construct(
        protected RegistrationMemberService $registrationMemberService,
        protected UserPersonLinkService $userPersonLinkService,
    ) {
    }

    /**
     * @return array{0: User, 1: Person|null}
     * @throws Throwable
     */
    public function resolveUserAndPerson(?string $name, string $email): array
    {
        $normalizedEmail = Str::lower(trim($email));
        $displayName = trim((string) $name) ?: 'Volunteer';

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->first();

        if (!$user) {
            $user = User::create([
                'name' => $displayName,
                'email' => $normalizedEmail,
                'password' => Hash::make(Str::password(32)),
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            try {
                event(new Registered($user));
            } catch (Throwable) {
                // Keep volunteer flow working even if role seeders/listeners are misconfigured.
            }

            if (!$user->person_id) {
                try {
                    $this->registrationMemberService->handleRegisteredUser($user);
                } catch (Throwable) {
                    // Keep volunteer flow working even if matching/roles fail.
                }
            }
        } else {
            if (empty($user->name) && $displayName !== '') {
                $user->name = $displayName;
                $user->save();
            }

            if (!$user->person_id) {
                try {
                    $this->registrationMemberService->handleRegisteredUser($user);
                } catch (Throwable) {
                    // Keep volunteer flow working even if matching/roles fail.
                }
            }
        }

        $user = $user->fresh() ?? $user;

        if (!$user->person_id) {
            $memberCandidate = Person::query()
                ->with('memberProfile')
                ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
                ->whereHas('memberProfile')
                ->first();

            if ($memberCandidate) {
                try {
                    $this->userPersonLinkService->link(
                        user: $user,
                        person: $memberCandidate,
                        action: UserPersonLinkAction::AutoMatched,
                        matchStrategy: 'volunteer_signup_email',
                        notes: 'Auto-linked during volunteer signup by roster email match.',
                    );
                } catch (Throwable) {
                    // Ignore if person is already linked elsewhere or role assignment fails.
                }
            }
        }

        $user = $user->fresh(['person.memberProfile']) ?? $user;
        $person = $user->person;

        return [$user, $person];
    }
}
