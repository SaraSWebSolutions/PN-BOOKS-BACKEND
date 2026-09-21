<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Mail\SupportTicketReplyMail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class SupportTicketController extends Controller
{
    public function index()
    {
        $tickets = SupportTicket::latest()->get();

        $stats = [
            'total'       => SupportTicket::count(),
            'open'        => SupportTicket::where('status', 'open')->count(),
            'in_progress' => SupportTicket::where('status', 'in_progress')->count(),
            'resolved'    => SupportTicket::where('status', 'resolved')->count(),
        ];

        return view('support-tickets.index', compact('tickets', 'stats'));
    }

    public function reply(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        $request->validate([
            'admin_reply' => 'required|string|max:2000',
            'status'      => 'required|in:open,in_progress,resolved',
        ]);

        $previousStatus = $supportTicket->status;

        $supportTicket->update([
            'admin_reply' => $request->admin_reply,
            'status'      => $request->status,
            'replied_by'  => Auth::id(),
            'replied_at'  => now(),
        ]);

        $this->notifyCustomerIfNeeded($supportTicket, $previousStatus);

        return response()->json([
            'status'  => 'success',
            'message' => 'Reply saved! ✅',
            'ticket'  => $supportTicket->fresh(),
        ]);
    }

    public function updateStatus(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        $request->validate(['status' => 'required|in:open,in_progress,resolved']);

        $previousStatus = $supportTicket->status;

        $supportTicket->update(['status' => $request->status]);

        $this->notifyCustomerIfNeeded($supportTicket, $previousStatus);

        return response()->json([
            'status'  => 'success',
            'message' => 'Ticket status updated.',
        ]);
    }

    public function destroy(SupportTicket $supportTicket): JsonResponse
    {
        $supportTicket->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Ticket deleted.',
        ]);
    }

    /**
     * Sends the customer an email only when the status actually changed
     * to 'in_progress' or 'resolved' (not on every save, and not on
     * a no-op update where status stays the same).
     */
    private function notifyCustomerIfNeeded(SupportTicket $ticket, string $previousStatus): void
    {
        $notifiableStatuses = ['in_progress', 'resolved'];

        $statusChanged = $previousStatus !== $ticket->status;

        if ($statusChanged && in_array($ticket->status, $notifiableStatuses, true)) {
            Mail::to($ticket->email)->send(new SupportTicketReplyMail($ticket->fresh()));
        }
    }
}