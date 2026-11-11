<?php

namespace Tests\Feature\Site;

use App\Enums\BeneficiaryStatus;
use App\Enums\BeneficiaryType;
use App\Enums\SupportStatus;
use App\Enums\TurkeyRequestStatus;
use App\Models\Beneficiary;
use App\Models\BeneficiarySupport;
use App\Models\Campaign;
use App\Models\TurkeyRequest;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TurkeyCampaignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        User::factory()->create()->assignRole('super_admin');
        config(['turkey_campaign.total' => 3, 'turkey_campaign.enabled' => true]);
    }

    /**
     * @return array<string, string>
     */
    private function formPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Ahmed Hassan Ali',
            'email' => 'ahmed@example.com',
            'phone' => '5551234567',
            'address' => '12 Main Street, Cairo',
        ], $overrides);
    }

    public function test_page_shows_remaining_turkeys_and_share_meta_tags(): void
    {
        TurkeyRequest::factory()->create();

        $this->get(route('turkey.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('site/turkey/turkey-show')
                ->where('remaining', 2)
                ->where('total', 3)
            )
            ->assertSee('property="og:title" content="Free Halal Turkey', false)
            ->assertSee('property="og:image" content="'.url('/images/turkey-campaign.jpg').'"', false)
            ->assertSee('property="og:image:width" content="819"', false)
            ->assertSee('property="og:url" content="'.route('turkey.show').'"', false)
            ->assertSee('name="twitter:card" content="summary_large_image"', false)
            ->assertSee('name="twitter:image" content="'.url('/images/turkey-campaign.jpg').'"', false);
    }

    public function test_submitting_the_form_creates_beneficiary_request_and_reduces_stock(): void
    {
        $this->post(route('turkey.store'), $this->formPayload())->assertRedirect();

        $request = TurkeyRequest::query()->firstOrFail();
        $this->assertSame(TurkeyRequestStatus::Pending, $request->status);
        $this->assertMatchesRegularExpression('/^TRK-[A-Z2-9]{6}$/', $request->code);

        $beneficiary = Beneficiary::query()->with('individual')->findOrFail($request->beneficiary_id);
        $this->assertSame(BeneficiaryType::Individual, $beneficiary->type);
        $this->assertSame(BeneficiaryStatus::PendingAssessment, $beneficiary->status);
        $this->assertSame('Ahmed', $beneficiary->individual->first_name);
        $this->assertSame('Hassan Ali', $beneficiary->individual->last_name);
        $this->assertSame('5551234567', $beneficiary->individual->phone);
        $this->assertSame('12 Main Street, Cairo', $beneficiary->individual->address);
        $this->assertSame('12 Main Street, Cairo', $request->address);

        $campaign = Campaign::query()->findOrFail($request->campaign_id);
        $this->assertFalse($campaign->is_public);

        $this->get(route('turkey.show'))
            ->assertInertia(fn (Assert $page) => $page->where('remaining', 2));
    }

    public function test_hidden_campaign_is_not_listed_publicly(): void
    {
        $this->post(route('turkey.store'), $this->formPayload());

        $this->get(route('campaigns.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('campaigns.data', 0));
    }

    public function test_form_is_rejected_when_sold_out(): void
    {
        TurkeyRequest::factory()->count(2)->create();
        TurkeyRequest::factory()->delivered()->create();

        $this->post(route('turkey.store'), $this->formPayload())
            ->assertSessionHasErrors('full_name');

        $this->assertSame(3, TurkeyRequest::query()->count());
    }

    public function test_cancelled_requests_free_a_turkey(): void
    {
        TurkeyRequest::factory()->count(2)->create();
        TurkeyRequest::factory()->cancelled()->create();

        $this->post(route('turkey.store'), $this->formPayload())->assertSessionHasNoErrors();

        $this->assertSame(4, TurkeyRequest::query()->count());
    }

    public function test_duplicate_phone_or_email_is_rejected(): void
    {
        TurkeyRequest::factory()->create(['email' => 'ahmed@example.com', 'phone' => '5551234567']);

        $this->post(route('turkey.store'), $this->formPayload())
            ->assertSessionHasErrors(['email', 'phone']);
    }

    public function test_required_fields_are_validated(): void
    {
        $this->post(route('turkey.store'), [])
            ->assertSessionHasErrors(['full_name', 'email', 'phone', 'address']);
    }

    public function test_valid_code_confirms_delivery_and_records_support(): void
    {
        $this->post(route('turkey.store'), $this->formPayload());
        $request = TurkeyRequest::query()->firstOrFail();

        $this->post(route('turkey.verify.store'), ['code' => strtolower($request->code)])
            ->assertSessionHasNoErrors();

        $request->refresh();
        $this->assertSame(TurkeyRequestStatus::Delivered, $request->status);
        $this->assertNotNull($request->delivered_at);

        $support = BeneficiarySupport::query()->firstOrFail();
        $this->assertSame($request->beneficiary_support_id, $support->id);
        $this->assertSame($request->beneficiary_id, $support->beneficiary_id);
        $this->assertSame($request->campaign_id, $support->campaign_id);
        $this->assertSame(SupportStatus::Delivered, $support->status);
        $this->assertSame(BeneficiaryStatus::Active, $request->beneficiary->refresh()->status);
    }

    public function test_unknown_code_is_rejected(): void
    {
        $this->post(route('turkey.verify.store'), ['code' => 'TRK-NOPE22'])
            ->assertSessionHasErrors('code');

        $this->assertSame(0, BeneficiarySupport::query()->count());
    }

    public function test_code_cannot_be_used_twice(): void
    {
        $request = TurkeyRequest::factory()->create();

        $this->post(route('turkey.verify.store'), ['code' => $request->code])->assertSessionHasNoErrors();
        $this->post(route('turkey.verify.store'), ['code' => $request->code])->assertSessionHasErrors('code');

        $this->assertSame(1, BeneficiarySupport::query()->count());
    }

    public function test_cancelled_request_code_cannot_be_verified(): void
    {
        $request = TurkeyRequest::factory()->cancelled()->create();

        $this->post(route('turkey.verify.store'), ['code' => $request->code])
            ->assertSessionHasErrors('code');
    }

    public function test_verify_page_renders(): void
    {
        $this->get(route('turkey.verify'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('site/turkey/turkey-verify'));
    }

    public function test_public_routes_return_not_found_when_campaign_is_disabled(): void
    {
        config(['turkey_campaign.enabled' => false]);

        $this->get(route('turkey.show'))->assertNotFound();
        $this->post(route('turkey.store'), $this->formPayload())->assertNotFound();
        $this->get(route('turkey.verify'))->assertNotFound();
        $this->post(route('turkey.verify.store'), ['code' => 'TRK-AAAAAA'])->assertNotFound();
    }
}
