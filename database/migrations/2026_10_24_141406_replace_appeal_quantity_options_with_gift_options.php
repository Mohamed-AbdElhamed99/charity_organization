<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Appeals used to sell one unit at one price, chosen by quantity.
 * Each choice is now its own named gift with its own price.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_appeals', function (Blueprint $table) {
            $table->json('gift_options')
                ->nullable()
                ->after('subtitle_ar')
                ->comment('Named gifts, each with name_en, name_ar, and amount_cents');
        });

        DB::table('campaign_appeals')->orderBy('id')->each(function (object $appeal): void {
            DB::table('campaign_appeals')->where('id', $appeal->id)->update([
                'gift_options' => json_encode(
                    $this->giftOptionsFromQuantityAppeal($appeal),
                    JSON_UNESCAPED_UNICODE,
                ),
            ]);
        });

        Schema::table('campaign_appeals', function (Blueprint $table) {
            $table->dropColumn([
                'unit_amount_cents',
                'unit_label_en',
                'unit_label_ar',
                'unit_label_plural_en',
                'unit_label_plural_ar',
                'quantity_options',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('campaign_appeals', function (Blueprint $table) {
            $table->unsignedInteger('unit_amount_cents')->nullable();
            $table->string('unit_label_en')->nullable();
            $table->string('unit_label_ar')->nullable();
            $table->string('unit_label_plural_en')->nullable();
            $table->string('unit_label_plural_ar')->nullable();
            $table->json('quantity_options')->nullable();
        });

        DB::table('campaign_appeals')->orderBy('id')->each(function (object $appeal): void {
            $options = $this->decodeOptions($appeal->gift_options);
            $first = $options[0] ?? null;

            DB::table('campaign_appeals')->where('id', $appeal->id)->update([
                'unit_amount_cents' => $first['amount_cents'] ?? 0,
                'unit_label_en' => $first['name_en'] ?? 'gift',
                'unit_label_ar' => $first['name_ar'] ?? 'هدية',
                'unit_label_plural_en' => $first['name_en'] ?? 'gifts',
                'unit_label_plural_ar' => $first['name_ar'] ?? 'هدايا',
                'quantity_options' => json_encode([1]),
            ]);
        });

        Schema::table('campaign_appeals', function (Blueprint $table) {
            $table->dropColumn('gift_options');
        });
    }

    /**
     * A quantity of 3 at $15 becomes one option named from that unit, priced at $45.
     *
     * @return list<array{name_en: string, name_ar: string, amount_cents: int}>
     */
    private function giftOptionsFromQuantityAppeal(object $appeal): array
    {
        $quantities = $this->decodeOptions($appeal->quantity_options);
        $quantities = array_values(array_filter(
            array_map(fn (mixed $quantity) => (int) $quantity, $quantities),
            fn (int $quantity) => $quantity > 0,
        ));

        if ($quantities === []) {
            $quantities = [1];
        }

        $unitCents = (int) $appeal->unit_amount_cents;

        return array_map(function (int $quantity) use ($appeal, $unitCents) {
            $nameEn = $quantity === 1 ? $appeal->unit_label_en : $appeal->unit_label_plural_en;
            $nameAr = $quantity === 1 ? $appeal->unit_label_ar : $appeal->unit_label_plural_ar;

            return [
                'name_en' => trim($quantity.' '.$nameEn),
                'name_ar' => trim($quantity.' '.$nameAr),
                'amount_cents' => $quantity * $unitCents,
            ];
        }, $quantities);
    }

    /**
     * @return list<mixed>
     */
    private function decodeOptions(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
};
