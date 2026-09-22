<?php

namespace App\Mail;

use App\Models\ContentQuestion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewContentQuestion extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContentQuestion $question)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [
                new Address(
                    $this->question->email,
                    $this->question->name,
                ),
            ],
            subject: 'Nueva pregunta para Dorelog',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.content-questions.new',
        );
    }
}
