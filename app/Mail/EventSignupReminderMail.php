<?php

namespace App\Mail;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EventSignupReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $eventTitle,
        public string $eventDescription,
        public string $reminderType, // week|day|hour
        public CarbonImmutable $occurrenceStartUtc,
        public string $timezone,
        public ?string $location,
        public string $manageUrl,
        public string $unsubscribeUrl,
    ) {}

    public function build(): self
    {
        $when = match ($this->reminderType) {
            'week' => 'in 1 week',
            'day'  => 'in 1 day',
            'hour' => 'in 1 hour',
            default => 'soon',
        };

        return $this->subject("Reminder: {$this->eventTitle} ({$when})")
            ->markdown('emails.event-signup-reminder');
    }
}
