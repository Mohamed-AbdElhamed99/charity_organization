<?php

namespace Tests\Feature\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_index_honors_per_page_query_parameter(): void
    {
        $user = User::factory()->create();
        User::factory()->count(14)->create();

        $this->actingAs($user)
            ->get(route('admin.users.index', ['per_page' => 10]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/users-index')
                ->where('users.per_page', 10)
                ->where('users.last_page', 2)
                ->where('search.per_page', '10'),
            );
    }

    public function test_users_index_filters_by_status_and_paginates_filtered_results(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        User::factory()->count(5)->create(['status' => UserStatus::Active]);
        User::factory()->count(10)->create(['status' => UserStatus::Inactive]);

        $this->actingAs($user)
            ->get(route('admin.users.index', [
                'status' => ['active'],
                'per_page' => 25,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/users-index')
                ->where('users.total', 6)
                ->where('users.last_page', 1)
                ->where('search.status', ['active']),
            );
    }

    public function test_users_index_filters_by_query(): void
    {
        $user = User::factory()->create(['name' => 'Unique Searchable User']);
        User::factory()->create(['name' => 'Someone Else']);

        $this->actingAs($user)
            ->get(route('admin.users.index', ['query' => 'Unique Searchable']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/users-index')
                ->where('users.total', 1)
                ->where('users.data.0.name', 'Unique Searchable User')
                ->where('search.query', 'Unique Searchable'),
            );
    }

    public function test_users_index_preserves_filters_in_search_props(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.users.index', [
                'query' => 'john',
                'page' => 2,
                'per_page' => 30,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/users-index')
                ->where('search.query', 'john')
                ->where('search.page', '2')
                ->where('search.per_page', '30'),
            );
    }
}
