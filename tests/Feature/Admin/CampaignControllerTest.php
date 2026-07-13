<?php

namespace Tests\Feature\Admin;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\CampaignExpense;
use App\Models\Meeting;
use App\Models\User;
use Database\Seeders\FinancialFoundationSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CampaignControllerTest extends TestCase
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
     * @return array<string, mixed>
     */
    private function validPayload(CampaignCategory $category, array $overrides = []): array
    {
        return array_merge([
            'title_ar' => 'حملة طبية',
            'title_en' => 'Medical Campaign',
            'slug' => 'medical-campaign',
            'category_id' => $category->id,
            'excerpt_ar' => 'مقتطف',
            'excerpt_en' => 'Excerpt',
            'description_ar' => 'وصف',
            'description_en' => 'Description',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonth()->format('Y-m-d'),
            'budget' => 5000,
            'donation_target' => 10000,
            'status' => CampaignStatus::Draft->value,
            'is_public' => false,
            'open_donation_form' => false,
            'is_repeated' => 'never',
        ], $overrides);
    }

    public function test_authorized_user_can_view_campaigns_index(): void
    {
        $user = $this->createAuthorizedUser();
        Campaign::factory()->count(2)->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->get(route('admin.campaigns.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/campaigns/campaigns-index')
                ->has('campaigns.data', 2)
                ->has('categories')
            );
    }

    public function test_authorized_user_can_view_create_page(): void
    {
        $user = $this->createAuthorizedUser();

        $this->actingAs($user)
            ->get(route('admin.campaigns.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/campaigns/campaigns-create')
                ->has('categories')
                ->has('meetingOptions')
            );
    }

    public function test_authorized_user_can_view_edit_page(): void
    {
        $user = $this->createAuthorizedUser();
        $campaign = Campaign::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->get(route('admin.campaigns.edit', $campaign))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/campaigns/campaigns-edit')
                ->where('campaign.id', $campaign->id)
                ->has('meetingOptions')
            );
    }

    public function test_authorized_user_can_view_campaign_show_with_reconciliation(): void
    {
        $user = $this->createAuthorizedUser();
        $campaign = Campaign::factory()->create(['created_by' => $user->id]);

        CampaignExpense::factory()->create([
            'campaign_id' => $campaign->id,
            'responsible_user_id' => $user->id,
            'amount' => 100.50,
        ]);

        $this->actingAs($user)
            ->get(route('admin.campaigns.show', $campaign))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/campaigns/campaigns-show')
                ->where('campaign.id', $campaign->id)
                ->where('reconciliation.distributed_total', 0)
                ->where('reconciliation.campaign_expenses_total', 10050)
                ->where('reconciliation.gap', 10050)
            );
    }

    public function test_authorized_user_can_create_campaign(): void
    {
        $user = $this->createAuthorizedUser();
        $category = CampaignCategory::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('admin.campaigns.store'), $this->validPayload($category));

        $campaign = Campaign::query()->where('slug', 'medical-campaign')->first();
        $this->assertNotNull($campaign);

        $response->assertRedirect(route('admin.campaigns.show', $campaign));

        $this->assertDatabaseHas('campaigns', [
            'slug' => 'medical-campaign',
            'title_en' => 'Medical Campaign',
            'created_by' => $user->id,
        ]);
    }

    public function test_authorized_user_can_sync_meetings_on_create(): void
    {
        $user = $this->createAuthorizedUser();
        $category = CampaignCategory::factory()->create();
        $meeting = Meeting::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->post(route('admin.campaigns.store'), $this->validPayload($category, [
                'meeting_ids' => [$meeting->id],
            ]))
            ->assertRedirect();

        $campaign = Campaign::query()->where('slug', 'medical-campaign')->first();
        $this->assertNotNull($campaign);
        $this->assertTrue($campaign->meetings()->whereKey($meeting->id)->exists());
    }

    public function test_authorized_user_can_update_campaign_and_sync_meetings(): void
    {
        $user = $this->createAuthorizedUser();
        $category = CampaignCategory::factory()->create();
        $campaign = Campaign::factory()->create([
            'created_by' => $user->id,
            'category_id' => $category->id,
            'slug' => 'existing-campaign',
        ]);
        $meeting = Meeting::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->patch(route('admin.campaigns.update', $campaign), $this->validPayload($category, [
                'slug' => 'existing-campaign',
                'title_en' => 'Updated Campaign',
                'meeting_ids' => [$meeting->id],
            ]))
            ->assertRedirect(route('admin.campaigns.show', $campaign));

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'title_en' => 'Updated Campaign',
        ]);
        $this->assertTrue($campaign->fresh()->meetings()->whereKey($meeting->id)->exists());
    }

    public function test_store_validates_required_description_ar(): void
    {
        $user = $this->createAuthorizedUser();
        $category = CampaignCategory::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.campaigns.store'), $this->validPayload($category, ['description_ar' => null]))
            ->assertSessionHasErrors(['description_ar']);
    }

    public function test_store_description_en_is_optional(): void
    {
        $user = $this->createAuthorizedUser();
        $category = CampaignCategory::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.campaigns.store'), $this->validPayload($category, ['description_en' => null]))
            ->assertSessionDoesntHaveErrors(['description_en']);
    }

    public function test_store_empty_paragraph_description_ar_fails_required(): void
    {
        $user = $this->createAuthorizedUser();
        $category = CampaignCategory::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.campaigns.store'), $this->validPayload($category, ['description_ar' => '<p></p>']))
            ->assertSessionHasErrors(['description_ar']);
    }

    public function test_store_html_description_ar_is_sanitized_before_storage(): void
    {
        $user = $this->createAuthorizedUser();
        $category = CampaignCategory::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.campaigns.store'), $this->validPayload($category, [
                'description_ar' => '<p>وصف</p><script>alert(1)</script>',
            ]))
            ->assertSessionDoesntHaveErrors();

        $campaign = Campaign::query()->where('slug', 'medical-campaign')->first();
        $this->assertStringNotContainsString('<script>', (string) $campaign->description_ar);
        $this->assertStringContainsString('وصف', (string) $campaign->description_ar);
    }

    public function test_campaign_with_expenses_cannot_be_deleted(): void
    {
        $user = $this->createAuthorizedUser();
        $campaign = Campaign::factory()->create(['created_by' => $user->id]);
        CampaignExpense::factory()->create([
            'campaign_id' => $campaign->id,
            'responsible_user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->delete(route('admin.campaigns.destroy', $campaign))
            ->assertRedirect();

        $this->assertNotSoftDeleted('campaigns', ['id' => $campaign->id]);
    }
}
