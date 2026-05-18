<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerWelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public Customer $customer;

    public string $password;

    public function __construct(Customer $customer, string $password)
    {
        $this->customer = $customer;
        $this->password = $password;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to ' . config('app.name') . ' - Your Account Details',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.customer-welcome-email',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}