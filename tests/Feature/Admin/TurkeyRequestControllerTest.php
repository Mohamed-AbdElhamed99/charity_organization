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
