<?php

namespace Tests\Feature\Admin;

use App\Enums\BeneficiaryStatus;
use App\Enums\BeneficiaryType;
use App\Models\Beneficiary;
use App\Models\BeneficiaryUserAccess;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BeneficiaryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function createSuperAdmin(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    private function createStaffWithoutGrant(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('staff');

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function individualPayload(array $overrides = []): array
    {
        return array_merge([
            'type' => BeneficiaryType::Individual->value,
            'status' => BeneficiaryStatus::PendingAssessment->value,
            'notes' => 'Internal note',
            'individual' => [
                'subtype' => 'adult',
                'first_name' => 'Ahmed',
                'middle_name' => null,
                'last_name' => 'Hassan',
                'gender' => 'male',
                'birthdate' => '1990-01-15',
                'national_id' => '29901151234567',
                'phone' => '01001234567',
                'address' => 'Cairo',
            ],
        ], $overrides);
    }

    public function test_authorized_user_can_view_beneficiaries_index(): void
    {
        $user = $this->createSuperAdmin();
        Beneficiary::factory()->individual()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->get(route('admin.beneficiaries.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/beneficiaries/beneficiaries-index')
                ->has('beneficiaries.data', 1)
            );
    }

    public function test_user_without_permission_cannot_view_beneficiaries_index(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('donor');

        $this->actingAs($user)
            ->get(route('admin.beneficiaries.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_create_individual_beneficiary_atomically(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAs($user)
            ->post(route('admin.beneficiaries.store'), $this->individualPayload())
            ->assertRedirect();

        $beneficiary = Beneficiary::query()->first();

        $this->assertNotNull($beneficiary);
        $this->assertSame(BeneficiaryType::Individual, $beneficiary->type);
        $this->assertDatabaseHas('beneficiary_individuals', [
            'beneficiary_id' => $beneficiary->id,
            'first_name' => 'Ahmed',
            'last_name' => 'Hassan',
        ]);
    }

    public function test_super_admin_can_create_family_beneficiary_with_members(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAs($user)
            ->post(route('admin.beneficiaries.store'), [
                'type' => BeneficiaryType::Family->value,
                'family' => [
                    'household_name' => 'Al-Sayyid Family',
                    'phone' => '01009998877',
                    'members' => [
                        [
                            'subtype' => 'adult',
                            'first_name' => 'Fatima',
                            'last_name' => 'Al-Sayyid',
                            'relation' => 'Head',
                        ],
                        [
                            'subtype' => 'child',
                            'first_name' => 'Omar',
                            'last_name' => 'Al-Sayyid',
                            'relation' => 'Son',
                            'school_year' => 'Grade 3',
                        ],
                    ],
                ],
            ])
            ->assertRedirect();

        $beneficiary = Beneficiary::query()->first();

        $this->assertSame(BeneficiaryType::Family, $beneficiary->type);
        $this->assertDatabaseHas('beneficiary_families', [
            'beneficiary_id' => $beneficiary->id,
            'household_name' => 'Al-Sayyid Family',
        ]);
        $this->assertDatabaseCount('beneficiary_family_members', 2);
    }

    public function test_super_admin_can_create_organization_beneficiary(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAs($user)
            ->post(route('admin.beneficiaries.store'), [
                'type' => BeneficiaryType::Organization->value,
                'organization' => [
                    'name' => 'Kidney Care Center',
                    'phone' => '0223456789',
                    'contact_person' => 'Dr. Samir',
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('beneficiary_organizations', [
            'name' => 'Kidney Care Center',
        ]);
    }

    public function test_type_cannot_be_changed_on_update(): void
    {
        $user = $this->createSuperAdmin();
        $beneficiary = Beneficiary::factory()->individual()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->put(route('admin.beneficiaries.update', $beneficiary), [
                'type' => BeneficiaryType::Family->value,
                'individual' => [
                    'subtype' => 'adult',
                    'first_name' => 'Updated',
                    'last_name' => 'Name',
                ],
            ])
            ->assertSessionHasErrors('type');
    }

    public function test_staff_without_grant_sees_masked_sensitive_fields_on_show(): void
    {
        $admin = $this->createSuperAdmin();
        $staff = $this->createStaffWithoutGrant();

        $beneficiary = Beneficiary::factory()->individual()->create(['created_by' => $admin->id]);
        $beneficiary->individual->update(['phone' => '01001234567']);

        $this->actingAs($staff)
            ->get(route('admin.beneficiaries.show', $beneficiary))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('beneficiary.can_view_sensitive', false)
                ->where('beneficiary.primary_contact', '••••••')
            );
    }

    public function test_staff_with_active_grant_can_view_allowed_fields(): void
    {
        $admin = $this->createSuperAdmin();
        $staff = $this->createStaffWithoutGrant();

        $beneficiary = Beneficiary::factory()->individual()->create(['created_by' => $admin->id]);
        $beneficiary->individual->update(['phone' => '01001234567']);

        BeneficiaryUserAccess::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'user_id' => $staff->id,
            'granted_by' => $admin->id,
            'allowed_fields' => ['phone', 'first_name'],
            'expires_in_seconds' => null,
            'granted_at' => now(),
        ]);

        $this->actingAs($staff)
            ->get(route('admin.beneficiaries.show', $beneficiary))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('beneficiary.can_view_sensitive', true)
                ->where('beneficiary.primary_contact', '01001234567')
            );
    }

    public function test_super_admin_can_create_assessment_for_beneficiary(): void
    {
        $user = $this->createSuperAdmin();
        $beneficiary = Beneficiary::factory()->individual()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->post(route('admin.beneficiaries.assessments.store', $beneficiary), [
                'assessment_date' => now()->toDateString(),
                'purpose' => 'Medical support',
                'researcher_opinion' => 'Urgent case',
                'recommended_aid_amount' => 1500,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('beneficiary_assessments', [
            'beneficiary_id' => $beneficiary->id,
            'purpose' => 'Medical support',
            'assessed_by' => $user->id,
        ]);
    }

    public function test_invalid_individual_payload_does_not_create_partial_beneficiary(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAs($user)
            ->post(route('admin.beneficiaries.store'), [
                'type' => BeneficiaryType::Individual->value,
                'individual' => [
                    'subtype' => 'adult',
                ],
            ])
            ->assertSessionHasErrors(['individual.first_name', 'individual.last_name']);

        $this->assertDatabaseCount('beneficiaries', 0);
        $this->assertDatabaseCount('beneficiary_individuals', 0);
    }

    public function test_authorized_user_can_bulk_delete_beneficiaries(): void
    {
        $user = $this->createSuperAdmin();
        $beneficiaries = Beneficiary::factory()
            ->count(3)
            ->individual()
            ->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->post(route('admin.beneficiaries.bulk-destroy'), [
                'ids' => $beneficiaries->pluck('id')->all(),
            ])
            ->assertRedirect();

        foreach ($beneficiaries as $beneficiary) {
            $this->assertSoftDeleted('beneficiaries', ['id' => $beneficiary->id]);
        }
    }

    public function test_user_without_permission_cannot_bulk_delete_beneficiaries(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('donor');
        $beneficiary = Beneficiary::factory()->individual()->create();

        $this->actingAs($user)
            ->post(route('admin.beneficiaries.bulk-destroy'), [
                'ids' => [$beneficiary->id],
            ])
            ->assertForbidden();

        $this->assertNull($beneficiary->fresh()->deleted_at);
    }

    public function test_index_includes_national_id_and_address_for_authorized_user(): void
    {
        $user = $this->createSuperAdmin();
        $beneficiary = Beneficiary::factory()->individual()->create(['created_by' => $user->id]);
        $beneficiary->individual->update([
            'national_id' => '29901151234567',
            'address' => '12 Nile Street',
        ]);

        $this->actingAs($user)
            ->get(route('admin.beneficiaries.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/beneficiaries/beneficiaries-index')
                ->where('beneficiaries.data.0.national_id', '29901151234567')
                ->where('beneficiaries.data.0.address', '12 Nile Street')
                ->has('geoOptions.countries')
                ->has('geoOptions.states')
            );
    }

    public function test_index_masks_national_id_and_address_without_sensitive_access(): void
    {
        $admin = $this->createSuperAdmin();
        $staff = $this->createStaffWithoutGrant();
        $beneficiary = Beneficiary::factory()->individual()->create(['created_by' => $admin->id]);
        $beneficiary->individual->update([
            'national_id' => '29901151234567',
            'address' => '12 Nile Street',
        ]);

        $this->actingAs($staff)
            ->get(route('admin.beneficiaries.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('beneficiaries.data.0.national_id', '••••••')
                ->where('beneficiaries.data.0.address', '••••••')
            );
    }

    public function test_index_can_filter_by_country_and_state(): void
    {
        $user = $this->createSuperAdmin();

        $egypt = Country::query()->create([
            'name' => 'Egypt',
            'iso2' => 'EG',
            'is_active' => true,
        ]);
        $cairo = State::query()->create([
            'name' => 'Cairo',
            'country_id' => $egypt->id,
        ]);
        $giza = State::query()->create([
            'name' => 'Giza',
            'country_id' => $egypt->id,
        ]);

        $usa = Country::query()->create([
            'name' => 'United States',
            'iso2' => 'US',
            'is_active' => true,
        ]);
        $california = State::query()->create([
            'name' => 'California',
            'country_id' => $usa->id,
        ]);

        $matched = Beneficiary::factory()->individual()->create(['created_by' => $user->id]);
        $matched->individual->update([
            'country_id' => $egypt->id,
            'state_id' => $cairo->id,
        ]);

        $otherState = Beneficiary::factory()->individual()->create(['created_by' => $user->id]);
        $otherState->individual->update([
            'country_id' => $egypt->id,
            'state_id' => $giza->id,
        ]);

        $otherCountry = Beneficiary::factory()->individual()->create(['created_by' => $user->id]);
        $otherCountry->individual->update([
            'country_id' => $usa->id,
            'state_id' => $california->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.beneficiaries.index', [
                'country_id' => [$egypt->id],
                'state_id' => [$cairo->id],
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('beneficiaries.data', 1)
                ->where('beneficiaries.data.0.id', $matched->id)
            );
    }

    public function test_index_query_searches_profile_address(): void
    {
        $user = $this->createSuperAdmin();

        $matched = Beneficiary::factory()->individual()->create(['created_by' => $user->id]);
        $matched->individual->update(['address' => 'Unique Nile Corniche Address']);

        $other = Beneficiary::factory()->individual()->create(['created_by' => $user->id]);
        $other->individual->update(['address' => 'Downtown Street']);

        $this->actingAs($user)
            ->get(route('admin.beneficiaries.index', ['query' => 'Nile Corniche']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('beneficiaries.data', 1)
                ->where('beneficiaries.data.0.id', $matched->id)
            );
    }
}
