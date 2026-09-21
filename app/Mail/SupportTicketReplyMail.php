<?php

namespace App\Mail;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupportTicketReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public SupportTicket $ticket;

    public function __construct(SupportTicket $ticket)
    {
        $this->ticket = $ticket;
    }

    public function build()
    {
        $statusLabel = $this->ticket->status === 'resolved'
            ? 'Resolved'
            : 'In Progress';

        return $this->subject("Update on your ticket: {$this->ticket->subject} [{$statusLabel}]")
            ->view('emails.support-ticket-reply');
    }
}