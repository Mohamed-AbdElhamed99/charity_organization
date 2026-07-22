<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_authorized_user_can_update_a_user_via_put(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $target = User::factory()->staff()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'name' => 'New Name',
                'email' => 'new@example.com',
                'status' => 'active',
                'role' => 'staff',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    public function test_authorized_user_can_update_a_user_via_patch(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $target = User::factory()->staff()->create([
            'name' => 'Patch Name',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $target), [
                'name' => 'Patched Name',
                'email' => $target->email,
                'status' => 'active',
                'role' => 'staff',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => 'Patched Name',
        ]);
    }
}
