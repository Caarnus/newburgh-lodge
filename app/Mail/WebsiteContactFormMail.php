<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WebsiteContactFormMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $data) {}

    public function build()
    {
        $subject = 'Website Contact Form: ' . $this->data['subject'];

        return $this->subject($subject)
            // Best practice: keep FROM as your site’s configured from-address, but set Reply-To to the sender
            ->replyTo($this->data['email'], $this->data['name'] ?? null)
            ->view('emails.website-contact-form')
            ->with(['data' => $this->data]);
    }
}
