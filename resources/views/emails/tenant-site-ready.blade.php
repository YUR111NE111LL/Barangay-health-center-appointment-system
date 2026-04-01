<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Site ready') }}</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #0d9488; color: #fff; padding: 16px; border-radius: 8px 8px 0 0; }
        .content { background: #f8fafc; padding: 24px; border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 8px 8px; }
        .detail { margin: 8px 0; }
        .btn { display: inline-block; margin: 8px 8px 8px 0; padding: 10px 16px; background: #0d9488; color: #fff !important; text-decoration: none; border-radius: 8px; font-weight: 600; }
        .footer { margin-top: 24px; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin:0; font-size: 1.25rem;">{{ config('bhcas.name') }}</h1>
        <p style="margin: 4px 0 0 0; opacity: 0.9;">{{ __('Your barangay health center site') }}</p>
    </div>
    <div class="content">
        <p>{{ __('Hello, :name,', ['name' => $organizationName]) }}</p>
        @php($isDashboardLink = is_string($staffLoginUrl) && str_contains($staffLoginUrl, '/auth/email/tenant-session'))
        <p>
            {{ __('Your barangay site is ready.') }}
            @if($isDashboardLink)
                {{ __('Your Barangay Admin account was created automatically using the email you entered in the application.') }}
            @else
                {{ __('You can sign in using the links below.') }}
            @endif
        </p>
        <div class="detail"><strong>{{ __('Barangay') }}:</strong> {{ $barangayName ?: $organizationName }}</div>
        <div class="detail"><strong>{{ __('Your site address (domain)') }}:</strong> {{ $domain }}</div>
        @if($plan)
            <div class="detail"><strong>{{ __('Plan') }}:</strong> {{ $plan->name }}</div>
            @if($plan->price !== null)
                <div class="detail"><strong>{{ __('Plan price') }}:</strong> {{ $plan->formattedPrice() }} {{ __('/ month') }}</div>
            @endif
        @endif
        <p style="margin-top: 20px;">{{ __('Use the links below to access your barangay site:') }}</p>
        <p>
            <a class="btn" href="{{ $staffLoginUrl }}">{{ $isDashboardLink ? __('Open dashboard') : __('Staff / Nurse login') }}</a>
            <a class="btn" href="{{ $residentLoginUrl }}">{{ $isDashboardLink ? __('Central login') : __('Resident login') }}</a>
        </p>
        <p class="footer">{{ __('If the buttons do not work, copy these links into your browser:') }}</p>
        <p class="footer" style="word-break: break-all;">{{ $staffLoginUrl }}<br>{{ $residentLoginUrl }}</p>
        <p class="footer">{{ __('This is an automated message from :app.', ['app' => config('bhcas.name')]) }}</p>
    </div>
</body>
</html>
