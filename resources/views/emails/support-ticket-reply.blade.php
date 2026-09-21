<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Support Ticket Update</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f4f6f9; padding:30px; margin:0;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:auto;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
        <tr>
            <td style="background:#1e293b;padding:20px 30px;">
                <h2 style="color:#ffffff;margin:0;font-size:18px;">Pustaka Nasional — Support Ticket Update</h2>
            </td>
        </tr>
        <tr>
            <td style="padding:30px;">
                <p style="margin:0 0 16px;color:#334155;">Hi {{ $ticket->name }},</p>

                <p style="margin:0 0 16px;color:#334155;">
                    There's an update on your support ticket
                    <strong>#{{ $ticket->id }} — {{ $ticket->subject }}</strong>.
                </p>

                <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse; margin-bottom:20px;">
                    <tr>
                        <td style="width:140px;color:#64748b;font-weight:bold;">Status</td>
                        <td>
                            <span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:13px;font-weight:bold;
                                background:{{ $ticket->status === 'resolved' ? '#d1fae5' : '#fef3c7' }};
                                color:{{ $ticket->status === 'resolved' ? '#065f46' : '#92400e' }};">
                                {{ $ticket->status === 'resolved' ? 'Resolved' : 'In Progress' }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td style="color:#64748b;font-weight:bold;vertical-align:top;">Your Message</td>
                        <td style="color:#334155;white-space:pre-line;">{{ $ticket->message }}</td>
                    </tr>
                    @if($ticket->admin_reply)
                    <tr>
                        <td style="color:#64748b;font-weight:bold;vertical-align:top;">Our Reply</td>
                        <td style="color:#0f172a;white-space:pre-line;">{{ $ticket->admin_reply }}</td>
                    </tr>
                    @endif
                </table>

                @if($ticket->status === 'resolved')
                <p style="margin:0 0 16px;color:#334155;">
                    We've marked this ticket as resolved. If you still need help, feel free to reply or raise a new ticket.
                </p>
                @else
                <p style="margin:0 0 16px;color:#334155;">
                    Our team is actively working on this. We'll follow up with the resolution soon.
                </p>
                @endif

                <p style="margin:24px 0 0;color:#64748b;font-size:13px;">
                    — Pustaka Nasional Support Team
                </p>
            </td>
        </tr>
    </table>
</body>
</html>