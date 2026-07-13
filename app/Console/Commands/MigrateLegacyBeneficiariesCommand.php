<?php

namespace App\Console\Commands;

use App\Enums\BeneficiaryStatus;
use App\Enums\BeneficiaryType;
use App\Enums\IndividualSubtype;
use App\Enums\UserGender;
use App\Models\Beneficiary;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Imports legacy `families` and `case_searches` SQL dumps into the new
 * beneficiaries schema.
 *
 *   Source                          -> Target
 *   --------------------------------------------------------------
 *   families                        -> beneficiaries (type=family)
 *                                      + beneficiary_families
 *   case_searches                   -> beneficiaries (type=individual)
 *                                      + beneficiary_individuals
 *
 * Duplicate national_id values are allowed; admins clean them up manually.
 *
 * Legacy country/state IDs do not match the new geo seeders; unknown FKs
 * are stored as NULL. Egypt (legacy country_id 65) is mapped by iso2=EG
 * when that country exists.
 */
#[Signature('migrate:legacy-beneficiaries
    {--user-id= : Staff user id written to beneficiaries.created_by (defaults to first user)}
    {--families= : Path to families.sql (default: database/data/families.sql)}
    {--case-searches= : Path to case_searches.sql (default: database/data/case_searches.sql)}
    {--only=all : Import scope: all|families|individuals}
    {--include-deleted : Also import soft-deleted legacy families}
    {--dry-run : Run inside a transaction and roll back at the end}')]
#[Description('Migrate legacy families and case_searches SQL dumps into the beneficiaries model')]
class MigrateLegacyBeneficiariesCommand extends Command
{
    /** @var array<int, true> */
    private array $validCountryIds = [];

    /** @var array<int, true> */
    private array $validStateIds = [];

    private ?int $egyptCountryId = null;

    private string $codePrefix = '';

    private int $codeSequence = 0;

    /** @var array<string, int> */
    private array $stats = [];

    /** @var list<string> */
    private array $failures = [];

    public function handle(): int
    {
        $only = strtolower((string) $this->option('only'));
        if (! in_array($only, ['all', 'families', 'individuals'], true)) {
            $this->error('Invalid --only value. Use all, families, or individuals.');

            return self::FAILURE;
        }

        $userId = $this->resolveCreatorId();
        if ($userId === null) {
            return self::FAILURE;
        }

        $familiesPath = $this->resolvePath(
            $this->option('families'),
            database_path('data/families.sql')
        );
        $caseSearchesPath = $this->resolvePath(
            $this->option('case-searches'),
            database_path('data/case_searches.sql')
        );

        if (in_array($only, ['all', 'families'], true) && ! is_readable($familiesPath)) {
            $this->error("Families SQL file not readable: {$familiesPath}");

            return self::FAILURE;
        }

        if (in_array($only, ['all', 'individuals'], true) && ! is_readable($caseSearchesPath)) {
            $this->error("Case searches SQL file not readable: {$caseSearchesPath}");

            return self::FAILURE;
        }

        $this->stats = [
            'families_created' => 0,
            'individuals_created' => 0,
            'skipped_deleted' => 0,
            'skipped_invalid' => 0,
            'skipped_failed' => 0,
        ];
        $this->failures = [];

        $this->bootLookups();
        $this->bootCodeSequence();

        $dryRun = (bool) $this->option('dry-run');
        $this->info('Migrating legacy beneficiaries'
            .($dryRun ? ' (DRY RUN — will roll back)' : '')
            ." as user #{$userId}...");

        // Outer transaction is only for --dry-run rollback of successful rows.
        // Each record uses its own nested transaction/savepoint so one failure
        // does not abort the rest of the import.
        if ($dryRun) {
            DB::beginTransaction();
        }

        try {
            if (in_array($only, ['all', 'families'], true)) {
                $this->migrateFamilies($familiesPath, $userId);
            }

            if (in_array($only, ['all', 'individuals'], true)) {
                $this->migrateCaseSearches($caseSearchesPath, $userId);
            }

            if ($dryRun) {
                DB::rollBack();
                $this->warn('Dry run complete — all changes rolled back.');
            } else {
                $this->info('Migration finished.');
            }
        } catch (Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->error('Migration aborted: '.$e->getMessage());
            $this->line($e->getFile().':'.$e->getLine());

            return self::FAILURE;
        }

        $this->printSummary();

        return self::SUCCESS;
    }

    private function resolveCreatorId(): ?int
    {
        $option = $this->option('user-id');

        if ($option !== null && $option !== '') {
            $user = User::query()->find((int) $option);
            if (! $user) {
                $this->error("User #{$option} does not exist.");

                return null;
            }

            return (int) $user->id;
        }

        $userId = User::query()->orderBy('id')->value('id');
        if ($userId === null) {
            $this->error('No users found. Pass --user-id= or seed a user first.');

            return null;
        }

        return (int) $userId;
    }

    private function resolvePath(mixed $option, string $default): string
    {
        $path = is_string($option) && $option !== '' ? $option : $default;

        if (! Str::startsWith($path, ['/', '\\']) && ! preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)) {
            $path = base_path($path);
        }

        return $path;
    }

    private function bootLookups(): void
    {
        $this->validCountryIds = Country::query()->pluck('id')->flip()->all();
        $this->validStateIds = State::query()->pluck('id')->flip()->all();
        $this->egyptCountryId = Country::query()->where('iso2', 'EG')->value('id');
    }

    private function bootCodeSequence(): void
    {
        $year = now()->year;
        $this->codePrefix = "BEN-{$year}-";

        $lastCode = Beneficiary::withTrashed()
            ->where('code', 'like', "{$this->codePrefix}%")
            ->orderByDesc('code')
            ->value('code');

        $this->codeSequence = 0;
        if ($lastCode !== null && preg_match('/-(\d+)$/', $lastCode, $matches)) {
            $this->codeSequence = (int) $matches[1];
        }
    }

    private function migrateFamilies(string $path, int $userId): void
    {
        $rows = $this->parseSqlInsertFile($path, 'families');
        $this->line('Families rows parsed: '.count($rows));

        $includeDeleted = (bool) $this->option('include-deleted');
        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach ($rows as $row) {
            $bar->advance();

            if (! empty($row['deleted_at']) && ! $includeDeleted) {
                $this->stats['skipped_deleted']++;

                continue;
            }

            $nationalId = $this->normalizeNationalId($row['national_id'] ?? null);

            $householdName = $this->nullableString($row['name'] ?? null);
            if ($householdName === null) {
                $this->stats['skipped_invalid']++;

                continue;
            }

            try {
                DB::transaction(function () use ($row, $userId, $nationalId, $householdName, $includeDeleted): void {
                    $beneficiary = Beneficiary::query()->create([
                        'type' => BeneficiaryType::Family,
                        'code' => $this->nextCode(),
                        'status' => BeneficiaryStatus::Active,
                        'notes' => $this->familyNotes($row),
                        'created_by' => $userId,
                        'created_at' => $row['created_at'] ?? now(),
                        'updated_at' => $row['updated_at'] ?? now(),
                        'deleted_at' => $includeDeleted ? ($row['deleted_at'] ?? null) : null,
                    ]);

                    $beneficiary->family()->create([
                        'household_name' => $householdName,
                        'national_id' => $nationalId,
                        'phone' => $this->nullableString($row['phone'] ?? null),
                        'address' => $this->composeAddress(
                            $row['address'] ?? null,
                            $row['city'] ?? null,
                            $row['village'] ?? null,
                        ),
                        'village' => $this->nullableString($row['village'] ?? null),
                        'country_id' => $this->resolveCountryId($row['country_id'] ?? null),
                        'state_id' => $this->resolveStateId($row['state_id'] ?? null),
                        'social_status' => $this->nullableString($row['social_status'] ?? null),
                        'total_members' => max(1, (int) ($row['family_members'] ?? 1)),
                        'notes' => $this->nullableString($row['email'] ?? null)
                            ? 'Legacy email: '.$row['email']
                            : null,
                        'created_at' => $row['created_at'] ?? now(),
                        'updated_at' => $row['updated_at'] ?? now(),
                    ]);
                });
            } catch (Throwable $e) {
                $this->recordFailure('family', $row['id'] ?? null, $e);

                continue;
            }

            $this->stats['families_created']++;
        }

        $bar->finish();
        $this->newLine();
    }

    private function migrateCaseSearches(string $path, int $userId): void
    {
        $rows = $this->parseSqlInsertFile($path, 'case_searches');
        $this->line('Case search rows parsed: '.count($rows));

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach ($rows as $row) {
            $bar->advance();

            $nationalId = $this->normalizeNationalId($row['national_id'] ?? null);

            $names = $this->resolveIndividualNames($row);
            if ($names['first_name'] === null || $names['last_name'] === null) {
                $this->stats['skipped_invalid']++;

                continue;
            }

            $accepted = (int) ($row['accepted'] ?? 0);
            $status = $accepted !== 0
                ? BeneficiaryStatus::Active
                : BeneficiaryStatus::PendingAssessment;

            try {
                DB::transaction(function () use ($row, $userId, $nationalId, $names, $status): void {
                    $beneficiary = Beneficiary::query()->create([
                        'type' => BeneficiaryType::Individual,
                        'code' => $this->nextCode(),
                        'status' => $status,
                        'notes' => $this->caseSearchNotes($row),
                        'created_by' => $userId,
                        'created_at' => $row['created_at'] ?? now(),
                        'updated_at' => $row['updated_at'] ?? now(),
                    ]);

                    $beneficiary->individual()->create([
                        'subtype' => IndividualSubtype::Adult,
                        'first_name' => $names['first_name'],
                        'middle_name' => $names['middle_name'],
                        'last_name' => $names['last_name'],
                        'gender' => $this->mapGender($row['gender'] ?? null),
                        'birthdate' => $this->nullableDate($row['birthdate'] ?? null),
                        'national_id' => $nationalId,
                        'phone' => $this->nullableString($row['phone'] ?? null),
                        'address' => $this->composeAddress(
                            $row['address'] ?? null,
                            $row['city'] ?? null,
                            $row['village'] ?? null,
                        ),
                        'country_id' => null,
                        'state_id' => $this->resolveStateId($row['goverment_id'] ?? null),
                        'health_status' => $this->nullableString($row['family_health_status'] ?? null),
                        'education_level' => $this->nullableString(
                            $row['educational_level'] ?? $row['academic_level'] ?? null
                        ),
                        'marital_status' => $this->nullableString($row['family_marital_status'] ?? null),
                        'employment_status' => $this->nullableString($row['current_job'] ?? null),
                        'notes' => $this->individualProfileNotes($row),
                        'created_at' => $row['created_at'] ?? now(),
                        'updated_at' => $row['updated_at'] ?? now(),
                    ]);
                });
            } catch (Throwable $e) {
                $this->recordFailure('case_search', $row['id'] ?? null, $e);

                continue;
            }

            $this->stats['individuals_created']++;
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseSqlInsertFile(string $path, string $table): array
    {
        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new \RuntimeException("Unable to read SQL file: {$path}");
        }

        // Normalize line endings; dumps may contain multiple INSERT batches.
        if (! preg_match_all(
            '/INSERT\s+INTO\s+`'.preg_quote($table, '/').'`\s*\(([^)]+)\)\s*VALUES\s*(.*?);/is',
            $sql,
            $matches,
            PREG_SET_ORDER
        )) {
            throw new \RuntimeException("No INSERT INTO `{$table}` statements found in {$path}");
        }

        $rows = [];
        foreach ($matches as $match) {
            $columns = array_map(
                fn (string $column): string => trim($column, " `\t\n\r"),
                explode(',', $match[1])
            );

            foreach ($this->parseValueTuples($match[2]) as $values) {
                if (count($values) !== count($columns)) {
                    // Skip malformed tuples rather than aborting the whole import.
                    $this->stats['skipped_invalid'] = ($this->stats['skipped_invalid'] ?? 0) + 1;

                    continue;
                }

                $rows[] = array_combine($columns, $values);
            }
        }

        return $rows;
    }

    /**
     * @return list<list<mixed>>
     */
    private function parseValueTuples(string $valuesSql): array
    {
        $rows = [];
        $len = strlen($valuesSql);
        $i = 0;

        while ($i < $len) {
            while ($i < $len && $valuesSql[$i] !== '(') {
                $i++;
            }

            if ($i >= $len) {
                break;
            }

            $i++; // skip '('
            $fields = [];
            $current = '';
            $inString = false;

            while ($i < $len) {
                $ch = $valuesSql[$i];

                if ($inString) {
                    if ($ch === '\\' && $i + 1 < $len) {
                        $next = $valuesSql[$i + 1];
                        $current .= match ($next) {
                            'n' => "\n",
                            'r' => "\r",
                            't' => "\t",
                            '0' => "\0",
                            'Z' => chr(26),
                            default => $next,
                        };
                        $i += 2;

                        continue;
                    }

                    if ($ch === "'") {
                        if ($i + 1 < $len && $valuesSql[$i + 1] === "'") {
                            $current .= "'";
                            $i += 2;

                            continue;
                        }

                        $inString = false;
                        $i++;

                        continue;
                    }

                    $current .= $ch;
                    $i++;

                    continue;
                }

                if ($ch === "'") {
                    $inString = true;
                    $current = '';
                    $i++;

                    continue;
                }

                if ($ch === ',') {
                    $fields[] = $this->normalizeSqlLiteral($current);
                    $current = '';
                    $i++;

                    continue;
                }

                if ($ch === ')') {
                    $fields[] = $this->normalizeSqlLiteral($current);
                    $rows[] = $fields;
                    $i++;

                    break;
                }

                if (! ctype_space($ch)) {
                    $current .= $ch;
                }

                $i++;
            }
        }

        return $rows;
    }

    private function normalizeSqlLiteral(string $raw): mixed
    {
        $raw = trim($raw);

        if ($raw === '' || strtoupper($raw) === 'NULL') {
            return null;
        }

        return $raw;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{first_name: ?string, middle_name: ?string, last_name: ?string}
     */
    private function resolveIndividualNames(array $row): array
    {
        $first = $this->nullableString($row['first_name'] ?? null);
        $middle = $this->nullableString($row['middle_name'] ?? null);
        $last = $this->nullableString($row['last_name'] ?? null);

        if ($first !== null && $last !== null) {
            return [
                'first_name' => $first,
                'middle_name' => $middle,
                'last_name' => $last,
            ];
        }

        $fullname = $this->nullableString($row['fullname'] ?? null);
        if ($fullname === null) {
            return [
                'first_name' => $first,
                'middle_name' => $middle,
                'last_name' => $last,
            ];
        }

        $parts = preg_split('/\s+/u', $fullname, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($parts === []) {
            return [
                'first_name' => $first,
                'middle_name' => $middle,
                'last_name' => $last,
            ];
        }

        if (count($parts) === 1) {
            return [
                'first_name' => $first ?? $parts[0],
                'middle_name' => $middle,
                'last_name' => $last ?? $parts[0],
            ];
        }

        return [
            'first_name' => $first ?? $parts[0],
            'middle_name' => $middle ?? (count($parts) > 2 ? implode(' ', array_slice($parts, 1, -1)) : null),
            'last_name' => $last ?? $parts[array_key_last($parts)],
        ];
    }

    private function mapGender(mixed $value): ?UserGender
    {
        $gender = $this->nullableString($value);
        if ($gender === null) {
            return null;
        }

        $normalized = Str::lower($gender);

        return match (true) {
            in_array($normalized, ['male', 'm', 'ذكر', 'رجل'], true) => UserGender::Male,
            in_array($normalized, ['female', 'f', 'أنثى', 'انثى', 'امرأة', 'ست'], true) => UserGender::Female,
            default => null,
        };
    }

    private function resolveCountryId(mixed $legacyId): ?int
    {
        if ($legacyId === null || $legacyId === '') {
            return null;
        }

        $id = (int) $legacyId;
        if (isset($this->validCountryIds[$id])) {
            return $id;
        }

        // Legacy world-countries dump uses Egypt = 65.
        if ($id === 65) {
            return $this->egyptCountryId;
        }

        return null;
    }

    private function resolveStateId(mixed $legacyId): ?int
    {
        if ($legacyId === null || $legacyId === '') {
            return null;
        }

        $id = (int) $legacyId;

        return isset($this->validStateIds[$id]) ? $id : null;
    }

    private function normalizeNationalId(mixed $value): ?string
    {
        $nationalId = $this->nullableString($value);
        if ($nationalId === null) {
            return null;
        }

        // Keep digits only for comparison / storage consistency.
        $digits = preg_replace('/\D+/', '', $nationalId);

        return ($digits !== null && $digits !== '') ? $digits : $nationalId;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function nullableDate(mixed $value): ?string
    {
        $date = $this->nullableString($value);
        if ($date === null || $date === '0000-00-00' || str_starts_with($date, '0000-00-00')) {
            return null;
        }

        try {
            return Carbon::parse($date)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private function composeAddress(mixed $address, mixed $city, mixed $village): ?string
    {
        $parts = array_values(array_filter([
            $this->nullableString($address),
            $this->nullableString($village),
            $this->nullableString($city),
        ]));

        return $parts === [] ? null : implode(' — ', $parts);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function familyNotes(array $row): string
    {
        $parts = ['Imported from legacy families #'.($row['id'] ?? '?')];

        if (! empty($row['club_id'])) {
            $parts[] = 'Legacy club_id: '.$row['club_id'];
        }

        if (! empty($row['city'])) {
            $parts[] = 'Legacy city: '.$row['city'];
        }

        return implode("\n", $parts);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function caseSearchNotes(array $row): string
    {
        $parts = ['Imported from legacy case_searches #'.($row['id'] ?? '?')];

        if (! empty($row['code'])) {
            $parts[] = 'Legacy code: '.$row['code'];
        }

        if (isset($row['accepted'])) {
            $parts[] = 'Legacy accepted: '.$row['accepted'];
        }

        if (! empty($row['researcher_opinion'])) {
            $parts[] = 'Researcher opinion: '.$row['researcher_opinion'];
        }

        if (! empty($row['family_needs'])) {
            $parts[] = 'Needs: '.$row['family_needs'];
        }

        return implode("\n", $parts);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function individualProfileNotes(array $row): ?string
    {
        $parts = [];

        foreach ([
            'housing_type' => 'Housing type',
            'monthly_rent' => 'Monthly rent',
            'family_economic_status' => 'Economic status',
            'job_place' => 'Job place',
            'age' => 'Legacy age',
        ] as $field => $label) {
            $value = $this->nullableString($row[$field] ?? null);
            if ($value !== null) {
                $parts[] = "{$label}: {$value}";
            }
        }

        return $parts === [] ? null : implode("\n", $parts);
    }

    private function nextCode(): string
    {
        $this->codeSequence++;

        return $this->codePrefix.str_pad((string) $this->codeSequence, 4, '0', STR_PAD_LEFT);
    }

    private function recordFailure(string $source, mixed $legacyId, Throwable $e): void
    {
        $this->stats['skipped_failed']++;

        $message = sprintf(
            '%s #%s: %s',
            $source,
            $legacyId ?? '?',
            Str::limit($e->getMessage(), 200, '')
        );

        if (count($this->failures) < 25) {
            $this->failures[] = $message;
        }

        $this->output->writeln('');
        $this->warn('Skipped failed insert — '.$message);
    }

    private function printSummary(): void
    {
        $this->newLine();
        $this->info('Import summary');
        $this->table(
            ['Metric', 'Count'],
            collect($this->stats)->map(fn ($count, $metric) => [$metric, $count])->values()->all()
        );

        if ($this->failures !== []) {
            $this->newLine();
            $this->warn('Failed inserts (showing up to 25):');
            foreach ($this->failures as $failure) {
                $this->line('  - '.$failure);
            }

            $remaining = $this->stats['skipped_failed'] - count($this->failures);
            if ($remaining > 0) {
                $this->line("  … and {$remaining} more");
            }
        }
    }
}
