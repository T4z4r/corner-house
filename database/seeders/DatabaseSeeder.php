<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@cornerhouse.example'],
            [
                'name' => 'Corner House Admin',
                'password' => Hash::make('password'),
            ],
        );

        if (! $superAdmin->hasRole('Super Admin')) {
            $superAdmin->assignRole('Super Admin');
        }

        $this->call(SettingsSeeder::class);
        $this->call(PropertySeeder::class);
        $this->call(RoomSeeder::class);
        $this->call(PricingSeeder::class);
        $this->call(AmenitySeeder::class);
        $this->call(FoodAndDrinkSeeder::class);
        $this->call(PlacesOfInterestSeeder::class);
        $this->call(AddOnSeeder::class);
        $this->call(ReviewSeeder::class);
        $this->call(CommunicationTemplateSeeder::class);
        $this->call(KnowledgeBaseSeeder::class);
    }
}
