<?php

namespace Tests\Feature\Admin;

use App\Models\Faq;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FaqControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthorizedUser(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'question_ar' => 'ما هي رسالتكم؟',
            'question_en' => 'What is your mission?',
            'answer_ar' => 'نساعد المحتاجين.',
            'answer_en' => 'We help those in need.',
            'sort_order' => 1,
            'is_published' => true,
        ], $overrides);
    }

    public function test_guests_cannot_access_faqs_index(): void
    {
        $this->get(route('admin.faqs.index'))
            ->assertNotFound();
    }

    public function test_authorized_user_can_view_faqs_index(): void
    {
        $user = $this->createAuthorizedUser();
        Faq::factory()->count(3)->create();

        $this->actingAs($user)
            ->get(route('admin.faqs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/faqs/faqs-index')
                ->has('faqs.data', 3)
            );
    }

    public function test_faqs_index_honors_search(): void
    {
        $user = $this->createAuthorizedUser();
        Faq::factory()->create(['question_en' => 'Special Question']);
        Faq::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('admin.faqs.index', ['query' => 'Special']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('faqs.total', 1)
                ->where('search.query', 'Special')
            );
    }

    public function test_authorized_user_can_create_faq(): void
    {
        $user = $this->createAuthorizedUser();

        $this->actingAs($user)
            ->post(route('admin.faqs.store'), $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('faqs', [
            'question_en' => 'What is your mission?',
            'question_ar' => 'ما هي رسالتكم؟',
            'is_published' => true,
        ]);
    }

    public function test_user_without_permission_cannot_create_faq(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.faqs.store'), $this->validPayload())
            ->assertForbidden();
    }

    public function test_authorized_user_can_update_faq(): void
    {
        $user = $this->createAuthorizedUser();
        $faq = Faq::factory()->create();

        $this->actingAs($user)
            ->patch(route('admin.faqs.update', $faq), $this->validPayload([
                'question_en' => 'Updated question',
            ]))
            ->assertRedirect();

        $this->assertSame('Updated question', $faq->fresh()->question_en);
    }

    public function test_authorized_user_can_soft_delete_faq(): void
    {
        $user = $this->createAuthorizedUser();
        $faq = Faq::factory()->create();

        $this->actingAs($user)
            ->delete(route('admin.faqs.destroy', $faq))
            ->assertRedirect();

        $this->assertSoftDeleted('faqs', ['id' => $faq->id]);
    }

    public function test_authorized_user_can_restore_faq(): void
    {
        $user = $this->createAuthorizedUser();
        $faq = Faq::factory()->create();
        $faq->delete();

        $this->actingAs($user)
            ->post(route('admin.faqs.restore', $faq->id))
            ->assertRedirect();

        $this->assertNull($faq->fresh()->deleted_at);
    }

    public function test_authorized_user_can_bulk_delete_faqs(): void
    {
        $user = $this->createAuthorizedUser();
        $faqs = Faq::factory()->count(3)->create();

        $this->actingAs($user)
            ->post(route('admin.faqs.bulk-destroy'), [
                'ids' => $faqs->pluck('id')->all(),
            ])
            ->assertRedirect();

        foreach ($faqs as $faq) {
            $this->assertSoftDeleted('faqs', ['id' => $faq->id]);
        }
    }
}
