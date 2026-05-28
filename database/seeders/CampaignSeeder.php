<?php

namespace Database\Seeders;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds campaign categories and campaigns.
 * Depends on: UserSeeder, GeoSeeder.
 */
class CampaignSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Campaign Categories ──────────────────────────────────────────────

        $categoriesData = [
            ['name_ar' => 'طبي',           'name_en' => 'Medical',          'description' => 'Medical treatment and healthcare support'],
            ['name_ar' => 'إسكان',          'name_en' => 'Housing',          'description' => 'Housing renovation and shelter support'],
            ['name_ar' => 'موسمي',          'name_en' => 'Seasonal',         'description' => 'Ramadan, Eid, winter, and seasonal campaigns'],
            ['name_ar' => 'تعليمي',         'name_en' => 'Education',        'description' => 'School supplies and education support'],
            ['name_ar' => 'حدث مجتمعي',     'name_en' => 'Community Event',  'description' => 'Community gatherings and events'],
            ['name_ar' => 'طارئ',           'name_en' => 'Emergency',        'description' => 'Emergency disaster and crisis relief'],
            ['name_ar' => 'دعم الأيتام',    'name_en' => 'Orphan Support',   'description' => 'Sponsorship and care for orphaned children'],
            ['name_ar' => 'أمن غذائي',      'name_en' => 'Food Security',    'description' => 'Food boxes and nutrition support'],
            ['name_ar' => 'مؤتمرات',        'name_en' => 'Conferences',      'description' => 'Press conferences and awareness events'],
            ['name_ar' => 'رعاية المسنين',  'name_en' => 'Elderly Care',     'description' => 'Support for elderly individuals'],
        ];

        foreach ($categoriesData as $data) {
            CampaignCategory::firstOrCreate(['name_en' => $data['name_en']], $data);
        }

        if (! app()->environment(['local', 'testing'])) {
            $this->command->info('✅ Campaign categories seeded. Skipping fake campaigns (production).');
            return;
        }

        // ─── Known Campaigns (realistic examples from requirements) ──────────

        $staffUser  = User::role('staff')->inRandomOrder()->first()
            ?? User::role('super_admin')->first();

        $medicalCat   = CampaignCategory::where('name_en', 'Medical')->first();
        $housingCat   = CampaignCategory::where('name_en', 'Housing')->first();
        $seasonalCat  = CampaignCategory::where('name_en', 'Seasonal')->first();
        $conferCat    = CampaignCategory::where('name_en', 'Conferences')->first();
        $orphanCat    = CampaignCategory::where('name_en', 'Orphan Support')->first();

        $knownCampaigns = [
            [
                'title_en'          => 'Cancer Treatment Support for Ahmed',
                'title_ar'          => 'دعم علاج السرطان لأحمد',
                'excerpt_en'        => 'Help a 10-year-old boy fighting cancer get the treatment he needs.',
                'excerpt_ar'        => 'ساعد طفلاً في العاشرة من عمره في مواجهة السرطان.',
                'category_id'       => $medicalCat?->id,
                'status'            => CampaignStatus::Active,
                'is_public'         => true,
                'open_donation_form' => true,
                'budget'            => 15_000.00,
                'donation_target'   => 20_000.00,
                'start_date'        => now()->subMonth()->format('Y-m-d'),
                'end_date'          => now()->addMonths(2)->format('Y-m-d'),
            ],
            [
                'title_en'          => 'House Renovation in Al-Qubeiba',
                'title_ar'          => 'تجديد منزل في القبيبة',
                'excerpt_en'        => 'Renovating a dilapidated home for a family of 7 in Al-Qubeiba village.',
                'excerpt_ar'        => 'تجديد منزل متهالك لأسرة مكونة من 7 أفراد في قرية القبيبة.',
                'category_id'       => $housingCat?->id,
                'status'            => CampaignStatus::Active,
                'is_public'         => true,
                'open_donation_form' => true,
                'budget'            => 8_000.00,
                'donation_target'   => 10_000.00,
                'start_date'        => now()->subWeeks(2)->format('Y-m-d'),
                'end_date'          => now()->addMonths(1)->format('Y-m-d'),
            ],
            [
                'title_en'          => 'Ramadan Celebration for Orphaned Children',
                'title_ar'          => 'احتفالية رمضان للأطفال الأيتام',
                'excerpt_en'        => 'Bringing joy to orphaned children with a Ramadan celebration in Al-Qubeiba.',
                'excerpt_ar'        => 'إدخال الفرح على قلوب الأيتام باحتفالية رمضانية في القبيبة.',
                'category_id'       => $seasonalCat?->id,
                'status'            => CampaignStatus::Completed,
                'is_public'         => true,
                'open_donation_form' => false,
                'budget'            => 3_000.00,
                'donation_target'   => 3_000.00,
                'start_date'        => now()->subMonths(3)->format('Y-m-d'),
                'end_date'          => now()->subMonths(2)->format('Y-m-d'),
            ],
            [
                'title_en'          => 'Press Conference to Support the Kidney Center',
                'title_ar'          => 'مؤتمر صحفي لدعم مركز الكلى',
                'excerpt_en'        => 'Raising awareness and funds for the kidney treatment center.',
                'excerpt_ar'        => 'رفع الوعي وجمع التبرعات لمركز علاج أمراض الكلى.',
                'category_id'       => $conferCat?->id,
                'status'            => CampaignStatus::Draft,
                'is_public'         => false,
                'open_donation_form' => false,
                'budget'            => 5_000.00,
                'donation_target'   => null,
                'start_date'        => now()->addWeeks(2)->format('Y-m-d'),
                'end_date'          => now()->addWeeks(3)->format('Y-m-d'),
            ],
            [
                'title_en'          => 'Orphan Sponsorship Program',
                'title_ar'          => 'برنامج كفالة الأيتام',
                'excerpt_en'        => 'Monthly sponsorship providing education, food, and healthcare for orphaned children.',
                'excerpt_ar'        => 'كفالة شهرية توفر التعليم والغذاء والرعاية الصحية للأطفال الأيتام.',
                'category_id'       => $orphanCat?->id,
                'status'            => CampaignStatus::Active,
                'is_public'         => true,
                'open_donation_form' => true,
                'budget'            => 50_000.00,
                'donation_target'   => 60_000.00,
                'is_repeated'       => 'monthly',
                'repeat_until'      => now()->addYear()->format('Y-m-d'),
                'start_date'        => now()->subMonths(6)->format('Y-m-d'),
                'end_date'          => now()->addYear()->format('Y-m-d'),
            ],
        ];

        foreach ($knownCampaigns as $data) {
            Campaign::firstOrCreate(
                ['title_en' => $data['title_en']],
                array_merge($data, [
                    'slug'       => \Illuminate\Support\Str::slug($data['title_en']) . '-' . rand(1000000000, 9999999999),
                    'created_by' => $staffUser->id,
                ])
            );
        }

        // ─── Additional fake campaigns ────────────────────────────────────────

        // Active campaigns
        Campaign::factory()
            ->count(4)
            ->active()
            ->recycle(CampaignCategory::all())
            ->create(['created_by' => $staffUser->id]);

        // Completed campaigns (for reports)
        Campaign::factory()
            ->count(4)
            ->completed()
            ->recycle(CampaignCategory::all())
            ->create(['created_by' => $staffUser->id]);

        // Draft campaigns
        Campaign::factory()
            ->count(4)
            ->draft()
            ->recycle(CampaignCategory::all())
            ->create(['created_by' => $staffUser->id]);

        $this->command->info('✅ Campaigns seeded (' . Campaign::count() . ' total, ' . CampaignCategory::count() . ' categories).');
    }
}