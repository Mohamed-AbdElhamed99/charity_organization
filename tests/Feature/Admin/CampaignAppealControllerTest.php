<?php

namespace Tests\Feature\Admin;

use App\Models\Campaign;
use App\Models\CampaignAppeal;
use App\Models\User;
use Database\Seeders\FinancialFoundationSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CampaignAppealControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthorizedUser(): User
    {
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(FinancialFoundationSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title_en' => 'Ramadan Food Box',
            'title_ar' => 'صندوق رمضان الغذائي',
            'slug' => 'ramadan-food-box',
            'subtitle_en' => 'Help a family with essential food this Ramadan.',
            'subtitle_ar' => 'ساعد أسرة بالطعام الأساسي في رمضان.',
            'gift_options' => [
                ['name_en' => 'Medium Turkey', 'name_ar' => 'ديك رومي متوسط', 'amount' => 30],
                ['name_en' => 'Halal Turkey', 'name_ar' => 'ديك رومي حلال', 'amount' => 50],
            ],
            'allow_custom_amount' => 1,
            'cta_label_en' => 'Donate a Food Box',
            'cta_label_ar' => 'تبرع بصندوق طعام',
            'is_active' => 1,
            'sort_order' => 1,
        ], $overrides);
    }

    public function test_authorized_user_can_create_an_appeal_for_a_campaign(): void
    {
        $user = $this->createAuthorizedUser();
        $campaign = Campaign::factory()->active()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->post(route('admin.campaigns.appeals.store', $campaign), $this->validPayload())
            ->assertRedirect(route('admin.campaigns.appeals.index', $campaign));

        $appeal = CampaignAppeal::query()->where('slug', 'ramadan-food-box')->first();
        $this->assertNotNull($appeal);
        $this->assertSame($campaign->id, $appeal->campaign_id);
        $this->assertSame('Medium Turkey', $appeal->gift_options[0]['name_en']);
        $this->assertSame(3000, $appeal->gift_options[0]['amount_cents']);
        $this->assertSame('Halal Turkey', $appeal->gift_options[1]['name_en']);
        $this->assertSame(5000, $appeal->gift_options[1]['amount_cents']);
        $this->assertTrue($appeal->is_active);
    }

    public function test_authorized_user_can_view_and_update_an_appeal(): void
    {
        $user = $this->createAuthorizedUser();
        $campaign = Campaign::factory()->active()->create(['created_by' => $user->id]);
        $appeal = CampaignAppeal::factory()->create([
            'campaign_id' => $campaign->id,
            'slug' => 'warmth-blankets',
        ]);

        $this->actingAs($user)
            ->get(route('admin.campaigns.appeals.index', $campaign))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/campaigns/appeals/appeals-index')
                ->has('appeals', 1)
                ->where('appeals.0.slug', 'warmth-blankets')
            );

        $this->actingAs($user)
            ->put(route('admin.campaigns.appeals.update', [$campaign, $appeal]), $this->validPayload([
                'slug' => 'warmth-blankets',
                'title_en' => 'Give the Gift of Warmth',
                'gift_options' => [
                    ['name_en' => 'Blanket', 'name_ar' => 'بطانية', 'amount' => 10],
                ],
                'cta_label_en' => 'Donate Blankets',
            ]))
            ->assertRedirect(route('admin.campaigns.appeals.index', $campaign));

        $appeal->refresh();
        $this->assertSame('Give the Gift of Warmth', $appeal->title_en);
        $this->assertSame('Blanket', $appeal->gift_options[0]['name_en']);
        $this->assertSame(1000, $appeal->gift_options[0]['amount_cents']);
    }

    public function test_appeal_from_another_campaign_cannot_be_edited(): void
    {
        $user = $this->createAuthorizedUser();
        $campaign = Campaign::factory()->active()->create(['created_by' => $user->id]);
        $other = CampaignAppeal::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.campaigns.appeals.edit', [$campaign, $other]))
            ->assertNotFound();
    }
}
