<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Support Ticket</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f4f6f9; padding:30px; margin:0;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:auto;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
        <tr>
            <td style="background:#1e293b;padding:20px 30px;">
                <h2 style="color:#ffffff;margin:0;font-size:18px;">Pustaka Nasional — New Support Ticket</h2>
            </td>
        </tr>
        <tr>
            <td style="padding:30px;">
                <p style="margin:0 0 16px;color:#334155;">A new support ticket has been submitted.</p>

                <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;">
                    <tr>
                        <td style="width:140px;color:#64748b;font-weight:bold;">Ticket #</td>
                        <td style="color:#0f172a;">{{ $ticket->id }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b;font-weight:bold;">Name</td>
                        <td style="color:#0f172a;">{{ $ticket->name }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b;font-weight:bold;">Email</td>
                        <td style="color:#0f172a;">{{ $ticket->email }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b;font-weight:bold;">Subject</td>
                        <td style="color:#0f172a;">{{ $ticket->subject }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b;font-weight:bold;vertical-align:top;">Message</td>
                        <td style="color:#0f172a;white-space:pre-line;">{{ $ticket->message }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b;font-weight:bold;">Submitted At</td>
                        <td style="color:#0f172a;">{{ $ticket->created_at->format('d M Y, h:i A') }}</td>
                    </tr>
                </table>

                <p style="margin:24px 0 0;color:#64748b;font-size:13px;">
                    Reply to this ticket from the admin panel to respond to the customer.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>