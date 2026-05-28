<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Currency;
use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

/**
 * Seeds the financial foundation: currencies, accounts, payment methods.
 * Must run before any transaction/donation/expense seeders.
 */
class FinancialFoundationSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Currencies ───────────────────────────────────────────────────────

        $currenciesData = [
            ['code' => 'USD', 'name' => 'US Dollar',       'symbol' => '$',    'is_default' => true,  'is_active' => true],
            ['code' => 'EGP', 'name' => 'Egyptian Pound',  'symbol' => 'ج.م',  'is_default' => false, 'is_active' => true],
            ['code' => 'EUR', 'name' => 'Euro',             'symbol' => '€',    'is_default' => false, 'is_active' => true],
            ['code' => 'GBP', 'name' => 'British Pound',   'symbol' => '£',    'is_default' => false, 'is_active' => true],
            ['code' => 'SAR', 'name' => 'Saudi Riyal',     'symbol' => 'ر.س',  'is_default' => false, 'is_active' => true],
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'CA$',  'is_default' => false, 'is_active' => true],
        ];

        foreach ($currenciesData as $data) {
            Currency::firstOrCreate(['code' => $data['code']], $data);
        }

        $usd = Currency::where('code', 'USD')->first();
        $egp = Currency::where('code', 'EGP')->first();

        // ─── Org Bank Accounts ────────────────────────────────────────────────

        $accountsData = [
            [
                'name'            => 'Chase Business Checking',
                'account_number'  => '4521-7890-1234-5678',
                'bank_name'       => 'Chase',
                'bank_branch'     => 'New York Main Branch',
                'currency_id'     => $usd->id,
                'type'            => 'bank',
                'opening_balance' => 25_000.00,
                'is_active'       => true,
                'notes'           => 'Primary operating account',
            ],
            [
                'name'            => 'Zelle Donations Wallet',
                'account_number'  => null,
                'bank_name'       => null,
                'bank_branch'     => null,
                'currency_id'     => $usd->id,
                'type'            => 'digital',
                'opening_balance' => 0.00,
                'is_active'       => true,
                'notes'           => 'Zelle incoming donations',
            ],
            [
                'name'            => 'Petty Cash',
                'account_number'  => null,
                'bank_name'       => null,
                'bank_branch'     => null,
                'currency_id'     => $usd->id,
                'type'            => 'cash',
                'opening_balance' => 500.00,
                'is_active'       => true,
                'notes'           => 'Office petty cash fund',
            ],
            [
                'name'            => 'Cairo Operations Account',
                'account_number'  => '0123-4567-8901',
                'bank_name'       => 'National Bank of Egypt',
                'bank_branch'     => 'Cairo Branch',
                'currency_id'     => $egp->id,
                'type'            => 'bank',
                'opening_balance' => 50_000.00,
                'is_active'       => true,
                'notes'           => 'Egypt field operations account',
            ],
        ];

        foreach ($accountsData as $data) {
            Account::firstOrCreate(['name' => $data['name']], $data);
        }

        // ─── Payment Methods ──────────────────────────────────────────────────

        $methodsData = [
            ['name' => 'Cash',           'code' => 'cash',          'is_active' => true],
            ['name' => 'Cheque',         'code' => 'cheque',        'is_active' => true],
            ['name' => 'Bank Transfer',  'code' => 'bank_transfer', 'is_active' => true],
            ['name' => 'Zelle',          'code' => 'zelle',         'is_active' => true],
            ['name' => 'Stripe',         'code' => 'stripe',        'is_active' => true],
            ['name' => 'Credit Card',    'code' => 'credit_card',   'is_active' => true],
            ['name' => 'PayPal',         'code' => 'paypal',        'is_active' => true],
            ['name' => 'Wire Transfer',  'code' => 'wire_transfer', 'is_active' => true],
        ];

        foreach ($methodsData as $data) {
            PaymentMethod::firstOrCreate(['code' => $data['code']], $data);
        }

        $this->command->info('✅ Financial foundation seeded (' .
            Currency::count() . ' currencies, ' .
            Account::count() . ' accounts, ' .
            PaymentMethod::count() . ' payment methods).'
        );
    }
}
