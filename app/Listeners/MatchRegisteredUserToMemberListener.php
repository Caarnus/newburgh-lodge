<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\People\RegistrationMemberService;
use Illuminate\Auth\Events\Registered;

class MatchRegisteredUserToMemberListener
{
    public function __construct(
        protected RegistrationMemberService $matcher,
    ) {
    }

    /**
     * @throws \Throwable
     */
    public function handle(Registered $event): void
    {
        if (!$event->user instanceof User) {
            return;
        }

        $this->matcher->handleRegisteredUser($event->user);
    }
}
