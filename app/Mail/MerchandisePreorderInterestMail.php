<?php

namespace App\Mail;

use App\Models\MerchandiseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MerchandisePreorderInterestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public MerchandiseOrder $order)
    {
    }

    public function build(): self
    {
        $itemName = $this->order->items->first()?->item_name ?? 'Unknown Item';
        $senderLabel = $this->order->customer_name ?: $this->order->customer_email;

        return $this
            ->subject("Pre-order Interest: {$itemName} ({$senderLabel})")
            ->replyTo($this->order->customer_email, $this->order->customer_name)
            ->view('emails.merchandise-preorder-interest')
            ->with(['order' => $this->order]);
    }
}
