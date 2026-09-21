<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportSubject;
use App\Models\SupportTicket;
use App\Mail\SupportTicketMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SupportTicketApiController extends Controller
{
    // POST /api/support/tickets (public — logged in or guest)
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:150',
            'email'      => 'required|email|max:150',
            'subject_id' => 'required|integer|exists:support_subjects,id',
            'message'    => 'required|string|max:2000',
        ]);

        $subject = SupportSubject::findOrFail($data['subject_id']);

        $ticket = SupportTicket::create([
            'user_id'    => $request->user()?->id, // null if guest
            'name'       => $data['name'],
            'email'      => $data['email'],
            'subject_id' => $subject->id,
            'subject'    => $subject->name, // stored as text snapshot too
            'message'    => $data['message'],
        ]);

        // send notification to the subject's routed inbox, falling back to default MAIL_FROM
        $notifyEmail = $subject->notify_email ?: config('mail.from.address');
        Mail::to($notifyEmail)->send(new SupportTicketMail($ticket));

        return response()->json([
            'success' => true,
            'message' => 'Your message has been sent. Our team will get back to you soon.',
            'data'    => $ticket->load('subjectMaster'),
        ], 201);
    }
}