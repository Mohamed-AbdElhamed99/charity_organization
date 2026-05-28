<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    private const ITEMS = [
        ['name_ar' => 'صندوق طعام',        'name_en' => 'Food Box',            'unit' => 'Box'],
        ['name_ar' => 'حقيبة ملابس',        'name_en' => 'Clothing Bag',        'unit' => 'Bag'],
        ['name_ar' => 'دواء',               'name_en' => 'Medicine',            'unit' => 'Pack'],
        ['name_ar' => 'مستلزمات طبية',      'name_en' => 'Medical Supplies',    'unit' => 'Set'],
        ['name_ar' => 'مستلزمات مدرسية',    'name_en' => 'School Supplies',     'unit' => 'Set'],
        ['name_ar' => 'بطانية',             'name_en' => 'Blanket',             'unit' => 'Piece'],
        ['name_ar' => 'مراتب',              'name_en' => 'Mattress',            'unit' => 'Piece'],
        ['name_ar' => 'مستلزمات النظافة',   'name_en' => 'Hygiene Kit',         'unit' => 'Kit'],
        ['name_ar' => 'حليب أطفال',         'name_en' => 'Baby Formula',        'unit' => 'Can'],
        ['name_ar' => 'حفاضات',             'name_en' => 'Diapers',             'unit' => 'Pack'],
        ['name_ar' => 'مواد بناء',          'name_en' => 'Construction Materials', 'unit' => 'Kg'],
        ['name_ar' => 'أجهزة كهربائية',     'name_en' => 'Electrical Appliances', 'unit' => 'Piece'],
        ['name_ar' => 'أثاث',              'name_en' => 'Furniture',           'unit' => 'Piece'],
    ];

    public function definition(): array
    {
        $item = fake()->unique()->randomElement(static::ITEMS);

        return [
            'name_ar'     => $item['name_ar'],
            'name_en'     => $item['name_en'],
            'description' => fake()->optional(0.4)->sentence(),
            'unit'        => $item['unit'],
            'is_active'   => true,
        ];
    }
}
