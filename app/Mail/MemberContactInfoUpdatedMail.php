<?php

namespace App\Mail;

use App\Models\Person;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class MemberContactInfoUpdatedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Person $person,
        public User $actor,
        public array $changes,
        public string $profileUrl,
    ) {
    }

    public function build(): self
    {
        return $this->subject('Member contact info updated: ' . ($this->person->display_name ?: 'Person #' . $this->person->id))
            ->view('emails.member-contact-info-updated', [
                'formattedChanges' => $this->formattedChanges(),
            ]);
    }

    protected function formattedChanges(): array
    {
        return collect($this->changes)
            ->map(function (array $change) {
                $field = (string) ($change['field'] ?? '');

                return [
                    'label' => Str::of($field)->replace('_', ' ')->title()->toString(),
                    'before' => $change['before'] ?? null,
                    'after' => $change['after'] ?? null,
                ];
            })
            ->values()
            ->all();
    }
}

