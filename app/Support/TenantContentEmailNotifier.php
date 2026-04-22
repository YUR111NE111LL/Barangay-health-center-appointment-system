<?php

namespace App\Support;

use App\Mail\TenantAnnouncementEmail;
use App\Mail\TenantEventEmail;
use App\Models\Announcement;
use App\Models\Event;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

final class TenantContentEmailNotifier
{
    /**
     * Send announcement emails to tenant users who may use the app and have an email on file:
     * approved users, plus roles that do not require approval (e.g. residents) even if {@see User::$is_approved} is false.
     * Same pattern as appointment status mail: synchronous send, explicit mailer.
     *
     * @return int Number of send attempts (one per recipient)
     */
    public static function queueAnnouncementEmails(Tenant $tenant, Announcement $announcement): int
    {
        $tenant->loadMissing('domains');
        $announcement->loadMissing('creator');
        $viewUrl = self::residentAbsoluteUrl($tenant, 'resident/announcements/'.$announcement->getKey());
        // Use barangay/tenant display name in email content instead of the staff/admin poster name.
        $posterName = $tenant->barangayDisplayName();
        $title = $announcement->title;
        $excerpt = Str::limit(strip_tags((string) $announcement->body), 220);

        $count = 0;
        $mailer = config('mail.default');
        foreach (self::recipients($tenant) as $user) {
            try {
                Mail::mailer($mailer)->to($user->email)->send(new TenantAnnouncementEmail(
                    posterName: $posterName,
                    recipientName: $user->name,
                    title: $title,
                    excerpt: $excerpt,
                    viewUrl: $viewUrl,
                ));
            } catch (\Throwable $e) {
                report($e);
            }
            $count++;
        }

        return $count;
    }

    /**
     * Send event emails to the same recipient set as announcements.
     *
     * @return int Number of send attempts (one per recipient)
     */
    public static function queueEventEmails(Tenant $tenant, Event $event): int
    {
        $tenant->loadMissing('domains');
        $viewUrl = self::residentAbsoluteUrl($tenant, 'resident/events/'.$event->getKey());
        $barangayName = $tenant->barangayDisplayName();
        $title = $event->title;
        $excerpt = Str::limit(strip_tags((string) $event->description), 220);
        $when = Carbon::parse($event->event_date)->format('l, F j, Y');
        if ($event->event_time) {
            $when .= ' · '.Carbon::parse($event->event_time)->format('g:i A');
        }
        $location = $event->location ? (string) $event->location : null;

        $count = 0;
        $mailer = config('mail.default');
        foreach (self::recipients($tenant) as $user) {
            try {
                Mail::mailer($mailer)->to($user->email)->send(new TenantEventEmail(
                    barangayName: $barangayName,
                    recipientName: $user->name,
                    title: $title,
                    excerpt: $excerpt,
                    whenLine: $when,
                    location: $location,
                    viewUrl: $viewUrl,
                ));
            } catch (\Throwable $e) {
                report($e);
            }
            $count++;
        }

        return $count;
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private static function recipients(Tenant $tenant): \Illuminate\Support\Collection
    {
        $rolesRequiringApproval = User::rolesRequiringApproval();

        return User::query()
            ->where('tenant_id', (int) $tenant->id)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->where(function ($q) use ($rolesRequiringApproval) {
                $q->where('is_approved', true)
                    ->orWhereNotIn('role', $rolesRequiringApproval);
            })
            ->get()
            ->unique(fn (User $user): string => strtolower((string) $user->email));
    }

    private static function residentAbsoluteUrl(Tenant $tenant, string $path): string
    {
        $domain = $tenant->domains->first()?->domain;
        if (! is_string($domain) || $domain === '') {
            return url($path);
        }

        $scheme = request()->getScheme();
        $port = request()->getPort();
        $base = $scheme.'://'.$domain;
        if (! in_array((int) $port, [80, 443], true) && $port) {
            $base .= ':'.$port;
        }

        return $base.'/'.ltrim($path, '/');
    }
}
