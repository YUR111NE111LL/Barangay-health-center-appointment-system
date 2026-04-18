<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family: ui-sans-serif, system-ui, sans-serif; line-height: 1.5; color: #1e293b;">
    <p>{{ __('Hello :name,', ['name' => $recipientName]) }}</p>
    <p>{{ __(':barangay posted a new health event.', ['barangay' => $barangayName]) }}</p>
    <p style="font-size: 1.125rem; font-weight: 600;">{{ $title }}</p>
    <p><strong>{{ __('When:') }}</strong> {{ $whenLine }}</p>
    @if($location)
        <p><strong>{{ __('Location:') }}</strong> {{ $location }}</p>
    @endif
    <p style="white-space: pre-wrap;">{{ $excerpt }}</p>
    <p>
        <a href="{{ $viewUrl }}" style="color: #0d9488;">{{ __('View event') }}</a>
    </p>
    <p style="font-size: 0.875rem; color: #64748b;">{{ __('You are receiving this because you have an account at this barangay health center.') }}</p>
</body>
</html>
