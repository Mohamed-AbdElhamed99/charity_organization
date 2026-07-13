<?php

namespace Tests\Feature\Console\Commands;

use App\Enums\BeneficiaryStatus;
use App\Enums\BeneficiaryType;
use App\Models\Beneficiary;
use App\Models\BeneficiaryFamily;
use App\Models\BeneficiaryIndividual;
use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MigrateLegacyBeneficiariesCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $tempDir;

    private string $familiesPath;

    private string $caseSearchesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'legacy_beneficiaries_'.uniqid();
        File::ensureDirectoryExists($this->tempDir);

        $this->familiesPath = $this->tempDir.DIRECTORY_SEPARATOR.'families.sql';
        $this->caseSearchesPath = $this->tempDir.DIRECTORY_SEPARATOR.'case_searches.sql';

        File::put($this->familiesPath, <<<'SQL'
INSERT INTO `families` (`id`, `club_id`, `name`, `email`, `phone`, `national_id`, `social_status`, `family_members`, `address`, `village`, `city`, `state_id`, `country_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 10, 'عائلة أحمد', NULL, '01000000001', '11111111111111', 'متزوجة', 4, 'شارع النصر', 'قرية أ', 'الجيزة', 9999, 65, '2023-01-01 10:00:00', '2023-01-01 10:00:00', NULL),
(2, 10, 'عائلة محذوفة', NULL, '01000000002', '22222222222222', 'أرملة', 2, 'عنوان', NULL, 'القاهرة', 9999, 65, '2023-01-02 10:00:00', '2023-01-02 10:00:00', '2023-01-03 10:00:00'),
(3, 11, 'عائلة مكررة', NULL, '01000000003', '33333333333333', 'مطلقة', 3, 'عنوان 3', NULL, 'الإسكندرية', 9999, 65, '2023-01-04 10:00:00', '2023-01-04 10:00:00', NULL);
SQL);

        File::put($this->caseSearchesPath, <<<'SQL'
INSERT INTO `case_searches` (`id`, `code`, `purpose_id`, `email`, `fullname`, `age`, `national_id`, `address`, `goverment_id`, `city`, `village`, `phone`, `phone_owner`, `current_job`, `job_place`, `insurance_number`, `housing_type`, `housing_space`, `room_numbers`, `possession`, `monthly_rent`, `house_independance`, `wc`, `water_source`, `house_status`, `family_marital_status`, `family_health_status`, `family_needs`, `family_economic_status`, `researcher_opinion`, `insolvency_reason`, `insolvency_date`, `money_required`, `money_paid`, `who_creditor`, `birthdate`, `father`, `mother`, `grandfather`, `grandmother`, `live_with_whom`, `educational_level`, `academic_level`, `gender`, `current_height`, `current_weight`, `shoe_size`, `favorite_colors`, `hobbies`, `favorite_sports`, `club_id`, `case_image`, `images`, `profile_image`, `front_identity_image`, `back_identity_image`, `first_name`, `middle_name`, `last_name`, `completed`, `accepted`, `delivered`, `activity_id`, `user_id`, `created_at`, `updated_at`) VALUES
(10, 'ABC123', 1, NULL, 'محمد علي حسن', '40', '11111111111111', 'عنوان فرد', 9999, 'الجيزة', NULL, '01111111111', NULL, 'عامل', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1985-01-01', NULL, NULL, NULL, NULL, NULL, 'ثانوي', NULL, 'male', NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 0, NULL, NULL, '2023-02-01 10:00:00', '2023-02-01 10:00:00'),
(11, 'DEF456', 1, NULL, 'سارة محمود إبراهيم', '28', '44444444444444', 'عنوان سارة', 9999, 'القاهرة', 'المعادي', '01222222222', NULL, NULL, NULL, NULL, 'إيجار', NULL, NULL, NULL, '1500', NULL, NULL, NULL, NULL, 'متزوجة', 'جيدة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'جامعي', NULL, 'female', NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, 'سارة', 'محمود', 'إبراهيم', 1, 1, 0, NULL, NULL, '2023-02-02 10:00:00', '2023-02-02 10:00:00'),
(12, 'GHI789', 1, NULL, 'فرد بدون قبول', '22', '55555555555555', 'عنوان', 9999, 'القاهرة', NULL, '01333333333', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, '2023-02-03 10:00:00', '2023-02-03 10:00:00');
SQL);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            File::deleteDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    public function test_migrates_families_and_individuals_including_duplicate_national_ids(): void
    {
        $user = User::factory()->create();
        $egypt = Country::query()->create([
            'name' => 'Egypt',
            'iso2' => 'EG',
            'iso3' => 'EGY',
            'phonecode' => '+20',
            'currency' => 'EGP',
            'currency_name' => 'Egyptian Pound',
            'currency_symbol' => 'ج.م',
            'is_active' => true,
        ]);

        $exitCode = Artisan::call('migrate:legacy-beneficiaries', [
            '--user-id' => $user->id,
            '--families' => $this->familiesPath,
            '--case-searches' => $this->caseSearchesPath,
        ]);

        $this->assertSame(0, $exitCode);

        $this->assertSame(2, Beneficiary::query()->families()->count());
        $this->assertSame(3, Beneficiary::query()->individuals()->count());

        $family = BeneficiaryFamily::query()->where('national_id', '11111111111111')->first();
        $this->assertNotNull($family);
        $this->assertSame('عائلة أحمد', $family->household_name);
        $this->assertSame($egypt->id, $family->country_id);
        $this->assertNull($family->state_id);
        $this->assertSame(4, $family->total_members);

        // Soft-deleted legacy family skipped
        $this->assertNull(BeneficiaryFamily::query()->where('national_id', '22222222222222')->first());

        // Duplicate national_id across family + individual is allowed
        $this->assertNotNull(BeneficiaryIndividual::query()->where('national_id', '11111111111111')->first());

        $sara = BeneficiaryIndividual::query()->where('national_id', '44444444444444')->first();
        $this->assertNotNull($sara);
        $this->assertSame('سارة', $sara->first_name);
        $this->assertSame('محمود', $sara->middle_name);
        $this->assertSame('إبراهيم', $sara->last_name);
        $this->assertSame('female', $sara->gender->value);
        $this->assertSame(BeneficiaryStatus::Active, $sara->beneficiary->status);

        $pending = BeneficiaryIndividual::query()->where('national_id', '55555555555555')->first();
        $this->assertNotNull($pending);
        $this->assertSame('فرد', $pending->first_name);
        $this->assertSame('قبول', $pending->last_name);
        $this->assertSame(BeneficiaryStatus::PendingAssessment, $pending->beneficiary->status);
        $this->assertSame(BeneficiaryType::Individual, $pending->beneficiary->type);
    }

    public function test_dry_run_rolls_back_all_inserts(): void
    {
        $user = User::factory()->create();

        $exitCode = Artisan::call('migrate:legacy-beneficiaries', [
            '--user-id' => $user->id,
            '--families' => $this->familiesPath,
            '--case-searches' => $this->caseSearchesPath,
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, Beneficiary::query()->count());
        $this->assertSame(0, BeneficiaryFamily::query()->count());
        $this->assertSame(0, BeneficiaryIndividual::query()->count());
    }

    public function test_include_deleted_imports_soft_deleted_families(): void
    {
        $user = User::factory()->create();

        Artisan::call('migrate:legacy-beneficiaries', [
            '--user-id' => $user->id,
            '--families' => $this->familiesPath,
            '--case-searches' => $this->caseSearchesPath,
            '--only' => 'families',
            '--include-deleted' => true,
        ]);

        $deleted = Beneficiary::withTrashed()
            ->whereHas('family', fn ($q) => $q->where('national_id', '22222222222222'))
            ->first();

        $this->assertNotNull($deleted);
        $this->assertNotNull($deleted->deleted_at);
        $this->assertSame(3, Beneficiary::withTrashed()->families()->count());
    }

    public function test_skips_failed_inserts_and_continues_with_remaining_rows(): void
    {
        $user = User::factory()->create();

        Beneficiary::creating(function (Beneficiary $beneficiary): void {
            if (str_contains((string) $beneficiary->notes, 'case_searches #11')) {
                throw new \RuntimeException('Simulated insert failure');
            }
        });

        $exitCode = Artisan::call('migrate:legacy-beneficiaries', [
            '--user-id' => $user->id,
            '--families' => $this->familiesPath,
            '--case-searches' => $this->caseSearchesPath,
            '--only' => 'individuals',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertNull(BeneficiaryIndividual::query()->where('national_id', '44444444444444')->first());
        $this->assertNotNull(BeneficiaryIndividual::query()->where('national_id', '55555555555555')->first());
        $this->assertSame(2, Beneficiary::query()->individuals()->count());
        $this->assertStringContainsString('skipped_failed', Artisan::output());
    }
}
