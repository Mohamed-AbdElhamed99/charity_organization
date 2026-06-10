<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Migrates the legacy `transactions` ledger and its related detail/lookup
 * tables into the new optimized schema (unified `transactions` + four detail
 * tables: donations, campaign_expenses, general_expenses, transfers).
 *
 *   Source (legacy)                 -> Target (new)
 *   --------------------------------------------------------------
 *   transactions_accounts           -> accounts
 *   accounts (bank accounts)        -> accounts            (id-offset to avoid clash)
 *   currencies                      -> currencies
 *   payment_methods                 -> payment_methods
 *   items                           -> items
 *   transactions                    -> transactions          (parent ledger)
 *     + transaction_deposits        ->   donations
 *     + transaction_expenses        ->   campaign_expenses
 *     + transaction_general_expenses->   general_expenses
 *     + transaction_users           ->   donations (member payment)
 *   bank_transactions               -> transactions (type=bank_transfer)
 *                                       linked rows reconcile, not duplicate
 *
 * Design decisions (confirmed with the team):
 *   - IDs are PRESERVED 1:1 for every migrated table (run with --fresh to
 *     clear targets first via DELETE; the command inserts explicit ids).
 *   - Legacy data is messy, so un-resolvable foreign keys are written as NULL.
 *     This requires the companion schema migration that relaxes the matching
 *     NOT NULL constraints. Presence is enforced on NEW records by the
 *     FormRequest validation layer, not the database.
 *   - The legacy `transactions` ledger carries no currency: every migrated
 *     transaction gets the default currency (the `is_default` row), or NULL.
 *   - `running_balance` does not exist in the old schema; it is COMPUTED here
 *     per-account in (date, id) order.
 *
 * Setup: add a second connection named `legacy` (or pass --connection=) in
 * config/database.php pointing at the old database, e.g.
 *
 *   'legacy' => [
 *       'driver' => 'mysql',
 *       'host' => env('LEGACY_DB_HOST', '127.0.0.1'),
 *       'database' => env('LEGACY_DB_DATABASE', 'newegypt_db'),
 *       'username' => env('LEGACY_DB_USERNAME'),
 *       'password' => env('LEGACY_DB_PASSWORD'),
 *       'charset' => 'utf8mb4',
 *       'collation' => 'utf8mb4_unicode_ci',
 *   ],
 */
class MigrateLegacyTransactionsCommand extends Command
{
    protected $signature = 'migrate:legacy-transactions
        {--connection=legacy : DB connection name for the OLD database}
        {--chunk=500 : Rows processed per chunk}
        {--fresh : Truncate target tables before importing}
        {--include-reconciled-bank : Also import bank rows already linked to a ledger transaction (default: skip to avoid double-counting)}
        {--dry-run : Run everything inside a transaction and roll back at the end}';

    protected $description = 'Migrate the legacy transactions ledger and its related tables into the new optimized schema.';

    /** Old DB connection handle. */
    private string $legacy;

    /** Lookup caches built once at the start of the run. */
    private array $userIds = [];        // set of valid new users.id
    private array $itemIds = [];        // set of valid new items.id
    private array $paymentByKeyword = []; // keyword => payment_method id
    private ?int $defaultCurrencyId = null;

    /** Detail-table indexes keyed by legacy transaction_id. */
    private array $deposits = [];
    private array $expenses = [];        // transaction_id => [rows...]
    private array $generalExpenses = []; // transaction_id => [rows...]
    private array $txUsers = [];

    /** Legacy general_expenses (id => name) name lookup. */
    private array $generalExpenseNames = [];

    /** Legacy bank `accounts`.id => new accounts.id (offset to avoid collision). */
    private array $bankAccountMap = [];

    private array $stats = [];

    public function handle(): int
    {
        $this->legacy = $this->option('connection');

        if (! $this->verifyLegacyConnection()) {
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $this->stats = [
            'accounts' => 0, 'currencies' => 0, 'payment_methods' => 0, 'items' => 0,
            'transactions' => 0, 'donations' => 0, 'campaign_expenses' => 0,
            'general_expenses' => 0, 'bank_accounts' => 0, 'bank_transactions' => 0,
            'bank_reconciled' => 0, 'skipped' => 0,
        ];

        $this->info("Migrating from connection [{$this->legacy}]"
            . ($dryRun ? ' (DRY RUN — will roll back)' : '') . '...');

        DB::beginTransaction();
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            if ($this->option('fresh')) {
                $this->truncateTargets();
            }

            $this->migrateCurrencies();
            $this->migratePaymentMethods();
            $this->migrateItems();
            $this->migrateAccounts();
            $this->migrateBankAccounts();

            $this->buildLookups();
            $this->loadDetailIndexes();

            $this->migrateLedger();
            $this->migrateBankTransactions();

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            if ($dryRun) {
                DB::rollBack();
                $this->warn('Dry run complete — all changes rolled back.');
            } else {
                DB::commit();
                $this->info('Migration committed successfully.');
            }
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error('Migration failed and was rolled back: ' . $e->getMessage());
            $this->line($e->getFile() . ':' . $e->getLine());
            return self::FAILURE;
        }

        $this->printSummary();
        return self::SUCCESS;
    }

    // ---------------------------------------------------------------------
    // Setup / safety
    // ---------------------------------------------------------------------

    private function verifyLegacyConnection(): bool
    {
        try {
            DB::connection($this->legacy)->getPdo();
        } catch (Throwable $e) {
            $this->error("Cannot connect to legacy DB [{$this->legacy}]: " . $e->getMessage());
            $this->line('Add the connection to config/database.php (see class docblock).');
            return false;
        }

        if (! Schema::connection($this->legacy)->hasTable('transactions')) {
            $this->error("Legacy connection has no `transactions` table — wrong database?");
            return false;
        }

        return true;
    }

    /**
     * Clears target tables using DELETE (NOT truncate). TRUNCATE is DDL and
     * forces an implicit COMMIT in MySQL, which would end our surrounding
     * transaction and break both rollback-on-error and --dry-run. DELETE is
     * DML, so it stays inside the transaction. Explicit IDs are inserted, so
     * we don't need TRUNCATE's auto-increment reset.
     */
    private function truncateTargets(): void
    {
        // Children before parents (FK checks are already off, but keep order tidy).
        foreach (['donations', 'campaign_expenses', 'general_expenses', 'transfers', 'transactions'] as $t) {
            DB::table($t)->delete();
        }
        // Lookups owned by this import (only safe because we preserve IDs).
        foreach (['accounts', 'items', 'payment_methods', 'currencies'] as $t) {
            DB::table($t)->delete();
        }
        $this->line('Cleared target tables.');
    }

    // ---------------------------------------------------------------------
    // Lookup migrations (IDs preserved 1:1)
    // ---------------------------------------------------------------------

    private function migrateCurrencies(): void
    {
        $rows = DB::connection($this->legacy)->table('currencies')->get();
        $now = now();

        foreach ($rows as $i => $c) {
            DB::table('currencies')->insert([
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->code,                 // legacy has no name
                'symbol' => $c->symbol,
                'is_default' => 0,                  // set below
                'is_active' => 1,
                'created_at' => $c->created_at ?? $now,
                'updated_at' => $c->updated_at ?? $now,
            ]);
            $this->stats['currencies']++;
        }

        // Pick a default: prefer USD, else the lowest id. Create one if none exist.
        $usd = DB::table('currencies')->whereRaw('LOWER(code) = ?', ['usd'])->first();
        $this->defaultCurrencyId = $usd?->id ?? DB::table('currencies')->min('id');

        if ($this->defaultCurrencyId === null) {
            $id = DB::table('currencies')->insertGetId([
                'code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$',
                'is_default' => 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $this->defaultCurrencyId = $id;
            $this->stats['currencies']++;
        } else {
            DB::table('currencies')->where('id', $this->defaultCurrencyId)->update(['is_default' => 1]);
        }
    }

    private function migratePaymentMethods(): void
    {
        $rows = DB::connection($this->legacy)->table('payment_methods')->get();
        $now = now();

        foreach ($rows as $pm) {
            DB::table('payment_methods')->insert([
                'id' => $pm->id,
                'name' => $pm->name,
                'code' => Str::slug($pm->name, '_') ?: ('pm_' . $pm->id),
                'is_active' => 1,
                'created_at' => $pm->created_at ?? $now,
                'updated_at' => $pm->updated_at ?? $now,
                'deleted_at' => $pm->deleted_at ?? null,
            ]);
            $this->stats['payment_methods']++;
        }
    }

    private function migrateItems(): void
    {
        if (! Schema::connection($this->legacy)->hasTable('items')) {
            return;
        }
        $now = now();

        DB::connection($this->legacy)->table('items')->orderBy('id')
            ->chunk((int) $this->option('chunk'), function ($items) use ($now) {
                foreach ($items as $it) {
                    $ar = $it->name_ar ?: $it->name_en ?: ('Item #' . $it->id);
                    $en = $it->name_en ?: $it->name_ar ?: ('Item #' . $it->id);
                    DB::table('items')->insert([
                        'id' => $it->id,
                        'name_ar' => $ar,
                        'name_en' => $en,
                        'description' => $it->details_en ?: $it->details_ar,
                        'unit' => null,
                        'is_active' => 1,
                        'created_at' => $it->created_at ?? $now,
                        'updated_at' => $it->updated_at ?? $now,
                        'deleted_at' => $it->deleted_at ?? null,
                    ]);
                    $this->stats['items']++;
                }
            });
    }

    private function migrateAccounts(): void
    {
        $rows = DB::connection($this->legacy)->table('transactions_accounts')->get();
        $now = now();

        foreach ($rows as $a) {
            DB::table('accounts')->insert([
                'id' => $a->id,
                'name' => $a->name,
                'account_number' => $a->number,
                'bank_name' => null,
                'bank_branch' => null,
                'currency_id' => $this->defaultCurrencyId,
                'type' => 'bank',
                'opening_balance' => 0,
                'is_active' => 1,
                'notes' => 'Imported from legacy transactions_accounts.',
                'created_at' => $a->created_at ?? $now,
                'updated_at' => $a->updated_at ?? $now,
                'deleted_at' => $a->deleted_at ?? null,
            ]);
            $this->stats['accounts']++;
        }
    }

    /**
     * The legacy `accounts` table (bank accounts referenced by bank_transactions)
     * is a DIFFERENT table from `transactions_accounts`, with its own id space.
     * Migrate it into the new `accounts` table using an offset so its ids cannot
     * collide with the operational accounts already migrated above. The offset is
     * computed dynamically as the current max accounts.id, so it is deterministic
     * across --fresh runs.
     */
    private function migrateBankAccounts(): void
    {
        if (! Schema::connection($this->legacy)->hasTable('accounts')) {
            return;
        }

        $offset = (int) (DB::table('accounts')->max('id') ?? 0);
        $now = now();

        foreach (DB::connection($this->legacy)->table('accounts')->get() as $a) {
            $newId = $offset + (int) $a->id;
            $this->bankAccountMap[(int) $a->id] = $newId;

            DB::table('accounts')->insert([
                'id' => $newId,
                'name' => $a->name,
                'account_number' => $a->number,
                'bank_name' => null,
                'bank_branch' => null,
                'currency_id' => $this->defaultCurrencyId,
                'type' => 'bank',
                'opening_balance' => $this->num($a->balance ?? 0),
                'is_active' => 1,
                'notes' => 'Imported from legacy bank accounts (legacy id ' . $a->id . ').',
                'created_at' => $a->created_at ?? $now,
                'updated_at' => $a->updated_at ?? $now,
                'deleted_at' => null,
            ]);
            $this->stats['bank_accounts']++;
        }
    }

    private function buildLookups(): void
    {
        $this->userIds = DB::table('users')->pluck('id')->flip()->all();
        $this->itemIds = DB::table('items')->pluck('id')->flip()->all();

        // Map legacy payment_type enum strings to a migrated payment_methods row.
        foreach (DB::table('payment_methods')->get(['id', 'name']) as $pm) {
            $this->paymentByKeyword[Str::lower($pm->name)] = $pm->id;
        }

        if (Schema::connection($this->legacy)->hasTable('general_expenses')) {
            $this->generalExpenseNames = DB::connection($this->legacy)
                ->table('general_expenses')->pluck('name', 'id')->all();
        }
    }

    private function loadDetailIndexes(): void
    {
        $legacy = DB::connection($this->legacy);

        $this->deposits = $legacy->table('transaction_deposits')
            ->get()->keyBy('transaction_id')->all();

        $this->txUsers = $legacy->table('transaction_users')
            ->get()->keyBy('transaction_id')->all();

        foreach ($legacy->table('transaction_expenses')->get() as $row) {
            $this->expenses[$row->transaction_id][] = $row;
        }
        foreach ($legacy->table('transaction_general_expenses')->get() as $row) {
            $this->generalExpenses[$row->transaction_id][] = $row;
        }
    }

    // ---------------------------------------------------------------------
    // Ledger migration (computes running_balance per account)
    // ---------------------------------------------------------------------

    private function migrateLedger(): void
    {
        // Process account-by-account so running_balance accumulates correctly.
        $accountIds = DB::connection($this->legacy)->table('transactions')
            ->distinct()->pluck('transactions_accounts_id')->all();

        // Ensure the NULL-account bucket is handled too.
        if (! in_array(null, $accountIds, true)) {
            $accountIds[] = null;
        }

        $bar = $this->output->createProgressBar(count($accountIds));
        $bar->start();

        foreach ($accountIds as $accId) {
            $balance = 0.0;

            $query = DB::connection($this->legacy)->table('transactions')
                ->orderBy('date')->orderBy('id');

            $accId === null
                ? $query->whereNull('transactions_accounts_id')
                : $query->where('transactions_accounts_id', $accId);

            $query->chunk((int) $this->option('chunk'), function ($txns) use (&$balance, $accId) {
                foreach ($txns as $t) {
                    $balance = $this->migrateTransaction($t, $accId, $balance);
                }
            });

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function migrateTransaction($t, ?int $accId, float $balance): float
    {
        $type = $this->classify($t);
        $direction = ((int) $t->type === 1) ? 'in' : 'out';

        $gross = $this->num($t->gross ?? $t->amount);
        $fee = $this->num($t->fee ?? 0);
        $net = $this->num($t->amount);

        $balance += ($direction === 'in') ? $net : -$net;

        DB::table('transactions')->insert([
            'id' => $t->id,
            'account_id' => $accId, // preserved id or NULL (relaxed)
            'transaction_type' => $type,
            'direction' => $direction,
            'currency_id' => $this->defaultCurrencyId,
            'gross_amount' => $gross,
            'fee_amount' => $fee,
            'net_amount' => $net,
            'running_balance' => round($balance, 2),
            'transaction_date' => $t->date,
            'reference_number' => $t->bank_transaction_id ? (string) $t->bank_transaction_id : null,
            'description' => $this->description($t, $type),
            'notes' => $this->buildNotes($t),
            'payment_method_id' => $this->resolvePaymentMethod($t->payment_type ?? null),
            'created_by' => $this->validUser($t->user_id),
            'is_reconciled' => ! empty($t->bank_transaction_id) ? 1 : 0,
            'created_at' => $t->created_at ?? now(),
            'updated_at' => $t->updated_at ?? now(),
            'deleted_at' => $t->deleted_at ?? null,
        ]);
        $this->stats['transactions']++;

        match ($type) {
            'donation'        => $this->insertDonation($t),
            'campaign_expense'=> $this->insertCampaignExpenses($t),
            'general_expense' => $this->insertGeneralExpenses($t),
            default           => null, // bank_transfer / adjustment: no detail row
        };

        return $balance;
    }

    // ---------------------------------------------------------------------
    // Bank statement ledger -> transactions (type = bank_transfer)
    // ---------------------------------------------------------------------

    /**
     * Migrate legacy `bank_transactions`.
     *
     *  - Rows already linked to a ledger transaction (transaction_id NOT NULL)
     *    are the bank-statement mirror of money we already migrated. By default
     *    they are NOT re-inserted (that would double-count); instead the linked
     *    transaction is marked reconciled. Pass --include-reconciled-bank to
     *    import them anyway.
     *  - Bank-only rows (transaction_id NULL) become new `bank_transfer`
     *    transactions. Their ids are offset (bank ids share the 1.. space with
     *    ledger ids), with the legacy id preserved in reference_number / notes.
     *  - running_balance is taken from the bank's own `balance` column rather
     *    than recomputed; these rows live on their own (bank) accounts.
     */
    private function migrateBankTransactions(): void
    {
        if (! Schema::connection($this->legacy)->hasTable('bank_transactions')) {
            return;
        }

        $includeLinked = (bool) $this->option('include-reconciled-bank');
        $idOffset = (int) (DB::table('transactions')->max('id') ?? 0);

        DB::connection($this->legacy)->table('bank_transactions')
            ->orderBy('id')
            ->chunk((int) $this->option('chunk'), function ($rows) use ($includeLinked, $idOffset) {
                foreach ($rows as $b) {
                    // Skip rows the org flagged to ignore.
                    if (! empty($b->is_ignore)) {
                        $this->stats['skipped']++;
                        continue;
                    }

                    // Linked to an existing ledger transaction -> reconcile, don't duplicate.
                    if (! empty($b->transaction_id)) {
                        DB::table('transactions')
                            ->where('id', $b->transaction_id)
                            ->update([
                                'is_reconciled' => ! empty($b->completed) ? 1 : 0,
                                'reference_number' => DB::raw(
                                    'COALESCE(reference_number, ' .
                                    DB::getPdo()->quote((string) ($b->invoice_number ?: $b->check_slip ?: ('BANK#' . $b->id))) .
                                    ')'
                                ),
                            ]);
                        $this->stats['bank_reconciled']++;

                        if (! $includeLinked) {
                            continue;
                        }
                    }

                    $this->insertBankTransaction($b, $idOffset);
                }
            });
    }

    private function insertBankTransaction($b, int $idOffset): void
    {
        // Direction: prefer the type flag; fall back to the sign of the amount.
        $direction = $b->type === null
            ? (((float) $b->amount) >= 0 ? 'in' : 'out')
            : (((int) $b->type === 1) ? 'in' : 'out');

        $amount = abs($this->num($b->amount));
        $accountId = $b->account_id !== null
            ? ($this->bankAccountMap[(int) $b->account_id] ?? null)
            : null;

        $reference = $b->invoice_number ?: $b->check_slip ?: ('BANK#' . $b->id);

        DB::table('transactions')->insert([
            'id' => $idOffset + (int) $b->id,
            'account_id' => $accountId,
            'transaction_type' => 'bank_transfer',
            'direction' => $direction,
            'currency_id' => $this->defaultCurrencyId,
            'gross_amount' => $amount,
            'fee_amount' => 0,
            'net_amount' => $amount,
            'running_balance' => $this->num($b->balance ?? 0),
            'transaction_date' => $b->date,
            'reference_number' => $this->truncate($reference, 255),
            'description' => $this->truncate((string) ($b->description ?: 'Legacy bank transaction'), 255),
            'notes' => $this->bankNotes($b),
            'payment_method_id' => null,
            'created_by' => null,
            'is_reconciled' => ! empty($b->completed) ? 1 : 0,
            'created_at' => $b->created_at ?? now(),
            'updated_at' => $b->updated_at ?? now(),
            'deleted_at' => null,
        ]);
        $this->stats['bank_transactions']++;
    }

    private function bankNotes($b): ?string
    {
        $parts = ['Legacy bank_transaction #' . $b->id];
        if (! empty($b->details)) $parts[] = (string) $b->details;
        if (! empty($b->note))    $parts[] = (string) $b->note;
        if (! empty($b->invoice)) $parts[] = 'Invoice: ' . $b->invoice;
        return implode("\n", $parts);
    }
    /*
     *
     * The authoritative signal is the legacy `transactions.type` column
     * (1 = deposit / income, 0 = expense / outflow). The detail tables are only
     * used to REFINE an expense into itemized (campaign) vs general — they are
     * sparse in production, so they must never be the sole basis for the type.
     */
    private function classify($t): string
    {
        if ((int) $t->type === 1) {
            return 'donation'; // any deposit/income row
        }

        // Expense (type = 0): refine the subtype from the detail tables.
        if (isset($this->expenses[$t->id])) {
            return 'campaign_expense';     // has itemized line-items
        }
        // General-expense pivot, OR no detail rows at all (un-itemized expense).
        return 'general_expense';
    }

    private function insertDonation($t): void
    {
        // donor_id -> users.id ONLY when the legacy user_id is a real migrated
        // user; standalone legacy `donors` are NOT users, so they become NULL
        // (per the agreed messy-data strategy). campaign_id is left NULL.
        DB::table('donations')->insert([
            'transaction_id' => $t->id,
            'donor_id' => $this->validUser($t->user_id),
            'campaign_id' => null,
            'is_general' => (int) ($t->is_public_donation ?? (empty($t->general_purpose_id) ? 1 : 0)),
            'purpose_note' => $this->truncate($t->note, 255),
            'stripe_payment_intent_id' => null,
            'stripe_charge_id' => null,
            'stripe_status' => null,
            'donor_covers_fee' => 0,
            'created_at' => $t->created_at ?? now(),
            'updated_at' => $t->updated_at ?? now(),
            'deleted_at' => $t->deleted_at ?? null,
        ]);
        $this->stats['donations']++;
    }

    private function insertCampaignExpenses($t): void
    {
        foreach ($this->expenses[$t->id] ?? [] as $e) {
            $price = $this->num($e->price);
            $amount = $this->num($e->amount);
            $qty = $price > 0 ? round($amount / $price, 2) : $amount;
            $residualAmount = $this->num($t->Residual ?? 0);
            $residualQty = $price > 0 ? round($residualAmount / $price, 2) : 0;

            DB::table('campaign_expenses')->insert([
                'transaction_id' => $t->id,
                'campaign_id' => null,                       // relaxed -> NULL
                'item_id' => isset($this->itemIds[$e->item_id]) ? $e->item_id : null,
                'item_price' => $price,
                'quantity' => $qty,
                'amount' => $amount,
                'residual_quantity' => $residualQty,
                'residual_amount' => $residualAmount,
                'responsible_user_id' => $this->validUser($e->user_id),
                'expense_date' => $t->date,
                'notes' => $this->truncate($e->invoice ?? null, 65535),
                'created_at' => $e->created_at ?? now(),
                'updated_at' => $e->updated_at ?? now(),
                'deleted_at' => $e->deleted_at ?? null,
            ]);
            $this->stats['campaign_expenses']++;
        }
    }

    private function insertGeneralExpenses($t): void
    {
        $rows = $this->generalExpenses[$t->id] ?? [];

        // Un-itemized expense (no legacy pivot rows): synthesize one detail row
        // from the parent so the transaction is never left without a detail.
        if (empty($rows)) {
            $name = trim((string) ($t->note ?? '')) ?: ('Legacy expense #' . $t->id);
            $this->writeGeneralExpense($t, $name, null);
            return;
        }

        foreach ($rows as $g) {
            $name = $this->generalExpenseNames[$g->general_expense_id] ?? 'Legacy general expense';
            $this->writeGeneralExpense($t, $name, $g);
        }
    }

    private function writeGeneralExpense($t, string $name, $g): void
    {
        DB::table('general_expenses')->insert([
            'transaction_id' => $t->id,
            'category_id' => null,
            'name' => $this->truncate($name, 255),
            'amount' => $this->num($t->amount),
            'expense_date' => $t->date,
            'vendor_name' => null,
            'is_recurring' => 0,
            'created_by' => $this->validUser($t->user_id),
            'notes' => $this->buildNotes($t),
            'created_at' => $g->created_at ?? $t->created_at ?? now(),
            'updated_at' => $g->updated_at ?? $t->updated_at ?? now(),
            'deleted_at' => $g->deleted_at ?? null,
        ]);
        $this->stats['general_expenses']++;
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function validUser($id): ?int
    {
        return ($id && isset($this->userIds[$id])) ? (int) $id : null;
    }

    private function resolvePaymentMethod(?string $paymentType): ?int
    {
        if (! $paymentType) {
            return null;
        }
        $needle = Str::lower($paymentType);

        // Direct name hit first.
        if (isset($this->paymentByKeyword[$needle])) {
            return $this->paymentByKeyword[$needle];
        }
        // Best-effort keyword match (sheque->cheque, zelle, credit card, in_kind).
        foreach ($this->paymentByKeyword as $name => $id) {
            if (str_contains($name, $needle) || str_contains($needle, $name)) {
                return $id;
            }
        }
        return null; // unmapped: raw value is preserved in notes
    }

    private function description($t, string $type): string
    {
        $note = trim((string) ($t->note ?? ''));
        if ($note !== '') {
            return $this->truncate($note, 255);
        }
        return ucfirst(str_replace('_', ' ', $type)) . ' #' . $t->id . ' (legacy import)';
    }

    private function buildNotes($t): ?string
    {
        $parts = [];
        if (! empty($t->note))         $parts[] = (string) $t->note;
        if (! empty($t->payment_type)) $parts[] = 'Legacy payment type: ' . $t->payment_type;
        if (! empty($t->invoice))      $parts[] = 'Legacy invoice ref: ' . $t->invoice;
        return $parts ? implode("\n", $parts) : null;
    }

    private function num($value): float
    {
        return round((float) ($value ?? 0), 2);
    }

    private function truncate(?string $value, int $max): ?string
    {
        if ($value === null) {
            return null;
        }
        return Str::limit($value, $max, '');
    }

    private function printSummary(): void
    {
        $this->newLine();
        $this->info('Import summary');
        $this->table(['Table', 'Rows'], collect($this->stats)
            ->map(fn ($v, $k) => [$k, $v])->values()->all());
    }
}