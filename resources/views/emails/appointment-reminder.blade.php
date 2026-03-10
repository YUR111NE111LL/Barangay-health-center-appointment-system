<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Reminder</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #198754; color: #fff; padding: 16px; border-radius: 8px 8px 0 0; }
        .content { background: #f8f9fa; padding: 24px; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 8px 8px; }
        .detail { margin: 8px 0; }
        .footer { margin-top: 24px; font-size: 12px; color: #6c757d; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin:0; font-size: 1.25rem;">{{ config('bhcas.name') }}</h1>
        <p style="margin: 4px 0 0 0; opacity: 0.9;">Appointment Reminder</p>
    </div>
    <div class="content">
        <p>Hello {{ $appointment->resident->name }},</p>
        <p>This is a reminder that you have an approved appointment <strong>tomorrow</strong>.</p>
        <div class="detail"><strong>Service:</strong> {{ $appointment->service->name }}</div>
        <div class="detail"><strong>Date:</strong> {{ $appointment->scheduled_date->format('l, F j, Y') }}</div>
        <div class="detail"><strong>Time:</strong> {{ \Carbon\Carbon::parse($appointment->scheduled_time)->format('g:i A') }}</div>
        <p>Please arrive a few minutes early. If you need to cancel or reschedule, contact your health center.</p>
        <p class="footer">This is an automated reminder from {{ config('bhcas.name') }}.</p>
    </div>
</body>
</html>
