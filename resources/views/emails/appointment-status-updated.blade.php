<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Status Update</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #0d6efd; color: #fff; padding: 16px; border-radius: 8px 8px 0 0; }
        .content { background: #f8f9fa; padding: 24px; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 8px 8px; }
        .detail { margin: 8px 0; }
        .status { display: inline-block; padding: 4px 12px; border-radius: 4px; font-weight: bold; }
        .status.pending { background: #ffc107; color: #000; }
        .status.approved { background: #198754; color: #fff; }
        .status.completed { background: #6c757d; color: #fff; }
        .status.cancelled, .status.no_show { background: #dc3545; color: #fff; }
        .footer { margin-top: 24px; font-size: 12px; color: #6c757d; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin:0; font-size: 1.25rem;">{{ config('bhcas.name') }}</h1>
        <p style="margin: 4px 0 0 0; opacity: 0.9;">Appointment Status Update</p>
    </div>
    <div class="content">
        <p>Hello {{ $appointment->resident->name }},</p>
        @if($appointment->status === 'approved')
        <p>Your appointment has been <strong>approved</strong>. You may come on the scheduled date and time.</p>
        @elseif($appointment->status === 'cancelled' || $appointment->status === 'no_show')
        <p>Your appointment has been <strong>cancelled</strong> or was not completed. You may book again if needed.</p>
        @else
        <p>Your appointment status has been updated.</p>
        @endif
        <div class="detail"><strong>Service:</strong> {{ $appointment->service->name }}</div>
        <div class="detail"><strong>Date:</strong> {{ $appointment->scheduled_date->format('l, F j, Y') }}</div>
        <div class="detail"><strong>Time:</strong> {{ \Carbon\Carbon::parse($appointment->scheduled_time)->format('g:i A') }}</div>
        <div class="detail"><strong>Status:</strong> <span class="status {{ $appointment->status }}">{{ ucfirst($appointment->status) }}</span></div>
        <p class="footer">This is an automated message from {{ config('bhcas.name') }}.</p>
    </div>
</body>
</html>
