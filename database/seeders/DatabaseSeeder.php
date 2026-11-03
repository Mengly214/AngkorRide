<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Note: deliberately NOT using WithoutModelEvents here — the
     * UUID-generation hooks on User/Business/Vehicle/VehicleType rely
     * on the `creating` model event to assign primary keys.
     */
    public function run(): void
    {
        $vehicleTypes = collect([
            'Scooter (Automatic)' => ['passenger_capacity' => 2, 'requires_license' => false],
            'Motorbike (Semi-Auto)' => ['passenger_capacity' => 2, 'requires_license' => true],
            'Electric Bike' => ['passenger_capacity' => 1, 'requires_license' => false],
            'Touring & Adventure' => ['passenger_capacity' => 2, 'requires_license' => true],
        ])->map(function ($attrs, $name) {
            return VehicleType::firstOrCreate(
                ['name' => $name],
                $attrs + ['is_active' => true]
            );
        });

        $owner = User::firstOrCreate(
            ['phone' => '85512345678'],
            [
                'full_name' => 'Sokha Chea',
                'email' => 'sokha@angkormoto.example',
                'password_hash' => Hash::make('password'),
                'role' => 'business_admin',
                'is_verified' => true,
                'is_active' => true,
            ]
        );

        $business = Business::firstOrCreate(
            ['name' => 'Angkor Moto Rental'],
            [
                'owner_user_id' => $owner->id,
                'description' => 'Siem Reap Hub — scooters and motorbikes for exploring the Angkor temple circuit.',
                'address' => 'Central Wat Bo Rd, Sala Kamreuk',
                'city' => 'Siem Reap',
                'phone' => '85512345678',
                'email' => 'contact@angkormoto.example',
                'opening_time' => '07:00',
                'closing_time' => '19:00',
                'is_verified' => true,
                'is_active' => true,
            ]
        );

        $vehicles = [
            [
                'type' => 'Scooter (Automatic)',
                'plate_number' => 'SR-1001',
                'brand' => 'Honda',
                'model' => 'Click 125',
                'color' => 'Red',
                'year' => 2023,
                'mileage' => 4200,
                'condition_notes' => 'Recently serviced',
                'price_per_day' => 8,
                'price_per_hour' => 1.5,
                'pick_up_location' => 'Wat Bo District',
                'status' => 'available',
            ],
            [
                'type' => 'Scooter (Automatic)',
                'plate_number' => 'SR-1002',
                'brand' => 'Honda',
                'model' => 'Scoopy 110',
                'color' => 'White',
                'year' => 2022,
                'mileage' => 6100,
                'condition_notes' => 'Good condition',
                'price_per_day' => 9,
                'price_per_hour' => 1.5,
                'pick_up_location' => 'Old Market Area',
                'status' => 'available',
            ],
            [
                'type' => 'Touring & Adventure',
                'plate_number' => 'SR-1003',
                'brand' => 'Yamaha',
                'model' => 'NMAX 155',
                'color' => 'Matte Grey',
                'year' => 2024,
                'mileage' => 1500,
                'condition_notes' => 'Like new',
                'price_per_day' => 14,
                'price_per_hour' => 2.5,
                'pick_up_location' => 'Charles de Gaulle',
                'status' => 'available',
            ],
            [
                'type' => 'Motorbike (Semi-Auto)',
                'plate_number' => 'SR-1004',
                'brand' => 'Honda',
                'model' => 'Dream 125',
                'color' => 'Black',
                'year' => 2021,
                'mileage' => 12500,
                'condition_notes' => 'Reliable daily runner',
                'price_per_day' => 6,
                'price_per_hour' => 1,
                'pick_up_location' => 'Airport Link Road',
                'status' => 'available',
            ],
        ];

        foreach ($vehicles as $data) {
            $type = $vehicleTypes[$data['type']];
            unset($data['type']);

            Vehicle::firstOrCreate(
                ['plate_number' => $data['plate_number']],
                $data + [
                    'business_id' => $business->id,
                    'vehicle_type_id' => $type->id,
                ]
            );
        }
    }
}