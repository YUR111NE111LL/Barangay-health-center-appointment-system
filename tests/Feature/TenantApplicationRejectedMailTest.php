<?php

use App\Mail\TenantApplicationRejected;

it('builds tenant application rejected mailable with optional reason', function (): void {
    $mailable = new TenantApplicationRejected('Test Org', 'Sample Barangay', 'Please try again next quarter.');

    $envelope = $mailable->envelope();
    $content = $mailable->content();

    expect($envelope->subject)->not->toBe('');
    expect($content->view)->toBe('emails.tenant-application-rejected');
    expect($mailable->organizationName)->toBe('Test Org');
    expect($mailable->barangay)->toBe('Sample Barangay');
    expect($mailable->rejectionReason)->toBe('Please try again next quarter.');
});
