<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Application update') }}</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #64748b; color: #fff; padding: 16px; border-radius: 8px 8px 0 0; }
        .content { background: #f8fafc; padding: 24px; border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 8px 8px; }
        .detail { margin: 8px 0; }
        .note { margin-top: 16px; padding: 12px; background: #fff; border-left: 4px solid #64748b; white-space: pre-wrap; }
        .footer { margin-top: 24px; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin:0; font-size: 1.25rem;">{{ config('bhcas.name') }}</h1>
        <p style="margin: 4px 0 0 0; opacity: 0.9;">{{ __('Barangay application') }}</p>
    </div>
    <div class="content">
        <p>{{ __('Hello,') }}</p>
        <p>{{ __('Thank you for applying. After review, your application was not approved at this time.') }}</p>
        <div class="detail"><strong>{{ __('Organization / barangay name') }}:</strong> {{ $organizationName }}</div>
        <div class="detail"><strong>{{ __('Barangay') }}:</strong> {{ $barangay }}</div>
        @if(filled($rejectionReason))
            <p style="margin-top: 16px; margin-bottom: 4px;"><strong>{{ __('Additional information') }}</strong></p>
            <div class="note">{{ $rejectionReason }}</div>
        @endif
        <p class="footer">{{ __('If you have questions, reply to this email or contact support through the main website.') }}</p>
        <p class="footer">{{ __('This is an automated message from :app.', ['app' => config('bhcas.name')]) }}</p>
    </div>
</body>
</html>
