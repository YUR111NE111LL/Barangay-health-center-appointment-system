<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $sumpong = Tenant::whereHas('domains', fn ($q) => $q->where('domain', 'brgy-sumpong.test'))->first();
        $casisang = Tenant::whereHas('domains', fn ($q) => $q->where('domain', 'brgy-casisang.test'))->first();

        if (! $sumpong || ! $casisang) {
            return;
        }

        $baseDate = Carbon::today()->addDays(7);

        $eventsSumpong = [
            [
                'title' => 'Malaybalay City Health – Free Vaccination Drive',
                'description' => 'Free flu and COVID-19 booster vaccination for residents. In partnership with Malaybalay City Health Office. Bring your vaccination card and valid ID.',
                'event_date' => $baseDate->copy()->addDays(3),
                'event_time' => '08:00',
                'location' => 'Brgy Sumpong Health Center, Malaybalay City',
            ],
            [
                'title' => 'Maternal and Child Health Check-up',
                'description' => 'Free prenatal and child health check-ups. Part of Malaybalay LGU health programs. Pregnant women and children 0–5 years welcome.',
                'event_date' => $baseDate->copy()->addDays(10),
                'event_time' => '09:00',
                'location' => 'Sumpong BHC, Malaybalay City',
            ],
            [
                'title' => 'Dengue Prevention and Health Info Session',
                'description' => 'Learn about dengue prevention and healthy habits. Malaybalay City Health Office information drive for a healthier community.',
                'event_date' => $baseDate->copy()->addDays(14),
                'event_time' => '14:00',
                'location' => 'Brgy Sumpong Multi-Purpose Hall, Malaybalay City',
            ],
        ];

        $eventsCasisang = [
            [
                'title' => 'Malaybalay Health – Medical Mission',
                'description' => 'Free consultation and basic medicines. In coordination with Malaybalay City Health. First-come, first-served. Bring any recent lab results if available.',
                'event_date' => $baseDate->copy()->addDays(5),
                'event_time' => '07:30',
                'location' => 'Brgy Casisang Health Center, Malaybalay City',
            ],
            [
                'title' => 'TB Screening and Awareness',
                'description' => 'Free TB screening and awareness seminar. Supported by Malaybalay City Health Office. Help us make Malaybalay TB-free.',
                'event_date' => $baseDate->copy()->addDays(12),
                'event_time' => '08:00',
                'location' => 'Casisang BHC, Malaybalay City',
            ],
            [
                'title' => 'Senior Citizen Health Day',
                'description' => 'Blood pressure check, blood sugar screening, and health tips for seniors. Malaybalay LGU program for 60+ residents.',
                'event_date' => $baseDate->copy()->addDays(18),
                'event_time' => '09:00',
                'location' => 'Brgy Casisang Health Center, Malaybalay City',
            ],
        ];

        foreach ($eventsSumpong as $e) {
            Event::firstOrCreate(
                [
                    'tenant_id' => $sumpong->id,
                    'title' => $e['title'],
                    'event_date' => $e['event_date']->toDateString(),
                ],
                [
                    'description' => $e['description'],
                    'event_time' => $e['event_time'],
                    'location' => $e['location'],
                    'is_published' => true,
                ]
            );
        }

        foreach ($eventsCasisang as $e) {
            Event::firstOrCreate(
                [
                    'tenant_id' => $casisang->id,
                    'title' => $e['title'],
                    'event_date' => $e['event_date']->toDateString(),
                ],
                [
                    'description' => $e['description'],
                    'event_time' => $e['event_time'],
                    'location' => $e['location'],
                    'is_published' => true,
                ]
            );
        }
    }
}
