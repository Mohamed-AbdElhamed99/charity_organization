<?php

use App\Models\TransactionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        foreach (TransactionType::systemDefinitions() as $definition) {
            DB::table('transaction_types')->insert([
                ...$definition,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('transaction_type_id')
                ->nullable()
                ->after('account_id')
                ->constrained('transaction_types')
                ->restrictOnDelete();
        });

        $types = DB::table('transaction_types')->pluck('id', 'code');

        foreach ($types as $code => $id) {
            DB::table('transactions')
                ->where('transaction_type', $code)
                ->update(['transaction_type_id' => $id]);
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['transaction_type']);
            $table->dropIndex(['account_id', 'transaction_type']);
            $table->dropColumn('transaction_type');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('transaction_type_id')->nullable(false)->change();
            $table->index('transaction_type_id');
            $table->index(['account_id', 'transaction_type_id']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('transaction_type', [
                'donation',
                'campaign_expense',
                'general_expense',
                'transfer',
                'bank_transfer',
                'adjustment',
            ])->nullable()->after('account_id');
        });

        $types = DB::table('transaction_types')->pluck('code', 'id');

        foreach ($types as $id => $code) {
            DB::table('transactions')
                ->where('transaction_type_id', $id)
                ->update(['transaction_type' => $code]);
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['transaction_type_id']);
            $table->dropIndex(['account_id', 'transaction_type_id']);
            $table->dropConstrainedForeignId('transaction_type_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('transaction_type', [
                'donation',
                'campaign_expense',
                'general_expense',
                'transfer',
                'bank_transfer',
                'adjustment',
            ])->nullable(false)->change();
            $table->index('transaction_type');
            $table->index(['account_id', 'transaction_type']);
        });

        Schema::dropIfExists('transaction_types');
    }
};
