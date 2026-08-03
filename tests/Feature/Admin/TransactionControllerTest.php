<?php

namespace Tests\Feature\Admin;

use App\Enums\TransactionDirection;
use App\Models\BankAccount;
use App\Models\Beneficiary;
use App\Models\Campaign;
use App\Models\Currency;
use App\Models\GeneralExpense;
use App\Models\Meeting;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\TransactionType;
use App\Models\User;
use Database\Seeders\FinancialFoundationSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createSuperAdmin(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    private function createStaffUser(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('staff');

        return $user;
    }

    private function seedFinancialData(): void
    {
        $this->seed(FinancialFoundationSeeder::class);
    }

    public function test_guests_cannot_access_transactions_index(): void
    {
        $this->get(route('admin.transactions.index'))
            ->assertNotFound();
    }

    public function test_authorized_user_can_view_transactions_index(): void
    {
        $user = $this->createSuperAdmin();
        $this->seedFinancialData();

        Transaction::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('admin.transactions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/transactions/transactions-index')
                ->has('transactions.data', 2)
                ->has('accounts')
                ->has('currencies')
                ->has('paymentMethods')
                ->has('transactionTypes')
            );
    }

    public function test_authorized_user_can_view_transaction_show_page(): void
    {
        $user = $this->createSuperAdmin();
        $this->seedFinancialData();

        $transaction = Transaction::factory()->donation()->create();

        $this->actingAs($user)
            ->get(route('admin.transactions.show', $transaction))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/transactions/transactions-show')
                ->where('transaction.id', $transaction->id)
            );
    }

    public function test_authorized_user_can_create_transaction(): void
    {
        $user = $this->createSuperAdmin();
        $this->seedFinancialData();

        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();
        $paymentMethod = PaymentMethod::query()->active()->orderBy('id')->firstOrFail();
        $donationTypeId = TransactionType::idForCode(TransactionType::CODE_DONATION);

        $this->actingAs($user)
            ->post(route('admin.transactions.store'), [
                'account_id' => $account->id,
                'transaction_type_id' => $donationTypeId,
                'direction' => TransactionDirection::In->value,
                'gross_amount' => 1000,
                'fee_amount' => 50,
                'transaction_date' => now()->toDateString(),
                'reference_number' => 'REF-001',
                'description' => 'Manual donation entry',
                'notes' => 'Test note',
                'payment_method_id' => $paymentMethod->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('transactions', [
            'account_id' => $account->id,
            'transaction_type_id' => $donationTypeId,
            'direction' => TransactionDirection::In->value,
            'gross_amount' => '1000.00',
            'fee_amount' => '50.00',
            'net_amount' => '950.00',
            'reference_number' => 'REF-001',
            'description' => 'Manual donation entry',
            'created_by' => $user->id,
        ]);
    }

    public function test_authorized_user_can_create_transaction_with_currency_equivalents(): void
    {
        $user = $this->createSuperAdmin();
        $this->seedFinancialData();

        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();
        $usd = Currency::query()->where('code', 'USD')->firstOrFail();
        $account->update(['currency_id' => $usd->id]);
        $donationTypeId = TransactionType::idForCode(TransactionType::CODE_DONATION);

        $this->actingAs($user)
            ->post(route('admin.transactions.store'), [
                'account_id' => $account->id,
                'transaction_type_id' => $donationTypeId,
                'direction' => TransactionDirection::In->value,
                'gross_amount' => 1000,
                'fee_amount' => 0,
                'transaction_date' => now()->toDateString(),
                'description' => 'Donation with EGP equivalent',
                'currency_equivalents' => [
                    ['currency_code' => 'EGP', 'equivalent' => 52],
                ],
            ])
            ->assertRedirect();

        $transaction = Transaction::query()->latest('id')->first();

        $this->assertNotNull($transaction);
        $this->assertEquals([
            ['currency_code' => 'EGP', 'equivalent' => 52],
        ], $transaction->currency_equivalents);

        $this->actingAs($user)
            ->get(route('admin.transactions.show', $transaction))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/transactions/transactions-show')
                ->where('transaction.currency_equivalent_amounts.0.currency_code', 'EGP')
                ->where('transaction.currency_equivalent_amounts.0.amount', 52000)
            );
    }

    public function test_create_transaction_rejects_ledger_currency_equivalent(): void
    {
        $user = $this->createSuperAdmin();
        $this->seedFinancialData();

        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();
        $usd = Currency::query()->where('code', 'USD')->firstOrFail();
        $account->update(['currency_id' => $usd->id]);
        $donationTypeId = TransactionType::idForCode(TransactionType::CODE_DONATION);

        $this->actingAs($user)
            ->from(route('admin.transactions.create'))
            ->post(route('admin.transactions.store'), [
                'account_id' => $account->id,
                'transaction_type_id' => $donationTypeId,
                'direction' => TransactionDirection::In->value,
                'gross_amount' => 100,
                'fee_amount' => 0,
                'transaction_date' => now()->toDateString(),
                'description' => 'Invalid equivalent',
                'currency_equivalents' => [
                    ['currency_code' => 'USD', 'equivalent' => 1],
                ],
            ])
            ->assertRedirect(route('admin.transactions.create'))
            ->assertSessionHasErrors('currency_equivalents.0.currency_code');
    }

    public function test_create_transaction_rejects_duplicate_currency_equivalent_codes(): void
    {
        $user = $this->createSuperAdmin();
        $this->seedFinancialData();

        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();
        $usd = Currency::query()->where('code', 'USD')->firstOrFail();
        $account->update(['currency_id' => $usd->id]);
        $donationTypeId = TransactionType::idForCode(TransactionType::CODE_DONATION);

        $this->actingAs($user)
            ->from(route('admin.transactions.create'))
            ->post(route('admin.transactions.store'), [
                'account_id' => $account->id,
                'transaction_type_id' => $donationTypeId,
                'direction' => TransactionDirection::In->value,
                'gross_amount' => 100,
                'fee_amount' => 0,
                'transaction_date' => now()->toDateString(),
                'description' => 'Duplicate equivalents',
                'currency_equivalents' => [
                    ['currency_code' => 'EGP', 'equivalent' => 50],
                    ['currency_code' => 'EGP', 'equivalent' => 51],
                ],
            ])
            ->assertRedirect(route('admin.transactions.create'))
            ->assertSessionHasErrors('currency_equivalents.1.currency_code');
    }

    public function test_authorized_user_can_create_transfer_transaction_with_label(): void
    {
        $user = $this->createSuperAdmin();
        $this->seedFinancialData();

        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();
        $transferTypeId = TransactionType::idForCode(TransactionType::CODE_TRANSFER);

        $response = $this->actingAs($user)
            ->post(route('admin.transactions.store'), [
                'account_id' => $account->id,
                'transaction_type_id' => $transferTypeId,
                'direction' => TransactionDirection::Out->value,
                'gross_amount' => 500,
                'fee_amount' => 0,
                'transaction_date' => now()->toDateString(),
                'description' => 'Aid transfer',
                'transfer' => [
                    'recipient_kind' => 'other',
                    'recipient_label' => 'Partner Org',
                    'purpose' => 'Emergency aid',
                ],
            ]);

        $transaction = Transaction::query()
            ->where('transaction_type_id', $transferTypeId)
            ->latest('id')
            ->first();

        $this->assertNotNull($transaction);
        $response->assertRedirect(route('admin.transactions.show', $transaction));

        $this->assertDatabaseHas('transfers', [
            'transaction_id' => $transaction->id,
            'recipient_label' => 'Partner Org',
            'purpose' => 'Emergency aid',
        ]);
    }

    public function test_authorized_user_can_view_create_page(): void
    {
        $user = $this->createSuperAdmin();
        $this->seedFinancialData();

        $this->actingAs($user)
            ->get(route('admin.transactions.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/transactions/transactions-create')
                ->has('accounts')
                ->has('users')
                ->has('campaigns')
                ->has('beneficiaries')
                ->has('meetings')
                ->has('generalExpenses')
                ->has('transactionTypes')
            );
    }

    public function test_authorized_user_can_create_transfer_to_user_with_campaign(): void
    {
        $user = $this->createSuperAdmin();
        $this->seedFinancialData();

        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();
        $transferTypeId = TransactionType::idForCode(TransactionType::CODE_TRANSFER);
        $recipient = User::factory()->create();
        $campaign = Campaign::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('admin.transactions.store'), [
                'account_id' => $account->id,
                'transaction_type_id' => $transferTypeId,
                'direction' => TransactionDirection::Out->value,
                'gross_amount' => 250,
                'fee_amount' => 0,
                'transaction_date' => now()->toDateString(),
                'description' => 'Staff transfer',
                'campaign_ids' => [$campaign->id],
                'transfer' => [
                    'recipient_kind' => 'user',
                    'recipient_id' => $recipient->id,
                    'purpose' => 'Field support',
                ],
            ]);

        $transaction = Transaction::query()
            ->where('transaction_type_id', $transferTypeId)
            ->latest('id')
            ->first();

        $this->assertNotNull($transaction);
        $response->assertRedirect(route('admin.transactions.show', $transaction));

        $this->assertDatabaseHas('transfers', [
            'transaction_id' => $transaction->id,
            'recipient_type' => User::class,
            'recipient_id' => $recipient->id,
            'purpose' => 'Field support',
        ]);

        $this->assertDatabaseHas('transactionables', [
            'transaction_id' => $transaction->id,
            'transactionable_type' => Campaign::class,
            'transactionable_id' => $campaign->id,
        ]);
    }

    public function test_authorized_user_can_link_polymorphic_related_records(): void
    {
        $user = $this->createSuperAdmin();
        $this->seedFinancialData();

        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();
        $donationTypeId = TransactionType::idForCode(TransactionType::CODE_DONATION);
        $campaign = Campaign::factory()->create();
        $meeting = Meeting::factory()->create();
        $beneficiary = Beneficiary::factory()->create();
        $generalExpense = GeneralExpense::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)
            ->post(route('admin.transactions.store'), [
                'account_id' => $account->id,
                'transaction_type_id' => $donationTypeId,
                'direction' => TransactionDirection::In->value,
                'gross_amount' => 100,
                'fee_amount' => 0,
                'transaction_date' => now()->toDateString(),
                'description' => 'Linked donation',
                'beneficiary_ids' => [$beneficiary->id],
                'campaign_ids' => [$campaign->id],
                'meeting_ids' => [$meeting->id],
                'general_expense_ids' => [$generalExpense->id],
            ]);

        $transaction = Transaction::query()
            ->where('description', 'Linked donation')
            ->latest('id')
            ->first();

        $this->assertNotNull($transaction);
        $response->assertRedirect(route('admin.transactions.show', $transaction));

        foreach ([
            [Beneficiary::class, $beneficiary->id],
            [Campaign::class, $campaign->id],
            [Meeting::class, $meeting->id],
            [GeneralExpense::class, $generalExpense->id],
        ] as [$type, $id]) {
            $this->assertDatabaseHas('transactionables', [
                'transaction_id' => $transaction->id,
                'transactionable_type' => $type,
                'transactionable_id' => $id,
            ]);
        }
    }

    public function test_authorized_user_can_update_transaction(): void
    {
        $user = $this->createSuperAdmin();
        $this->seedFinancialData();

        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();
        $currency = Currency::query()->active()->orderBy('id')->firstOrFail();
        $donationTypeId = TransactionType::idForCode(TransactionType::CODE_DONATION);

        $transaction = Transaction::factory()->donation()->create([
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'gross_amount' => 200,
            'fee_amount' => 0,
            'net_amount' => 200,
            'running_balance' => bcadd((string) $account->opening_balance, '200.00', 2),
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->put(route('admin.transactions.update', $transaction), [
                'account_id' => $account->id,
                'transaction_type_id' => $donationTypeId,
                'direction' => TransactionDirection::In->value,
                'gross_amount' => 300,
                'fee_amount' => 10,
                'transaction_date' => now()->toDateString(),
                'reference_number' => 'REF-UPDATED',
                'description' => 'Updated donation',
                'notes' => 'Updated notes',
                'payment_method_id' => null,
            ])
            ->assertRedirect(route('admin.transactions.show', $transaction));

        $transaction->refresh();

        $this->assertSame('300.00', (string) $transaction->gross_amount);
        $this->assertSame('10.00', (string) $transaction->fee_amount);
        $this->assertSame('290.00', (string) $transaction->net_amount);
        $this->assertSame('REF-UPDATED', $transaction->reference_number);
        $this->assertSame('Updated donation', $transaction->description);
    }

    public function test_authorized_user_can_export_filtered_transactions_as_csv(): void
    {
        $user = $this->createSuperAdmin();
        $this->seedFinancialData();

        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();

        Transaction::factory()->donation()->create([
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'transaction_date' => '2026-01-15',
            'description' => 'January donation',
            'net_amount' => 500,
            'gross_amount' => 500,
            'fee_amount' => 0,
            'running_balance' => bcadd((string) $account->opening_balance, '500.00', 2),
        ]);

        Transaction::factory()->transfer()->create([
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'transaction_date' => '2026-02-10',
            'description' => 'February transfer',
            'net_amount' => 100,
            'gross_amount' => 100,
            'fee_amount' => 0,
            'running_balance' => bcadd((string) $account->opening_balance, '400.00', 2),
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.transactions.export', [
                'date_from' => '2026-01-01',
                'date_to' => '2026-01-31',
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('Date,Balance,Expenses,Donations,Transfer,Details', $content);
        $this->assertStringContainsString('January donation', $content);
        $this->assertStringNotContainsString('February transfer', $content);
    }

    public function test_calendar_view_defaults_to_current_month(): void
    {
        $user = $this->createSuperAdmin();
        $this->seedFinancialData();

        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();
        $currentMonth = now()->format('Y-m');
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        Transaction::factory()->donation()->create([
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'transaction_date' => now()->toDateString(),
            'description' => 'Current month transaction',
        ]);

        Transaction::factory()->donation()->create([
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'transaction_date' => now()->subMonth()->toDateString(),
            'description' => 'Previous month transaction',
        ]);

        $this->actingAs($user)
            ->get(route('admin.transactions.index', ['view' => 'calendar']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/transactions/transactions-index')
                ->where('search.view', 'calendar')
                ->where('search.month', $currentMonth)
                ->where('search.date_from', $startOfMonth)
                ->where('search.date_to', $endOfMonth)
                ->has('transactions.data', 1)
                ->where('transactions.data.0.description', 'Current month transaction')
            );
    }

    public function test_calendar_view_scopes_transactions_to_requested_month(): void
    {
        $user = $this->createSuperAdmin();
        $this->seedFinancialData();

        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();

        Transaction::factory()->donation()->create([
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'transaction_date' => '2026-01-15',
            'description' => 'January transaction',
        ]);

        Transaction::factory()->donation()->create([
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'transaction_date' => '2026-02-10',
            'description' => 'February transaction',
        ]);

        $this->actingAs($user)
            ->get(route('admin.transactions.index', [
                'view' => 'calendar',
                'month' => '2026-02',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/transactions/transactions-index')
                ->where('search.view', 'calendar')
                ->where('search.month', '2026-02')
                ->where('search.date_from', '2026-02-01')
                ->where('search.date_to', '2026-02-28')
                ->has('transactions.data', 1)
                ->where('transactions.data.0.description', 'February transaction')
            );
    }

    public function test_calendar_view_orders_transactions_by_date_ascending(): void
    {
        $user = $this->createSuperAdmin();
        $this->seedFinancialData();

        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();

        Transaction::factory()->donation()->create([
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'transaction_date' => '2026-07-20',
            'description' => 'Later July transaction',
        ]);

        Transaction::factory()->donation()->create([
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'transaction_date' => '2026-07-05',
            'description' => 'Earlier July transaction',
        ]);

        $this->actingAs($user)
            ->get(route('admin.transactions.index', [
                'view' => 'calendar',
                'month' => '2026-07',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/transactions/transactions-index')
                ->has('transactions.data', 2)
                ->where('transactions.data.0.description', 'Earlier July transaction')
                ->where('transactions.data.1.description', 'Later July transaction')
            );
    }

    public function test_list_view_ignores_calendar_month_param(): void
    {
        $user = $this->createSuperAdmin();
        $this->seedFinancialData();

        Transaction::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('admin.transactions.index', [
                'view' => 'list',
                'month' => '2026-02',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/transactions/transactions-index')
                ->where('search.view', 'list')
                ->missing('search.month')
                ->has('transactions.data', 2)
            );
    }

    public function test_staff_without_edit_permission_cannot_update_transaction(): void
    {
        $user = $this->createStaffUser();
        $this->seedFinancialData();

        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();
        $currency = Currency::query()->active()->orderBy('id')->firstOrFail();
        $transferTypeId = TransactionType::idForCode(TransactionType::CODE_TRANSFER);

        $transaction = Transaction::factory()->transfer()->create([
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->put(route('admin.transactions.update', $transaction), [
                'account_id' => $account->id,
                'transaction_type_id' => $transferTypeId,
                'direction' => TransactionDirection::Out->value,
                'gross_amount' => 300,
                'fee_amount' => 0,
                'transaction_date' => now()->toDateString(),
                'description' => 'Should fail',
            ])
            ->assertForbidden();
    }

    public function test_user_without_view_permission_cannot_export_transactions(): void
    {
        $user = $this->createStaffUser();

        $this->actingAs($user)
            ->get(route('admin.transactions.export'))
            ->assertForbidden();
    }

    public function test_calculate_balance_recalculates_month_from_first_running_balance(): void
    {
        $user = $this->createSuperAdmin();
        $this->seedFinancialData();

        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();

        $first = Transaction::factory()->donation()->create([
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'transaction_date' => '2026-07-01',
            'gross_amount' => 1000,
            'fee_amount' => 0,
            'net_amount' => 1000,
            'balance_before' => 0,
            'running_balance' => 1000,
            'description' => 'Starter balance day',
        ]);

        $donation = Transaction::factory()->donation()->create([
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'transaction_date' => '2026-07-02',
            'gross_amount' => 125,
            'fee_amount' => 0,
            'net_amount' => 125,
            'balance_before' => null,
            'running_balance' => null,
            'description' => 'July donation',
        ]);

        $transfer = Transaction::factory()->transfer()->create([
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'transaction_date' => '2026-07-03',
            'gross_amount' => 500,
            'fee_amount' => 0,
            'net_amount' => 500,
            'balance_before' => 999,
            'running_balance' => 999,
            'description' => 'July transfer',
        ]);

        $this->actingAs($user)
            ->post(route('admin.transactions.calculate-balance'), ['month' => '2026-07'])
            ->assertRedirect(route('admin.transactions.index', [
                'view' => 'calendar',
                'month' => '2026-07',
            ]));

        $flash = session('inertia.flash_data');
        $this->assertIsArray($flash);
        $this->assertSame('success', $flash['toast']['type']);

        $first->refresh();
        $donation->refresh();
        $transfer->refresh();

        $this->assertSame('0.00', $first->balance_before);
        $this->assertSame('1000.00', $first->running_balance);
        $this->assertSame('1000.00', $donation->balance_before);
        $this->assertSame('1125.00', $donation->running_balance);
        $this->assertSame('1125.00', $transfer->balance_before);
        $this->assertSame('625.00', $transfer->running_balance);
    }

    public function test_calculate_balance_handles_accounts_independently(): void
    {
        $user = $this->createSuperAdmin();
        $this->seedFinancialData();

        $accounts = BankAccount::query()->active()->orderBy('id')->take(2)->get();
        $this->assertGreaterThanOrEqual(2, $accounts->count());

        $accountA = $accounts[0];
        $accountB = $accounts[1];

        Transaction::factory()->donation()->create([
            'account_id' => $accountA->id,
            'currency_id' => $accountA->currency_id,
            'transaction_date' => '2026-07-01',
            'gross_amount' => 1000,
            'fee_amount' => 0,
            'net_amount' => 1000,
            'running_balance' => 1000,
        ]);

        $aSecond = Transaction::factory()->donation()->create([
            'account_id' => $accountA->id,
            'currency_id' => $accountA->currency_id,
            'transaction_date' => '2026-07-05',
            'gross_amount' => 100,
            'fee_amount' => 0,
            'net_amount' => 100,
            'running_balance' => null,
        ]);

        Transaction::factory()->donation()->create([
            'account_id' => $accountB->id,
            'currency_id' => $accountB->currency_id,
            'transaction_date' => '2026-07-01',
            'gross_amount' => 500,
            'fee_amount' => 0,
            'net_amount' => 500,
            'running_balance' => 500,
        ]);

        $bSecond = Transaction::factory()->transfer()->create([
            'account_id' => $accountB->id,
            'currency_id' => $accountB->currency_id,
            'transaction_date' => '2026-07-05',
            'gross_amount' => 50,
            'fee_amount' => 0,
            'net_amount' => 50,
            'running_balance' => null,
        ]);

        $this->actingAs($user)
            ->post(route('admin.transactions.calculate-balance'), ['month' => '2026-07'])
            ->assertRedirect();

        $aSecond->refresh();
        $bSecond->refresh();

        $this->assertSame('1000.00', $aSecond->balance_before);
        $this->assertSame('1100.00', $aSecond->running_balance);
        $this->assertSame('500.00', $bSecond->balance_before);
        $this->assertSame('450.00', $bSecond->running_balance);
    }

    public function test_calculate_balance_stops_when_first_transaction_has_no_balance(): void
    {
        $user = $this->createSuperAdmin();
        $this->seedFinancialData();

        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();

        Transaction::factory()->donation()->create([
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'transaction_date' => '2026-07-05',
            'gross_amount' => 1000,
            'fee_amount' => 0,
            'net_amount' => 1000,
            'running_balance' => null,
        ]);

        $later = Transaction::factory()->donation()->create([
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'transaction_date' => '2026-07-10',
            'gross_amount' => 125,
            'fee_amount' => 0,
            'net_amount' => 125,
            'balance_before' => 42,
            'running_balance' => 42,
        ]);

        $this->actingAs($user)
            ->from(route('admin.transactions.index', [
                'view' => 'calendar',
                'month' => '2026-07',
            ]))
            ->post(route('admin.transactions.calculate-balance'), ['month' => '2026-07'])
            ->assertRedirect();

        $flash = session('inertia.flash_data');
        $this->assertIsArray($flash);
        $this->assertSame('error', $flash['toast']['type']);
        $this->assertStringContainsString('2026-07-05', $flash['toast']['message']);
        $this->assertStringContainsString('balance to calculate based on it', $flash['toast']['message']);

        $later->refresh();
        $this->assertSame('42.00', $later->balance_before);
        $this->assertSame('42.00', $later->running_balance);
    }

    public function test_calculate_balance_does_not_modify_other_months(): void
    {
        $user = $this->createSuperAdmin();
        $this->seedFinancialData();

        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();

        $june = Transaction::factory()->donation()->create([
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'transaction_date' => '2026-06-15',
            'gross_amount' => 200,
            'fee_amount' => 0,
            'net_amount' => 200,
            'balance_before' => 10,
            'running_balance' => 20,
        ]);

        Transaction::factory()->donation()->create([
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'transaction_date' => '2026-07-01',
            'gross_amount' => 1000,
            'fee_amount' => 0,
            'net_amount' => 1000,
            'running_balance' => 1000,
        ]);

        Transaction::factory()->donation()->create([
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'transaction_date' => '2026-07-02',
            'gross_amount' => 50,
            'fee_amount' => 0,
            'net_amount' => 50,
            'running_balance' => null,
        ]);

        $this->actingAs($user)
            ->post(route('admin.transactions.calculate-balance'), ['month' => '2026-07'])
            ->assertRedirect();

        $june->refresh();
        $this->assertSame('10.00', $june->balance_before);
        $this->assertSame('20.00', $june->running_balance);
    }

    public function test_staff_without_edit_permission_cannot_calculate_balance(): void
    {
        $user = $this->createStaffUser();

        $this->actingAs($user)
            ->post(route('admin.transactions.calculate-balance'), ['month' => '2026-07'])
            ->assertForbidden();
    }
}
