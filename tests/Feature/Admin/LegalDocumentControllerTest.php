<?php

namespace Tests\Feature\Admin;

use App\Enums\LegalDocumentType;
use App\Models\LegalDocument;
use App\Models\User;
use Database\Seeders\LegalDocumentSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LegalDocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(LegalDocumentSeeder::class);
    }

    private function createAuthorizedUser(): User
    {
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
            'title_ar' => 'الشروط المحدثة',
            'title_en' => 'Updated Terms',
            'body_ar' => '<p>محتوى عربي</p>',
            'body_en' => '<p>English content</p>',
        ], $overrides);
    }

    public function test_guests_cannot_edit_terms(): void
    {
        $this->get(route('admin.legal.terms.edit'))
            ->assertRedirect(route('login'));
    }

    public function test_authorized_user_can_view_terms_edit_page(): void
    {
        $user = $this->createAuthorizedUser();

        $this->actingAs($user)
            ->get(route('admin.legal.terms.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/legal/legal-document-edit')
                ->where('documentType', LegalDocumentType::Terms->value)
            );
    }

    public function test_authorized_user_can_update_terms(): void
    {
        $user = $this->createAuthorizedUser();

        $this->actingAs($user)
            ->patch(route('admin.legal.terms.update'), $this->validPayload())
            ->assertRedirect();

        $document = LegalDocument::query()
            ->where('type', LegalDocumentType::Terms)
            ->first();

        $this->assertSame('Updated Terms', $document->title_en);
        $this->assertSame('<p>محتوى عربي</p>', $document->body_ar);
    }

    public function test_authorized_user_can_update_privacy(): void
    {
        $user = $this->createAuthorizedUser();

        $this->actingAs($user)
            ->patch(route('admin.legal.privacy.update'), $this->validPayload([
                'title_en' => 'Updated Privacy',
            ]))
            ->assertRedirect();

        $document = LegalDocument::query()
            ->where('type', LegalDocumentType::Privacy)
            ->first();

        $this->assertSame('Updated Privacy', $document->title_en);
    }

    public function test_user_without_permission_cannot_update_terms(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('admin.legal.terms.update'), $this->validPayload())
            ->assertForbidden();
    }
}
