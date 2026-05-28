<?php

namespace Database\Seeders;

use App\Models\AboutUs;
use App\Models\ContactUs;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Slider;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds all CMS content: news categories, news articles,
 * about us page, sliders, and contact us submissions.
 *
 * Depends on: UserSeeder.
 */
class CmsSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::role('staff')->first()
            ?? User::role('super_admin')->first();

        // ─── News Categories ──────────────────────────────────────────────────

        $categoriesData = [
            ['name_ar' => 'أخبار المنظمة',    'name_en' => 'Organization News'],
            ['name_ar' => 'فعاليات',           'name_en' => 'Events'],
            ['name_ar' => 'قصص نجاح',          'name_en' => 'Success Stories'],
            ['name_ar' => 'إعلانات',           'name_en' => 'Announcements'],
            ['name_ar' => 'تقارير',            'name_en' => 'Reports'],
            ['name_ar' => 'شراكات',            'name_en' => 'Partnerships'],
        ];

        foreach ($categoriesData as $data) {
            NewsCategory::firstOrCreate(['name_en' => $data['name_en']], array_merge($data, ['is_active' => true]));
        }

        $categories = NewsCategory::all();

        // ─── Known News Articles ──────────────────────────────────────────────

        $knownArticles = [
            [
                'title_en'     => 'New Egypt Org Launches Orphan Sponsorship Program',
                'title_ar'     => 'مصر الجديدة تطلق برنامج كفالة الأيتام',
                'excerpt_en'   => 'New Egypt Group announces the launch of its comprehensive orphan sponsorship program.',
                'excerpt_ar'   => 'تعلن مجموعة مصر الجديدة عن إطلاق برنامجها الشامل لكفالة الأيتام.',
                'body_en'      => 'New Egypt Group is proud to announce the launch of its orphan sponsorship program, providing comprehensive support to orphaned children including education, healthcare, and monthly stipends.',
                'body_ar'      => 'تفخر مجموعة مصر الجديدة بالإعلان عن إطلاق برنامج كفالة الأيتام الشامل، الذي يوفر الدعم الكامل للأطفال الأيتام بما في ذلك التعليم والرعاية الصحية والمستحقات الشهرية.',
                'category_en'  => 'Announcements',
                'is_active'    => true,
            ],
            [
                'title_en'     => 'Ramadan 2024: Food Boxes Distribution Success',
                'title_ar'     => 'رمضان 2024: نجاح توزيع صناديق الطعام',
                'excerpt_en'   => 'Over 500 families received food boxes during Ramadan thanks to our generous donors.',
                'excerpt_ar'   => 'حصلت أكثر من 500 أسرة على صناديق طعام خلال شهر رمضان المبارك بفضل كرم المتبرعين.',
                'body_en'      => 'Alhamdulillah, this Ramadan was a great success. Our volunteers worked tirelessly to distribute over 500 food boxes to families in need across multiple villages.',
                'body_ar'      => 'الحمد لله، كان هذا الرمضان نجاحاً كبيراً. عمل متطوعونا بلا كلل لتوزيع أكثر من 500 صندوق طعام على الأسر المحتاجة عبر قرى متعددة.',
                'category_en'  => 'Success Stories',
                'is_active'    => true,
            ],
            [
                'title_en'     => 'New Egypt Org Partners with Kidney Center',
                'title_ar'     => 'مصر الجديدة تتشارك مع مركز الكلى',
                'excerpt_en'   => 'A new partnership to support kidney patients with treatment costs.',
                'excerpt_ar'   => 'شراكة جديدة لدعم مرضى الكلى في تكاليف العلاج.',
                'body_en'      => 'New Egypt Group is proud to announce a strategic partnership with the Regional Kidney Center to help cover treatment costs for patients who cannot afford dialysis.',
                'body_ar'      => 'تفخر مجموعة مصر الجديدة بالإعلان عن شراكة استراتيجية مع مركز الكلى الإقليمي للمساعدة في تغطية تكاليف العلاج للمرضى الذين لا يستطيعون تحمل نفقات غسيل الكلى.',
                'category_en'  => 'Partnerships',
                'is_active'    => true,
            ],
            [
                'title_en'     => 'Annual Report 2023: Impact & Achievements',
                'title_ar'     => 'التقرير السنوي 2023: الأثر والإنجازات',
                'excerpt_en'   => 'A year in review: 1,200 families supported, 300 orphans sponsored.',
                'excerpt_ar'   => 'مراجعة عام كامل: 1,200 أسرة مدعومة، 300 يتيم مكفول.',
                'body_en'      => 'In 2023, New Egypt Group supported over 1,200 families, sponsored 300 orphans, completed 45 campaigns, and distributed over 2,000 food boxes.',
                'body_ar'      => 'في عام 2023، دعمت مجموعة مصر الجديدة أكثر من 1,200 أسرة، وكفلت 300 يتيم، وأتمت 45 حملة، ووزعت أكثر من 2,000 صندوق طعام.',
                'category_en'  => 'Reports',
                'is_active'    => true,
            ],
        ];

        foreach ($knownArticles as $articleData) {
            $category = $categories->where('name_en', $articleData['category_en'])->first();
            unset($articleData['category_en']);

            News::firstOrCreate(
                ['title_en' => $articleData['title_en']],
                array_merge($articleData, [
                    'slug'         => \Illuminate\Support\Str::slug($articleData['title_en']) . '-' . rand(100, 999),
                    'category_id'  => $category?->id,
                    'published_at' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
                    'created_by'   => $author->id,
                ])
            );
        }

        // Additional faker news for dev environment
        if (app()->environment(['local', 'testing'])) {
            News::factory()
                ->count(20)
                ->published()
                ->recycle($categories)
                ->create(['created_by' => $author->id]);

            News::factory()
                ->count(5)
                ->draft()
                ->recycle($categories)
                ->create(['created_by' => $author->id]);
        }

        // ─── About Us (single record) ─────────────────────────────────────────

        AboutUs::firstOrCreate(
            ['id' => 1],
            [
                'title_ar'                  => 'من نحن',
                'title_en'                  => 'About Us',
                'mission_title_ar'          => 'رسالتنا',
                'mission_title_en'          => 'Our Mission',
                'mission_description_ar'    => 'مساعدة المحتاجين وتمكين المجتمعات من خلال العطاء المستدام.',
                'mission_description_en'    => 'Helping those in need and empowering communities through sustainable giving.',
                'message_title_ar'          => 'كلمة الرئيس',
                'message_title_en'          => 'Message from the President',
                'message_description_ar'    => 'نؤمن بأن التضامن الاجتماعي هو أساس المجتمع القوي.',
                'message_description_en'    => 'We believe that social solidarity is the foundation of a strong community.',
                'team_title_ar'             => 'فريقنا',
                'team_title_en'             => 'Our Team',
                'team_description_ar'       => 'فريق متطوع متفانٍ يعمل على مدار الساعة لخدمة المحتاجين.',
                'team_description_en'       => 'A dedicated volunteer team working around the clock to serve those in need.',
                'body_ar'                   => 'مجموعة مصر الجديدة منظمة غير ربحية تأسست بهدف تقديم الدعم للأسر المحتاجة والأيتام والمرضى.',
                'body_en'                   => 'New Egypt Group is a non-profit organization founded to provide support to families in need, orphans, and patients.',
                'video_url'                 => null,
            ]
        );

        // ─── Sliders ──────────────────────────────────────────────────────────

        $slidersData = [
            ['order' => 1, 'is_active' => true],
            ['order' => 2, 'is_active' => true],
            ['order' => 3, 'is_active' => false],
        ];

        foreach ($slidersData as $sliderData) {
            if (Slider::where('order', $sliderData['order'])->doesntExist()) {
                Slider::create($sliderData);
            }
        }

        // ─── Contact Us Submissions ───────────────────────────────────────────

        if (app()->environment(['local', 'testing'])) {
            // Unreviewed (inbox)
            ContactUs::factory()
                ->count(10)
                ->unreviewed()
                ->create();

            // Reviewed (resolved)
            ContactUs::factory()
                ->count(15)
                ->reviewed()
                ->create();
        }

        $this->command->info('✅ CMS seeded:');
        $this->command->info('   — News Categories: ' . NewsCategory::count());
        $this->command->info('   — News Articles:   ' . News::count());
        $this->command->info('   — Sliders:         ' . Slider::count());
        $this->command->info('   — Contact Us:      ' . ContactUs::count());
    }
}