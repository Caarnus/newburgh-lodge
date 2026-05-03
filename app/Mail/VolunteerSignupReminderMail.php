<?php

namespace App\Mail;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VolunteerSignupReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $eventTitle,
        public ?string $eventDescription,
        public string $reminderType, // week|day
        public CarbonImmutable $occurrenceStartUtc,
        public string $timezone,
        public ?string $location,
        public array $assignments,
        public ?string $signupUrl,
    ) {
    }

    public function build(): self
    {
        $when = match ($this->reminderType) {
            'week' => 'in 1 week',
            'day' => 'in 1 day',
            default => 'soon',
        };

        return $this->subject("Volunteer Reminder: {$this->eventTitle} ({$when})")
            ->markdown('emails.volunteer-signup-reminder');
    }
}
