<?php

namespace Tests\Feature;

use App\Enums\DonationStatus;
use App\Mail\DonationSubmittedMailable;
use App\Models\Campaign;
use App\Models\CampaignAppeal;
use App\Models\Donation;
use App\Models\User;
use Database\Seeders\FinancialFoundationSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ZelleDonationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(FinancialFoundationSeeder::class);
        $this->withoutVite();
    }

    public function test_zelle_submission_stores_a_pending_donation_and_notifies_admin(): void
    {
        Mail::fake();

        $appeal = $this->createDonatableAppeal();

        $response = $this->post(route('donations.zelle'), [
            'campaign_id' => $appeal->campaign_id,
            'is_general' => false,
            'amount' => 3000,
            'appeal_id' => $appeal->id,
            'gift_option' => 0,
            'first_name' => 'Jane',
            'last_name' => 'Donor',
            'email' => 'jane-zelle@example.com',
            'phone' => '555-0100',
            'receipt' => UploadedFile::fake()->image('zelle-receipt.jpg'),
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', DonationStatus::Pending->value);

        $thankYouUrl = $response->json('thankYouUrl');
        $this->assertIsString($thankYouUrl);

        $this->get($thankYouUrl)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('site/donations/thank-you')
                ->where('variant', 'zelle')
                ->where('donation.status', 'pending')
                ->where('donation.amount_cents', 3000)
            );

        $donation = Donation::query()->findOrFail($response->json('donationId'));

        $this->get(route('donations.zelle.thank-you', $donation))->assertForbidden();
        $donation->load('paymentMethod');

        $this->assertSame(DonationStatus::Pending, $donation->status);
        $this->assertNull($donation->stripe_status);
        $this->assertNull($donation->transaction_id);
        $this->assertSame($appeal->id, $donation->appeal_id);
        $this->assertSame($appeal->campaign_id, $donation->campaign_id);
        $this->assertSame(3000, $donation->amount);
        $this->assertSame('Medium Turkey', $donation->unit_label);
        $this->assertSame('zelle', $donation->paymentMethod?->code);
        $this->assertSame('jane-zelle@example.com', $donation->donor?->email);
        $this->assertCount(1, $donation->getMedia('zelle_receipt'));
        $this->assertSame(0, $appeal->campaign->fresh()->collected_amount);

        Mail::assertQueued(DonationSubmittedMailable::class, function (DonationSubmittedMailable $mail) use ($donation) {
            return $mail->hasTo(config('site.admin_notification_email'))
                && $mail->donation->is($donation);
        });
    }

    public function test_zelle_submission_requires_a_receipt(): void
    {
        $appeal = $this->createDonatableAppeal();

        $this->postJson(route('donations.zelle'), [
            'campaign_id' => $appeal->campaign_id,
            'is_general' => false,
            'amount' => 3000,
            'appeal_id' => $appeal->id,
            'gift_option' => 0,
            'first_name' => 'Jane',
            'last_name' => 'Donor',
            'email' => 'jane-zelle-missing@example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['receipt']);
    }

    public function test_admin_donation_list_includes_the_zelle_receipt(): void
    {
        Mail::fake();

        $appeal = $this->createDonatableAppeal();

        $this->post(route('donations.zelle'), [
            'campaign_id' => $appeal->campaign_id,
            'is_general' => false,
            'amount' => 5000,
            'appeal_id' => $appeal->id,
            'gift_option' => 1,
            'first_name' => 'Omar',
            'last_name' => 'Donor',
            'email' => 'omar-zelle@example.com',
            'receipt' => UploadedFile::fake()->image('zelle-receipt.jpg'),
        ], [
            'Accept' => 'application/json',
        ])->assertOk();

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->get(route('admin.donations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/donations/donations-index')
                ->where('donations.data.0.payment_method', 'Zelle')
                ->where('donations.data.0.status', 'pending')
                ->where('donations.data.0.receipt_url', fn ($url) => is_string($url) && $url !== '')
            );
    }

    private function createDonatableAppeal(): CampaignAppeal
    {
        $campaign = Campaign::factory()->active()->create();

        return CampaignAppeal::factory()->create([
            'campaign_id' => $campaign->id,
            'slug' => 'thanksgiving-turkey',
            'gift_options' => [
                ['name_en' => 'Medium Turkey', 'name_ar' => 'ديك رومي متوسط', 'amount_cents' => 3000],
                ['name_en' => 'Halal Turkey', 'name_ar' => 'ديك رومي حلال', 'amount_cents' => 5000],
            ],
            'allow_custom_amount' => true,
            'is_active' => true,
        ]);
    }
}
