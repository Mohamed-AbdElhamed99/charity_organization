<?php

namespace Database\Seeders;

use App\Models\TransactionType;
use Illuminate\Database\Seeder;

class TransactionTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (TransactionType::systemDefinitions() as $definition) {
            TransactionType::query()->firstOrCreate(
                ['code' => $definition['code']],
                $definition,
            );
        }
    }
}
