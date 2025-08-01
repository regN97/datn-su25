<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReceiptEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    /**
     * Create a new message instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function build()
    {
        return $this->subject('Hóa đơn thanh toán từ G7 Mart')
            ->view('emails.receipt')
            ->with([
                'orderId' => $this->data['orderId'],
                'cart' => $this->data['cart'],
                'paymentMethod' => $this->data['paymentMethod'],
                'amountReceived' => $this->data['amountReceived'],
                'customer' => $this->data['customer'],
                'total' => $this->data['total'],
                'transactionTime' => $this->data['transactionTime'],
                'storeInfo' => $this->data['storeInfo'],
            ]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Receipt Email',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'view.name',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
