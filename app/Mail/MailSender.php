<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MailSender extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $body;
    public $name;

    /**
     * Create a new message instance.
     *
     * @param string $subject
     * @param string $body
     * @return void
     */
    public function __construct($subject, $body, $name)
    {
        $this->subject = $subject;
        $this->body = $body;
        $this->name = $name;
      
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('email.template')
                    ->subject($this->subject)
                    ->with([
                        'body' => $this->body,
                        'name' => $this->name
                    ]);
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
