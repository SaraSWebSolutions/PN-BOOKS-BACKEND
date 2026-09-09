<?php
namespace App\Console\Commands;

use App\Models\Lead;
use App\Notifications\LeadFollowupWhatsapp;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendLeadFollowupReminders extends Command
{
    protected $signature = 'leads:send-followup-reminders {--days=3 : Days before follow-up date to send reminder}';
    protected $description = 'Send WhatsApp reminders for leads whose follow-up date is N days away';

    public function handle(LeadFollowupWhatsapp $whatsapp)
    {
        $daysAhead = (int) $this->option('days');
        $targetDate = Carbon::today()->addDays($daysAhead)->toDateString();

        $leads = Lead::with(['assignedTo', 'salesAssignedTo'])
            ->whereDate('follow_up_date', $targetDate)
            ->whereNotIn('status', ['booked', 'lost'])
            ->get();

        if ($leads->isEmpty()) {
            $this->info("No leads with follow-up date on {$targetDate}.");
            return self::SUCCESS;
        }

        $sent = 0;
        $skipped = 0;

        foreach ($leads as $lead) {
            // Prefer sales_assigned_to, fallback to assigned_to (telecaller)
            $recipient = $lead->salesAssignedTo ?? $lead->assignedTo;

            if (!$recipient || empty($recipient->phone)) {
                $this->warn("Skipped lead #{$lead->id} ({$lead->name}) — no assigned user or phone number.");
                $skipped++;
                continue;
            }

            $mobile = preg_replace('/[^0-9]/', '', $recipient->phone);
            // Prepend country code if missing (adjust default as needed)
            if (strlen($mobile) === 10) {
                $mobile = '91' . $mobile;
            }

            $ok = $whatsapp->send(
                $mobile,
                $recipient->name,
                $lead->name,
                $lead->phone,
                Carbon::parse($lead->follow_up_date)->format('d M Y')
            );

            if ($ok) {
                $sent++;
                $this->info("Reminder sent for lead #{$lead->id} ({$lead->name}) to {$recipient->name} ({$mobile}).");
            } else {
                $skipped++;
                $this->error("Failed to send reminder for lead #{$lead->id} ({$lead->name}).");
            }
        }

        $this->info("Done. Sent: {$sent}, Skipped/Failed: {$skipped}.");
        return self::SUCCESS;
    }
}