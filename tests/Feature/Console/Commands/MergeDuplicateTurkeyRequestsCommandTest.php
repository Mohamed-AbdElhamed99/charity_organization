<?php

namespace Tests\Feature\Console\Commands;

use App\Models\Beneficiary;
use App\Models\BeneficiarySupport;
use App\Models\TurkeyRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeDuplicateTurkeyRequestsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create();
    }

    /**
     * @return list<TurkeyRequest>
     */
    private function createDuplicates(int $count, array $overrides = []): array
    {
        $requests = [];

        for ($i = 0; $i < $count; $i++) {
            $requests[] = TurkeyRequest::factory()->create(array_merge([
                'full_name' => 'Ashraf Mansour',
                'email' => 'ashraf@example.com',
                'phone' => '5551234567',
            ], $overrides));
        }

        return $requests;
    }

    public function test_ten_pending_duplicates_become_one_request_with_quantity_ten(): void
    {
        $requests = $this->createDuplicates(10);
        $stranger = TurkeyRequest::factory()->create();

        $this->artisan('turkey:merge-duplicates', ['--force' => true])->assertSuccessful();

        $merged = TurkeyRequest::query()->where('full_name', 'Ashraf Mansour')->get();
        $this->assertCount(1, $merged);
        $this->assertSame($requests[0]->id, $merged->first()->id);
        $this->assertSame(10, $merged->first()->quantity);
        $this->assertSame(1, $stranger->refresh()->quantity);
        $this->assertSame(2, TurkeyRequest::query()->count());
    }

    public function test_requests_are_matched_by_phone_or_email_separately(): void
    {
        $byPhone = TurkeyRequest::factory()->create(['email' => 'a1@example.com', 'phone' => '+1 (555) 123-4567']);
        TurkeyRequest::factory()->create(['email' => 'a2@example.com', 'phone' => '15551234567']);
        TurkeyRequest::factory()->create(['email' => 'same@example.com', 'phone' => '111111111']);
        TurkeyRequest::factory()->create(['email' => 'SAME@example.com', 'phone' => '222222222']);

        $this->artisan('turkey:merge-duplicates', ['--force' => true])->assertSuccessful();

        $this->assertSame(2, TurkeyRequest::query()->count());
        $this->assertSame(2, $byPhone->refresh()->quantity);
        $this->assertSame([2, 2], TurkeyRequest::query()->orderBy('id')->pluck('quantity')->all());
    }

    public function test_delivered_and_cancelled_requests_are_left_alone(): void
    {
        $this->createDuplicates(2);
        $delivered = TurkeyRequest::factory()->delivered()->create([
            'email' => 'ashraf@example.com',
            'phone' => '5551234567',
        ]);
        $cancelled = TurkeyRequest::factory()->cancelled()->create([
            'email' => 'ashraf@example.com',
            'phone' => '5551234567',
        ]);

        $this->artisan('turkey:merge-duplicates', ['--force' => true])->assertSuccessful();

        $this->assertSame(3, TurkeyRequest::query()->count());
        $this->assertSame(1, $delivered->refresh()->quantity);
        $this->assertSame(1, $cancelled->refresh()->quantity);
        $this->assertSame(2, TurkeyRequest::query()->pending()->firstOrFail()->quantity);
    }

    public function test_extra_beneficiaries_are_removed_only_when_unused(): void
    {
        [$kept, $unused, $withSupport] = $this->createDuplicates(3);
        BeneficiarySupport::factory()->create([
            'beneficiary_id' => $withSupport->beneficiary_id,
            'campaign_id' => $withSupport->campaign_id,
        ]);

        $this->artisan('turkey:merge-duplicates', ['--force' => true])->assertSuccessful();

        $this->assertNotNull(Beneficiary::query()->find($kept->beneficiary_id));
        $this->assertNull(Beneficiary::query()->find($unused->beneficiary_id));
        $this->assertNotNull(Beneficiary::query()->find($withSupport->beneficiary_id));
    }

    public function test_dry_run_changes_nothing(): void
    {
        $this->createDuplicates(3);

        $this->artisan('turkey:merge-duplicates', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame(3, TurkeyRequest::query()->count());
        $this->assertSame(0, TurkeyRequest::query()->where('quantity', '>', 1)->count());
    }

    public function test_running_twice_merges_nothing_the_second_time(): void
    {
        $this->createDuplicates(4);

        $this->artisan('turkey:merge-duplicates', ['--force' => true])->assertSuccessful();
        $this->artisan('turkey:merge-duplicates', ['--force' => true])
            ->expectsOutputToContain('No duplicate pending requests found.')
            ->assertSuccessful();

        $this->assertSame(4, TurkeyRequest::query()->firstOrFail()->quantity);
    }

    public function test_declining_the_confirmation_aborts(): void
    {
        $this->createDuplicates(2);

        $this->artisan('turkey:merge-duplicates')
            ->expectsConfirmation('Merge these requests? Removed codes become invalid.', 'no')
            ->assertFailed();

        $this->assertSame(2, TurkeyRequest::query()->count());
    }
}
