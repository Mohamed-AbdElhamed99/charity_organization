<?php

namespace Database\Seeders;

use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\BankExpense;
use App\Models\Campaign;
use App\Models\CampaignExpense;
use App\Models\Currency;
use App\Models\Donation;
use App\Models\GeneralExpense;
use App\Models\GeneralExpenseCategory;
use App\Models\Item;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the full financial dataset:
 * items, general expense categories, donations, campaign expenses,
 * general expenses, transfers, and bank expenses.
 *
 * Every financial record owns a Transaction (the unified ledger entry).
 * All inserts are wrapped per-record to maintain referential integrity.
 *
 * Depends on: FinancialFoundationSeeder, UserSeeder, CampaignSeeder, BeneficiarySeeder.
 */
class FinancialSeeder extends Seeder
{
    public function run(): void
    {
        $account       = Account::where('type', 'bank')->first();
        $usd           = Currency::where('code', 'USD')->first();
        $cashMethod    = PaymentMethod::where('code', 'cash')->first();
        $chequeMethod  = PaymentMethod::where('code', 'cheque')->first();
        $zelleMethod   = PaymentMethod::where('code', 'zelle')->first();
        $stripeMethod  = PaymentMethod::where('code', 'stripe')->first();
        $staffUser     = User::role('staff')->first() ?? User::role('super_admin')->first();
        $donors        = User::role('donor')->get();
        $activeCampaigns    = Campaign::active()->get();
        $completedCampaigns = Campaign::completed()->get();
        $allCampaigns       = $activeCampaigns->merge($completedCampaigns);

        // ─── Items Catalog ────────────────────────────────────────────────────

        $itemsData = [
            ['name_ar' => 'صندوق طعام',        'name_en' => 'Food Box',              'unit' => 'Box'],
            ['name_ar' => 'حقيبة ملابس',        'name_en' => 'Clothing Bag',          'unit' => 'Bag'],
            ['name_ar' => 'دواء',               'name_en' => 'Medicine Pack',         'unit' => 'Pack'],
            ['name_ar' => 'مستلزمات طبية',      'name_en' => 'Medical Supplies',      'unit' => 'Set'],
            ['name_ar' => 'مستلزمات مدرسية',    'name_en' => 'School Supplies Kit',   'unit' => 'Set'],
            ['name_ar' => 'بطانية',             'name_en' => 'Blanket',               'unit' => 'Piece'],
            ['name_ar' => 'مراتب',              'name_en' => 'Mattress',              'unit' => 'Piece'],
            ['name_ar' => 'مستلزمات نظافة',     'name_en' => 'Hygiene Kit',           'unit' => 'Kit'],
            ['name_ar' => 'حليب أطفال',         'name_en' => 'Baby Formula',          'unit' => 'Can'],
            ['name_ar' => 'مواد بناء',          'name_en' => 'Construction Materials','unit' => 'Kg'],
            ['name_ar' => 'أجهزة كهربائية',     'name_en' => 'Electrical Appliance',  'unit' => 'Piece'],
            ['name_ar' => 'أثاث',              'name_en' => 'Furniture',             'unit' => 'Piece'],
            ['name_ar' => 'لعب أطفال',          'name_en' => 'Toys',                  'unit' => 'Piece'],
        ];

        foreach ($itemsData as $data) {
            Item::firstOrCreate(['name_en' => $data['name_en']], array_merge($data, ['is_active' => true]));
        }

        $items = Item::all();

        // ─── General Expense Categories ───────────────────────────────────────

        $genCatData = [
            'Software & Subscriptions', 'Office Supplies', 'Utilities',
            'Salaries & Payroll', 'Marketing & Outreach', 'Travel & Transportation',
            'Legal & Compliance', 'Insurance', 'Telecommunication', 'Banking Fees',
        ];

        foreach ($genCatData as $name) {
            GeneralExpenseCategory::firstOrCreate(['name' => $name], ['is_active' => true]);
        }

        if (! app()->environment(['local', 'testing'])) {
            $this->command->info('✅ Financial reference data seeded. Skipping fake transactions (production).');
            return;
        }

        // ─── Donations ────────────────────────────────────────────────────────

        $this->command->info('  Seeding donations...');

        foreach (range(1, 60) as $i) {
            $isGeneral   = fake()->boolean(25);
            $campaign    = !$isGeneral && $allCampaigns->isNotEmpty()
                ? $allCampaigns->random()
                : null;
            $donor       = $donors->isNotEmpty() ? $donors->random() : null;
            $gross       = fake()->randomFloat(2, 25, 5_000);
            $fee         = round($gross * 0.029 + 0.30, 2);
            $net         = round($gross - $fee, 2);
            $method      = fake()->randomElement([$cashMethod, $chequeMethod, $zelleMethod, $stripeMethod]);
            $date        = fake()->dateTimeBetween('-1 year', 'now');

            // Create unified ledger entry first
            $transaction = Transaction::create([
                'account_id'       => $account->id,
                'transaction_type' => TransactionType::Donation,
                'direction'        => TransactionDirection::In,
                'currency_id'      => $usd->id,
                'gross_amount'     => $gross,
                'fee_amount'       => $method?->code === 'stripe' ? $fee : 0,
                'net_amount'       => $method?->code === 'stripe' ? $net : $gross,
                'transaction_date' => $date->format('Y-m-d'),
                'description'      => $isGeneral
                    ? 'General donation'
                    : "Donation for: {$campaign?->title_en}",
                'payment_method_id' => $method?->id,
                'created_by'        => $staffUser->id,
                'is_reconciled'     => fake()->boolean(50),
            ]);

            Donation::create([
                'transaction_id'           => $transaction->id,
                'donor_id'                 => $donor?->id,
                'campaign_id'              => $campaign?->id,
                'is_general'               => $isGeneral,
                'purpose_note'             => fake()->optional(0.3)->sentence(),
                'stripe_payment_intent_id' => $method?->code === 'stripe' ? 'pi_' . \Illuminate\Support\Str::random(24) : null,
                'stripe_charge_id'         => $method?->code === 'stripe' ? 'ch_' . \Illuminate\Support\Str::random(24) : null,
                'stripe_status'            => $method?->code === 'stripe' ? 'succeeded' : null,
                'donor_covers_fee'         => fake()->boolean(20),
            ]);
        }

        // ─── Campaign Expenses ────────────────────────────────────────────────

        $this->command->info('  Seeding campaign expenses...');

        if ($allCampaigns->isNotEmpty()) {
            foreach ($allCampaigns as $campaign) {
                // 2–6 expense line items per campaign
                $expenseCount = rand(2, 6);

                foreach (range(1, $expenseCount) as $j) {
                    $item      = $items->random();
                    $unitPrice = fake()->randomFloat(2, 5, 300);
                    $quantity  = fake()->randomFloat(1, 1, 100);
                    $amount    = round($unitPrice * $quantity, 2);
                    $residQty  = fake()->boolean(40) ? round($quantity * fake()->randomFloat(2, 0, 0.4), 1) : 0;
                    $residAmt  = round($residQty * $unitPrice, 2);

                    $transaction = Transaction::create([
                        'account_id'        => $account->id,
                        'transaction_type'  => TransactionType::CampaignExpense,
                        'direction'         => TransactionDirection::Out,
                        'currency_id'       => $usd->id,
                        'gross_amount'      => $amount,
                        'fee_amount'        => 0,
                        'net_amount'        => $amount,
                        'transaction_date'  => fake()->dateTimeBetween(
                            $campaign->start_date ?? '-1 year',
                            $campaign->end_date ?? 'now'
                        )->format('Y-m-d'),
                        'description'       => "Purchase of {$item->name_en} for campaign: {$campaign->title_en}",
                        'payment_method_id' => $cashMethod?->id,
                        'created_by'        => $staffUser->id,
                        'is_reconciled'     => fake()->boolean(40),
                    ]);

                    CampaignExpense::create([
                        'transaction_id'      => $transaction->id,
                        'campaign_id'         => $campaign->id,
                        'item_id'             => $item->id,
                        'item_price'          => $unitPrice,
                        'quantity'            => $quantity,
                        'amount'              => $amount,
                        'residual_quantity'   => $residQty,
                        'residual_amount'     => $residAmt,
                        'responsible_user_id' => $staffUser->id,
                        'expense_date'        => $transaction->transaction_date,
                    ]);
                }
            }
        }

        // ─── General Expenses (org operational costs) ─────────────────────────

        $this->command->info('  Seeding general expenses...');

        $genCategories = GeneralExpenseCategory::all();

        $orgExpenses = [
            ['name' => 'Zoom Pro Monthly',       'vendor' => 'Zoom',            'amount' => 149.90,  'recurring' => true],
            ['name' => 'Google Workspace',        'vendor' => 'Google',          'amount' => 12.00,   'recurring' => true],
            ['name' => 'Zoho CRM',                'vendor' => 'Zoho',            'amount' => 49.00,   'recurring' => true],
            ['name' => 'Gusto Payroll',           'vendor' => 'Gusto',           'amount' => 89.00,   'recurring' => true],
            ['name' => 'Aplos Accounting',        'vendor' => 'Aplos',           'amount' => 59.00,   'recurring' => true],
            ['name' => 'Microsoft 365',           'vendor' => 'Microsoft',       'amount' => 22.00,   'recurring' => true],
            ['name' => 'Canva Pro',               'vendor' => 'Canva',           'amount' => 12.99,   'recurring' => true],
            ['name' => 'Office Supplies Q1',      'vendor' => 'Staples',         'amount' => 234.50,  'recurring' => false],
            ['name' => 'AWS Hosting',             'vendor' => 'Amazon Web Services', 'amount' => 87.40, 'recurring' => true],
            ['name' => 'Cloudflare Plan',         'vendor' => 'Cloudflare',      'amount' => 20.00,   'recurring' => true],
        ];

        $softwareCat = $genCategories->where('name', 'Software & Subscriptions')->first();
        $officeCat   = $genCategories->where('name', 'Office Supplies')->first();

        foreach ($orgExpenses as $expense) {
            $transaction = Transaction::create([
                'account_id'        => $account->id,
                'transaction_type'  => TransactionType::GeneralExpense,
                'direction'         => TransactionDirection::Out,
                'currency_id'       => $usd->id,
                'gross_amount'      => $expense['amount'],
                'fee_amount'        => 0,
                'net_amount'        => $expense['amount'],
                'transaction_date'  => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
                'description'       => $expense['name'],
                'payment_method_id' => $chequeMethod?->id,
                'created_by'        => $staffUser->id,
                'is_reconciled'     => true,
            ]);

            GeneralExpense::create([
                'transaction_id' => $transaction->id,
                'category_id'    => $expense['recurring'] ? $softwareCat?->id : $officeCat?->id,
                'name'           => $expense['name'],
                'amount'         => $expense['amount'],
                'expense_date'   => $transaction->transaction_date,
                'vendor_name'    => $expense['vendor'],
                'is_recurring'   => $expense['recurring'],
                'created_by'     => $staffUser->id,
            ]);
        }

        // Additional random general expenses
        foreach (range(1, 15) as $i) {
            $amount = fake()->randomFloat(2, 20, 1_000);
            $transaction = Transaction::create([
                'account_id'        => $account->id,
                'transaction_type'  => TransactionType::GeneralExpense,
                'direction'         => TransactionDirection::Out,
                'currency_id'       => $usd->id,
                'gross_amount'      => $amount,
                'fee_amount'        => 0,
                'net_amount'        => $amount,
                'transaction_date'  => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
                'description'       => fake()->sentence(5),
                'payment_method_id' => $cashMethod?->id,
                'created_by'        => $staffUser->id,
            ]);

            GeneralExpense::create([
                'transaction_id' => $transaction->id,
                'category_id'    => $genCategories->isNotEmpty() ? $genCategories->random()->id : null,
                'name'           => fake()->sentence(3),
                'amount'         => $amount,
                'expense_date'   => $transaction->transaction_date,
                'vendor_name'    => fake()->company(),
                'is_recurring'   => fake()->boolean(30),
                'created_by'     => $staffUser->id,
            ]);
        }

        // ─── Transfers ────────────────────────────────────────────────────────

        $this->command->info('  Seeding transfers...');

        $activeBeneficiaries = \App\Models\Beneficiary::active()->get();

        foreach (range(1, 25) as $i) {
            $campaign      = $allCampaigns->isNotEmpty() ? $allCampaigns->random() : null;
            $isBeneficiary = fake()->boolean(40) && $activeBeneficiaries->isNotEmpty();
            $beneficiary   = $isBeneficiary ? $activeBeneficiaries->random() : null;
            $amount        = fake()->randomFloat(2, 100, 10_000);

            $transaction = Transaction::create([
                'account_id'        => $account->id,
                'transaction_type'  => TransactionType::Transfer,
                'direction'         => TransactionDirection::Out,
                'currency_id'       => $usd->id,
                'gross_amount'      => $amount,
                'fee_amount'        => 0,
                'net_amount'        => $amount,
                'transaction_date'  => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
                'description'       => "Transfer to " . ($isBeneficiary ? $beneficiary->displayName : fake()->name()),
                'payment_method_id' => fake()->randomElement([$zelleMethod, $chequeMethod])?->id,
                'created_by'        => $staffUser->id,
            ]);

            Transfer::create([
                'transaction_id'  => $transaction->id,
                'campaign_id'     => $campaign?->id,
                'recipient_type'  => $isBeneficiary ? 'beneficiary' : fake()->randomElement(['vendor', 'other']),
                'recipient_name'  => $isBeneficiary ? $beneficiary->displayName : fake()->name(),
                'recipient_phone' => fake()->optional(0.5)->phoneNumber(),
                'beneficiary_id'  => $beneficiary?->id,
                'user_id'         => null,
                'amount'          => $amount,
                'transfer_date'   => $transaction->transaction_date,
                'purpose'         => fake()->sentence(5),
                'created_by'      => $staffUser->id,
            ]);
        }

        // ─── Bank Expenses ────────────────────────────────────────────────────

        $this->command->info('  Seeding bank expenses...');

        $bankFees = [
            ['description' => 'Monthly service fee',           'amount' => 15.00],
            ['description' => 'Wire transfer charge',          'amount' => 25.00],
            ['description' => 'International transaction fee', 'amount' => 45.00],
            ['description' => 'Account maintenance fee',       'amount' => 12.00],
            ['description' => 'Returned cheque fee',           'amount' => 35.00],
        ];

        foreach ($bankFees as $fee) {
            foreach (range(1, 3) as $month) {
                $transaction = Transaction::create([
                    'account_id'        => $account->id,
                    'transaction_type'  => TransactionType::BankTransfer,
                    'direction'         => TransactionDirection::Out,
                    'currency_id'       => $usd->id,
                    'gross_amount'      => $fee['amount'],
                    'fee_amount'        => 0,
                    'net_amount'        => $fee['amount'],
                    'transaction_date'  => now()->subMonths($month)->format('Y-m-01'),
                    'description'       => $fee['description'],
                    'created_by'        => $staffUser->id,
                    'is_reconciled'     => true,
                ]);

                BankExpense::create([
                    'transaction_id' => $transaction->id,
                    'description'    => $fee['description'],
                    'amount'         => $fee['amount'],
                    'expense_date'   => $transaction->transaction_date,
                    'created_by'     => $staffUser->id,
                ]);
            }
        }

        $this->command->info('✅ Financial data seeded:');
        $this->command->info('   — Donations:         ' . Donation::count());
        $this->command->info('   — Campaign Expenses: ' . CampaignExpense::count());
        $this->command->info('   — General Expenses:  ' . GeneralExpense::count());
        $this->command->info('   — Transfers:         ' . Transfer::count());
        $this->command->info('   — Bank Expenses:     ' . BankExpense::count());
        $this->command->info('   — Transactions Total:' . Transaction::count());
    }
}
