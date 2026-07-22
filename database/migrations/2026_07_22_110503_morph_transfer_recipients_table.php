<?php

use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replace transfer recipient enum + FKs with a morph to User/Beneficiary
 * plus optional free-text recipient_label for one-off payees.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->string('recipient_label')->nullable()->after('campaign_id');
            $table->string('morph_recipient_type')->nullable()->after('recipient_label');
            $table->unsignedBigInteger('morph_recipient_id')->nullable()->after('morph_recipient_type');
        });

        $userClass = User::class;
        $beneficiaryClass = Beneficiary::class;

        foreach (DB::table('transfers')->cursor() as $row) {
            $updates = [
                'morph_recipient_type' => null,
                'morph_recipient_id' => null,
                'recipient_label' => null,
            ];

            if ($row->recipient_type === 'user' && $row->user_id) {
                $updates['morph_recipient_type'] = $userClass;
                $updates['morph_recipient_id'] = $row->user_id;
            } elseif ($row->recipient_type === 'beneficiary' && $row->beneficiary_id) {
                $updates['morph_recipient_type'] = $beneficiaryClass;
                $updates['morph_recipient_id'] = $row->beneficiary_id;
            } else {
                $updates['recipient_label'] = $row->recipient_name;
            }

            DB::table('transfers')->where('id', $row->id)->update($updates);
        }

        Schema::table('transfers', function (Blueprint $table) {
            $table->dropIndex(['recipient_type']);
            $table->dropForeign(['beneficiary_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('transfers', function (Blueprint $table) {
            $table->dropColumn(['recipient_type', 'recipient_name', 'beneficiary_id', 'user_id']);
        });

        Schema::table('transfers', function (Blueprint $table) {
            $table->renameColumn('morph_recipient_type', 'recipient_type');
            $table->renameColumn('morph_recipient_id', 'recipient_id');
        });

        Schema::table('transfers', function (Blueprint $table) {
            $table->index(['recipient_type', 'recipient_id']);
        });
    }

    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropIndex(['recipient_type', 'recipient_id']);
        });

        Schema::table('transfers', function (Blueprint $table) {
            $table->renameColumn('recipient_type', 'morph_recipient_type');
            $table->renameColumn('recipient_id', 'morph_recipient_id');
        });

        Schema::table('transfers', function (Blueprint $table) {
            $table->string('recipient_type')->nullable()->after('campaign_id');
            $table->string('recipient_name')->nullable()->after('recipient_type');
            $table->foreignId('beneficiary_id')->nullable()->constrained('beneficiaries')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
        });

        $userClass = User::class;
        $beneficiaryClass = Beneficiary::class;

        foreach (DB::table('transfers')->cursor() as $row) {
            $updates = [
                'recipient_type' => 'other',
                'recipient_name' => $row->recipient_label,
                'beneficiary_id' => null,
                'user_id' => null,
            ];

            if ($row->morph_recipient_type === $userClass && $row->morph_recipient_id) {
                $updates['recipient_type'] = 'user';
                $updates['user_id'] = $row->morph_recipient_id;
            } elseif ($row->morph_recipient_type === $beneficiaryClass && $row->morph_recipient_id) {
                $updates['recipient_type'] = 'beneficiary';
                $updates['beneficiary_id'] = $row->morph_recipient_id;
            }

            DB::table('transfers')->where('id', $row->id)->update($updates);
        }

        Schema::table('transfers', function (Blueprint $table) {
            $table->dropColumn(['recipient_label', 'morph_recipient_type', 'morph_recipient_id']);
            $table->index('recipient_type');
        });
    }
};
