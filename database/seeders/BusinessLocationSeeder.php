<?php

namespace Database\Seeders;

use App\Models\BusinessLocation;
use Illuminate\Database\Seeder;

class BusinessLocationSeeder extends Seeder
{
    public function run(): void
    {
        BusinessLocation::firstOrCreate(
            ['code' => 'HO001'],
            [
                'name'       => 'Saras Jewellery - Head Office',
                'email'      => 'info@sarasjewellery.com',
                'phone'      => '+91 99999 99999',
                'address'    => 'No.1, Main Street',
                'city'       => 'Chennai',
                'state'      => 'Tamil Nadu',
                'country'    => 'India',
                'pincode'    => '600001',
                'currency'   => 'INR',
                'status'     => 'active',
                'is_default' => true,
            ]
        );

        $this->command->info('✅ Business Location seeded!');
    }
}