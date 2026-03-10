<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan Expiry Notification</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #dc2626; color: #fff; padding: 20px; border-radius: 8px 8px 0 0; }
        .header.warning { background: #f59e0b; }
        .header.info { background: #3b82f6; }
        .content { background: #f8f9fa; padding: 24px; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 8px 8px; }
        .detail { margin: 12px 0; padding: 12px; background: #fff; border-left: 4px solid #dc2626; border-radius: 4px; }
        .detail.warning { border-left-color: #f59e0b; }
        .detail.info { border-left-color: #3b82f6; }
        .footer { margin-top: 24px; font-size: 12px; color: #6c757d; text-align: center; }
        .button { display: inline-block; padding: 12px 24px; background: #0d9488; color: #fff; text-decoration: none; border-radius: 6px; margin-top: 16px; }
    </style>
</head>
<body>
    @if($type === 'expiring_soon')
        <div class="header warning">
            <h1 style="margin:0; font-size: 1.5rem;">⚠️ Plan Expiring Soon</h1>
            <p style="margin: 8px 0 0 0; opacity: 0.95;">Action Required</p>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>This is a notification that your <strong>{{ $tenant->plan->name ?? 'Plan' }}</strong> subscription for <strong>{{ $tenant->name }}</strong> is expiring soon.</p>
            <div class="detail warning">
                <strong>Expiry Date:</strong> {{ $tenant->subscription_ends_at->format('l, F j, Y') }}<br>
                <strong>Days Remaining:</strong> {{ now()->diffInDays($tenant->subscription_ends_at, false) }} day(s)
            </div>
            <p>To continue using the system without interruption, please renew your subscription before the expiry date.</p>
            <p>If payment is not received before the expiry date, your system will enter a <strong>3-day grace period</strong> before being deactivated.</p>
            <p>Please contact the Super Admin or system administrator to renew your subscription.</p>
        </div>
    @elseif($type === 'expired_grace_period')
        <div class="header">
            <h1 style="margin:0; font-size: 1.5rem;">🚨 Plan Expired – Grace Period Active</h1>
            <p style="margin: 8px 0 0 0; opacity: 0.95;">Urgent Action Required</p>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p><strong>Your subscription for {{ $tenant->name }} has expired.</strong></p>
            <div class="detail">
                <strong>Expiry Date:</strong> {{ $tenant->subscription_ends_at->format('l, F j, Y') }}<br>
                <strong>Grace Period Ends:</strong> {{ $tenant->grace_period_ends_at->format('l, F j, Y') }}<br>
                <strong>Days Remaining:</strong> {{ now()->diffInDays($tenant->grace_period_ends_at, false) }} day(s)
            </div>
            <p><strong>⚠️ IMPORTANT:</strong> Your system is now in a <strong>3-day grace period</strong>. You have until <strong>{{ $tenant->grace_period_ends_at->format('F j, Y') }}</strong> to renew your subscription.</p>
            <p>If payment is not received within the grace period, your system will be <strong>automatically deactivated</strong> and all users will lose access.</p>
            <p><strong>Please contact the Super Admin immediately to renew your subscription.</strong></p>
        </div>
    @elseif($type === 'deactivated')
        <div class="header">
            <h1 style="margin:0; font-size: 1.5rem;">❌ System Deactivated</h1>
            <p style="margin: 8px 0 0 0; opacity: 0.95;">Subscription Not Renewed</p>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p><strong>Your system for {{ $tenant->name }} has been deactivated</strong> due to non-payment after the grace period.</p>
            <div class="detail">
                <strong>Expiry Date:</strong> {{ $tenant->subscription_ends_at->format('l, F j, Y') }}<br>
                <strong>Grace Period Ended:</strong> {{ $tenant->grace_period_ends_at->format('l, F j, Y') }}
            </div>
            <p>All users associated with {{ $tenant->name }} can no longer access the system.</p>
            <p>To restore access, please contact the Super Admin to renew your subscription. Once payment is received, your system will be reactivated.</p>
            <p>If you have any questions or concerns, please contact the system administrator.</p>
        </div>
    @endif
    
    <div class="footer">
        <p>This is an automated notification from {{ config('bhcas.name') }}.</p>
        <p>If you have questions, please contact the Super Admin.</p>
    </div>
</body>
</html>
