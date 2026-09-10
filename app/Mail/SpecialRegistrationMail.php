<?php

namespace App\Mail;

use App\Models\SpecialRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SpecialRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SpecialRegistration $registration) {}

    public function build()
    {
        $title = $this->registration->form === 'open-air-2026'
            ? 'Open Air Degree registration'
            : 'Golf Scramble registration';

        return $this->subject($title)->replyTo($this->registration->email, $this->registration->name)
            ->view('emails.special-registration');
    }
}
