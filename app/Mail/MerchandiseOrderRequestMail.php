<?php

namespace App\Mail;

use App\Models\MerchandiseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MerchandiseOrderRequestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public MerchandiseOrder $order)
    {
    }

    public function build(): self
    {
        $senderLabel = $this->order->customer_name ?: $this->order->customer_email;

        return $this
            ->subject('Merchandise Order Request: ' . $senderLabel)
            ->replyTo($this->order->customer_email, $this->order->customer_name)
            ->view('emails.merchandise-order-request')
            ->with(['order' => $this->order]);
    }
}
