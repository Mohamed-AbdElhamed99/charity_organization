<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Seeder;

/**
 * Seeds essential countries and states.
 * Focused on countries relevant to the org's operations.
 * Production: replace with full dataset from a geo package or CSV.
 */
class GeoSeeder extends Seeder
{
    private const GEO_DATA = [
        [
            'name'        => 'Egypt',
            'iso2'        => 'EG',
            'iso3'        => 'EGY',
            'phonecode'   => '+20',
            'currency'    => 'EGP',
            'currency_name'   => 'Egyptian Pound',
            'currency_symbol' => 'ج.م',
            'is_active'   => true,
            'states'      => [
                'Cairo', 'Giza', 'Alexandria', 'Luxor', 'Aswan',
                'Dakahlia', 'Sharqia', 'Gharbia', 'Menufia', 'Kafr El Sheikh',
                'Beheira', 'Ismailia', 'Port Said', 'Suez', 'Faiyum',
                'Beni Suef', 'Minya', 'Asyut', 'Sohag', 'Qena',
                'Damietta', 'Qalyubia', 'North Sinai', 'South Sinai', 'Red Sea',
                'Matruh', 'New Valley', 'Quesna',
            ],
        ],
        [
            'name'        => 'United States',
            'iso2'        => 'US',
            'iso3'        => 'USA',
            'phonecode'   => '+1',
            'currency'    => 'USD',
            'currency_name'   => 'US Dollar',
            'currency_symbol' => '$',
            'is_active'   => true,
            'states'      => [
                'Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California',
                'Colorado', 'Connecticut', 'Delaware', 'Florida', 'Georgia',
                'Hawaii', 'Idaho', 'Illinois', 'Indiana', 'Iowa',
                'Kansas', 'Kentucky', 'Louisiana', 'Maine', 'Maryland',
                'Massachusetts', 'Michigan', 'Minnesota', 'Mississippi', 'Missouri',
                'Montana', 'Nebraska', 'Nevada', 'New Hampshire', 'New Jersey',
                'New Mexico', 'New York', 'North Carolina', 'North Dakota', 'Ohio',
                'Oklahoma', 'Oregon', 'Pennsylvania', 'Rhode Island', 'South Carolina',
                'South Dakota', 'Tennessee', 'Texas', 'Utah', 'Vermont',
                'Virginia', 'Washington', 'West Virginia', 'Wisconsin', 'Wyoming',
            ],
        ],
        [
            'name'        => 'United Kingdom',
            'iso2'        => 'GB',
            'iso3'        => 'GBR',
            'phonecode'   => '+44',
            'currency'    => 'GBP',
            'currency_name'   => 'British Pound',
            'currency_symbol' => '£',
            'is_active'   => true,
            'states'      => [
                'England', 'Scotland', 'Wales', 'Northern Ireland',
            ],
        ],
        [
            'name'        => 'Saudi Arabia',
            'iso2'        => 'SA',
            'iso3'        => 'SAU',
            'phonecode'   => '+966',
            'currency'    => 'SAR',
            'currency_name'   => 'Saudi Riyal',
            'currency_symbol' => 'ر.س',
            'is_active'   => true,
            'states'      => [
                'Riyadh', 'Makkah', 'Madinah', 'Eastern Province',
                'Asir', 'Tabuk', 'Hail', 'Northern Borders',
                'Jizan', 'Najran', 'Al Bahah', 'Al Jawf', 'Qassim',
            ],
        ],
        [
            'name'        => 'Canada',
            'iso2'        => 'CA',
            'iso3'        => 'CAN',
            'phonecode'   => '+1',
            'currency'    => 'CAD',
            'currency_name'   => 'Canadian Dollar',
            'currency_symbol' => 'CA$',
            'is_active'   => true,
            'states'      => [
                'Alberta', 'British Columbia', 'Manitoba', 'New Brunswick',
                'Newfoundland and Labrador', 'Nova Scotia', 'Ontario',
                'Prince Edward Island', 'Quebec', 'Saskatchewan',
                'Northwest Territories', 'Nunavut', 'Yukon',
            ],
        ],
    ];

    public function run(): void
    {
        foreach (static::GEO_DATA as $countryData) {
            $states = $countryData['states'];
            unset($countryData['states']);

            $country = Country::firstOrCreate(
                ['iso2' => $countryData['iso2']],
                array_merge($countryData, ['name' => $countryData['name']])
            );

            // Bulk insert states for performance
            $stateRecords = collect($states)->map(fn (string $name) => [
                'name'       => $name,
                'country_id' => $country->id,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            // upsert to avoid duplicates on re-run
            State::upsert($stateRecords, ['name', 'country_id'], ['updated_at']);
        }

        $this->command->info('✅ Geo data seeded (' . Country::count() . ' countries, ' . State::count() . ' states).');
    }
}
