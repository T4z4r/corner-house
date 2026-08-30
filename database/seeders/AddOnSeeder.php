<?php

namespace Database\Seeders;

use App\Models\AddOn;
use Illuminate\Database\Seeder;

class AddOnSeeder extends Seeder
{
    public function run(): void
    {
        $addons = [
            [
                'name' => 'Classic Drinks Package',
                'slug' => 'classic-drinks-package',
                'description' => 'A selection of wines, beers, and soft drinks to welcome your group on arrival. Includes 2 bottles of wine, 12 craft beers, and a selection of soft drinks.',
                'category' => 'drinks',
                'price' => 75.00,
                'unit' => 'package',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Premium Drinks Package',
                'slug' => 'premium-drinks-package',
                'description' => 'An upgraded selection featuring premium wines, craft gins, and artisan soft drinks. Includes 4 bottles of wine, 6 craft gins & mixers, 12 craft beers, and premium soft drinks.',
                'category' => 'drinks',
                'price' => 150.00,
                'unit' => 'package',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Breakfast Hamper',
                'slug' => 'breakfast-hamper',
                'description' => 'A traditional English breakfast hamper sourced from Braunston Butcher. Includes fresh sausages, bacon, eggs, bread, butter, and juice.',
                'category' => 'food',
                'price' => 35.00,
                'unit' => 'hamper',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Afternoon Tea Basket',
                'slug' => 'afternoon-tea-basket',
                'description' => 'A delightful afternoon tea basket with freshly baked scones, clotted cream, selection of teas, and homemade preserves.',
                'category' => 'food',
                'price' => 30.00,
                'unit' => 'basket',
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($addons as $addon) {
            AddOn::firstOrCreate(['slug' => $addon['slug']], $addon);
        }
    }
}
