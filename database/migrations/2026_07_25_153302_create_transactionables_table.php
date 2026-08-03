<?php

use App\Models\Campaign;
use App\Models\GeneralExpense;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactionables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')
                ->constrained('transactions')
                ->cascadeOnDelete();
            $table->morphs('transactionable');
            $table->timestamps();

            $table->unique(
                ['transaction_id', 'transactionable_type', 'transactionable_id'],
                'transactionables_unique'
            );
        });

        $now = now();

        $generalExpenseRows = DB::table('general_expenses')
            ->whereNotNull('transaction_id')
            ->get(['id', 'transaction_id']);

        foreach ($generalExpenseRows as $row) {
            DB::table('transactionables')->insert([
                'transaction_id' => $row->transaction_id,
                'transactionable_type' => GeneralExpense::class,
                'transactionable_id' => $row->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $transferCampaignRows = DB::table('transfers')
            ->whereNotNull('campaign_id')
            ->whereNotNull('transaction_id')
            ->get(['transaction_id', 'campaign_id']);

        foreach ($transferCampaignRows as $row) {
            $exists = DB::table('transactionables')
                ->where('transaction_id', $row->transaction_id)
                ->where('transactionable_type', Campaign::class)
                ->where('transactionable_id', $row->campaign_id)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('transactionables')->insert([
                'transaction_id' => $row->transaction_id,
                'transactionable_type' => Campaign::class,
                'transactionable_id' => $row->campaign_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->rebuildGeneralExpensesWithoutTransactionId();
            $this->rebuildTransfersWithoutCampaignId();
        } else {
            Schema::table('general_expenses', function (Blueprint $table) {
                $table->dropConstrainedForeignId('transaction_id');
            });

            Schema::table('transfers', function (Blueprint $table) {
                $table->dropForeign(['campaign_id']);

                $indexNames = collect(Schema::getIndexes('transfers'))->pluck('name');
                if ($indexNames->contains('transfers_campaign_id_index')) {
                    $table->dropIndex('transfers_campaign_id_index');
                }

                $table->dropColumn('campaign_id');
            });
        }
    }

    private function rebuildGeneralExpensesWithoutTransactionId(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('general_expenses_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('general_expense_categories')
                ->nullOnDelete();
            $table->string('name');
            $table->float('amount', 2);
            $table->date('expense_date');
            $table->string('vendor_name')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('expense_date');
            $table->index('is_recurring');
        });

        $rows = DB::table('general_expenses')->get();

        foreach ($rows as $row) {
            DB::table('general_expenses_new')->insert([
                'id' => $row->id,
                'category_id' => $row->category_id,
                'name' => $row->name,
                'amount' => $row->amount,
                'expense_date' => $row->expense_date,
                'vendor_name' => $row->vendor_name,
                'is_recurring' => $row->is_recurring,
                'created_by' => $row->created_by,
                'notes' => $row->notes,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);
        }

        Schema::drop('general_expenses');
        Schema::rename('general_expenses_new', 'general_expenses');
        Schema::enableForeignKeyConstraints();
    }

    private function rebuildTransfersWithoutCampaignId(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('transfers_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')
                ->unique()
                ->constrained('transactions')
                ->cascadeOnDelete();
            $table->string('recipient_label')->nullable();
            $table->string('recipient_type')->nullable();
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->float('amount', 2);
            $table->date('transfer_date');
            $table->string('purpose');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('transfer_date');
            $table->index(['recipient_type', 'recipient_id']);
        });

        $rows = DB::table('transfers')->get();

        foreach ($rows as $row) {
            DB::table('transfers_new')->insert([
                'id' => $row->id,
                'transaction_id' => $row->transaction_id,
                'recipient_label' => $row->recipient_label,
                'recipient_type' => $row->recipient_type,
                'recipient_id' => $row->recipient_id,
                'recipient_phone' => $row->recipient_phone,
                'amount' => $row->amount,
                'transfer_date' => $row->transfer_date,
                'purpose' => $row->purpose,
                'notes' => $row->notes,
                'created_by' => $row->created_by,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);
        }

        Schema::drop('transfers');
        Schema::rename('transfers_new', 'transfers');
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->foreignId('campaign_id')
                ->nullable()
                ->after('transaction_id')
                ->constrained('campaigns')
                ->nullOnDelete();
        });

        Schema::table('general_expenses', function (Blueprint $table) {
            $table->foreignId('transaction_id')
                ->nullable()
                ->unique()
                ->after('id')
                ->constrained('transactions')
                ->cascadeOnDelete();
        });

        $campaignLinks = DB::table('transactionables')
            ->where('transactionable_type', Campaign::class)
            ->get(['transaction_id', 'transactionable_id']);

        foreach ($campaignLinks as $link) {
            DB::table('transfers')
                ->where('transaction_id', $link->transaction_id)
                ->whereNull('campaign_id')
                ->update(['campaign_id' => $link->transactionable_id]);
        }

        $generalExpenseLinks = DB::table('transactionables')
            ->where('transactionable_type', GeneralExpense::class)
            ->get(['transaction_id', 'transactionable_id']);

        foreach ($generalExpenseLinks as $link) {
            DB::table('general_expenses')
                ->where('id', $link->transactionable_id)
                ->whereNull('transaction_id')
                ->update(['transaction_id' => $link->transaction_id]);
        }

        Schema::dropIfExists('transactionables');
    }
};
