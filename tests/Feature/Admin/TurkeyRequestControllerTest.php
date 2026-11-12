<?php

namespace Tests\Feature\Admin;

use App\Enums\TurkeyRequestStatus;
use App\Models\BeneficiarySupport;
use App\Models\TurkeyRequest;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TurkeyRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        config(['turkey_campaign.total' => 5]);
    }

    private function createUserWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_guests_cannot_access_turkey_requests(): void
    {
        $this->get(route('admin.turkey-requests.index'))->assertNotFound();
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $user = $this->createUserWithRole('field_worker');
        $request = TurkeyRequest::factory()->create();

        $this->actingAs($user)->get(route('admin.turkey-requests.index'))->assertForbidden();
        $this->actingAs($user)->patch(route('admin.turkey-requests.cancel', $request))->assertForbidden();
    }

    public function test_staff_can_view_requests_with_stats(): void
    {
        $user = $this->createUserWithRole('staff');
        TurkeyRequest::factory()->count(2)->create();
        TurkeyRequest::factory()->delivered()->create();

        $this->actingAs($user)
            ->get(route('admin.turkey-requests.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/turkey-requests/turkey-requests-index')
                ->has('requests', 3)
                ->where('stats.remaining', 2)
                ->where('stats.pending', 2)
                ->where('stats.delivered', 1)
            );
    }

    public function test_cancelling_a_pending_request_returns_the_turkey_to_stock(): void
    {
        $user = $this->createUserWithRole('staff');
        $request = TurkeyRequest::factory()->create();

        $this->actingAs($user)->patch(route('admin.turkey-requests.cancel', $request))->assertRedirect();

        $this->assertSame(TurkeyRequestStatus::Cancelled, $request->refresh()->status);
        $this->actingAs($user)
            ->get(route('admin.turkey-requests.index'))
            ->assertInertia(fn (Assert $page) => $page->where('stats.remaining', 5));
    }

    public function test_delivered_request_cannot_be_cancelled(): void
    {
        $user = $this->createUserWithRole('staff');
        $request = TurkeyRequest::factory()->delivered()->create();

        $this->actingAs($user)->patch(route('admin.turkey-requests.cancel', $request))->assertRedirect();

        $this->assertSame(TurkeyRequestStatus::Delivered, $request->refresh()->status);
    }

    /**
     * @return array<string, mixed>
     */
    private function updatePayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Ashraf Mansour',
            'email' => 'ashraf@example.com',
            'phone' => '5551112222',
            'address' => '5 Nile Street, Cairo',
            'quantity' => 3,
        ], $overrides);
    }

    public function test_staff_can_edit_details_and_quantity_and_beneficiary_profile_is_synced(): void
    {
        $user = $this->createUserWithRole('staff');
        $request = TurkeyRequest::factory()->create();

        $this->actingAs($user)
            ->patch(route('admin.turkey-requests.update', $request), $this->updatePayload())
            ->assertSessionHasNoErrors();

        $request->refresh();
        $this->assertSame(3, $request->quantity);
        $this->assertSame('ashraf@example.com', $request->email);

        $individual = $request->beneficiary->individual;
        $this->assertSame('Ashraf', $individual->first_name);
        $this->assertSame('Mansour', $individual->last_name);
        $this->assertSame('5551112222', $individual->phone);
        $this->assertSame('5 Nile Street, Cairo', $individual->address);

        $this->actingAs($user)
            ->get(route('admin.turkey-requests.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.remaining', 2)
                ->where('stats.pending', 3)
            );
    }

    public function test_quantity_above_available_stock_is_rejected(): void
    {
        $user = $this->createUserWithRole('staff');
        TurkeyRequest::factory()->create(['quantity' => 3]);
        $request = TurkeyRequest::factory()->create();

        $this->actingAs($user)
            ->patch(route('admin.turkey-requests.update', $request), $this->updatePayload(['quantity' => 3]))
            ->assertSessionHasErrors('quantity');

        $this->assertSame(1, $request->refresh()->quantity);
    }

    public function test_quantity_cannot_change_on_a_delivered_request_but_details_can(): void
    {
        $user = $this->createUserWithRole('staff');
        $request = TurkeyRequest::factory()->delivered()->create();

        $this->actingAs($user)
            ->patch(route('admin.turkey-requests.update', $request), $this->updatePayload(['quantity' => 2]))
            ->assertSessionHasErrors('quantity');

        $this->actingAs($user)
            ->patch(route('admin.turkey-requests.update', $request), $this->updatePayload(['quantity' => 1]))
            ->assertSessionHasNoErrors();

        $this->assertSame('Ashraf Mansour', $request->refresh()->full_name);
    }

    public function test_update_rejects_phone_or_email_used_by_another_active_request(): void
    {
        $user = $this->createUserWithRole('staff');
        $other = TurkeyRequest::factory()->create();
        $request = TurkeyRequest::factory()->create();

        $this->actingAs($user)
            ->patch(route('admin.turkey-requests.update', $request), $this->updatePayload([
                'email' => $other->email,
                'phone' => $other->phone,
            ]))
            ->assertSessionHasErrors(['email', 'phone']);
    }

    public function test_update_requires_manage_permission(): void
    {
        $user = $this->createUserWithRole('field_worker');
        $request = TurkeyRequest::factory()->create();

        $this->actingAs($user)
            ->patch(route('admin.turkey-requests.update', $request), $this->updatePayload())
            ->assertForbidden();
    }

    public function test_delivery_support_note_includes_the_quantity(): void
    {
        $user = $this->createUserWithRole('staff');
        $request = TurkeyRequest::factory()->create(['quantity' => 3]);

        $this->actingAs($user)->patch(route('admin.turkey-requests.mark-delivered', $request));

        $this->assertStringContainsString('x3', BeneficiarySupport::query()->firstOrFail()->notes);
    }

    public function test_staff_can_manually_mark_a_request_delivered(): void
    {
        $user = $this->createUserWithRole('staff');
        $request = TurkeyRequest::factory()->create();

        $this->actingAs($user)->patch(route('admin.turkey-requests.mark-delivered', $request))->assertRedirect();

        $request->refresh();
        $this->assertSame(TurkeyRequestStatus::Delivered, $request->status);
        $this->assertSame(1, BeneficiarySupport::query()->where('beneficiary_id', $request->beneficiary_id)->count());
    }
}
