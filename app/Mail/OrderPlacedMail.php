<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;

class OrderPlacedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Order $order)
    {
        //
    }

   
    public function envelope(): Envelope{
        return new Envelope(
            subject: 'Siparişiniz Alındı.'
        );
    }

    public function content():Content{
        return new Content(
            view:'emails.order',
            with:[
                'order'=>$this->order
            ]
            );
    }

 
}
