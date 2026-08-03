<?php

use App\Models\Beneficiary;
use App\Models\Campaign;
use App\Models\GeneralExpense;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transactionables')) {
            $this->remap('transactionables', 'transactionable_type', [
                'user' => User::class,
                'beneficiary' => Beneficiary::class,
                'campaign' => Campaign::class,
                'meeting' => Meeting::class,
                'general_expense' => GeneralExpense::class,
            ]);
        }

        if (Schema::hasTable('transfers')) {
            $this->remap('transfers', 'recipient_type', [
                'user' => User::class,
                'beneficiary' => Beneficiary::class,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transactionables')) {
            $this->remap('transactionables', 'transactionable_type', [
                User::class => 'user',
                Beneficiary::class => 'beneficiary',
                Campaign::class => 'campaign',
                Meeting::class => 'meeting',
                GeneralExpense::class => 'general_expense',
            ]);
        }

        if (Schema::hasTable('transfers')) {
            $this->remap('transfers', 'recipient_type', [
                User::class => 'user',
                Beneficiary::class => 'beneficiary',
            ]);
        }
    }

    /**
     * @param  array<string, string>  $map
     */
    private function remap(string $table, string $column, array $map): void
    {
        foreach ($map as $from => $to) {
            DB::table($table)->where($column, $from)->update([$column => $to]);
        }
    }
};
