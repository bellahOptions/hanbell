<?php

namespace Database\Seeders;

use App\Enums\TwoFactorMethod;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo accounts.
 *
 * The passwords below are intentionally weak and publicly documented so the
 * demo can be logged into. They must never be used on a live install — the
 * README and the admin dashboard both say so.
 */
class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@hanbellshop.demo'],
            [
                'name' => 'Hanbell Administrator',
                'password' => Hash::make('password'),
                'phone' => '+2348000000001',
                'email_verified_at' => now(),
                'two_factor_method' => TwoFactorMethod::None,
                'locale' => 'en',
            ],
        );

        $admin->assignRole('admin');

        $customer = User::updateOrCreate(
            ['email' => 'customer@hanbellshop.demo'],
            [
                'name' => 'Ada Okonkwo',
                'password' => Hash::make('password'),
                'phone' => '+2348000000002',
                'email_verified_at' => now(),
                'two_factor_method' => TwoFactorMethod::None,
                'locale' => 'en',
            ],
        );

        $customer->assignRole('customer');

        // A second customer with two-factor switched on, so the challenge screen
        // can be exercised without setting it up by hand.
        $secured = User::updateOrCreate(
            ['email' => 'secured@hanbellshop.demo'],
            [
                'name' => 'Tunde Bakare',
                'password' => Hash::make('password'),
                'phone' => '+2348000000003',
                'email_verified_at' => now(),
                'two_factor_method' => TwoFactorMethod::Eotp,
                'two_factor_confirmed_at' => now(),
                'locale' => 'en',
            ],
        );

        $secured->assignRole('customer');

        if ($customer->addresses()->doesntExist()) {
            $customer->addresses()->create([
                'label' => 'Home',
                'recipient_name' => $customer->name,
                'phone' => '+2348000000002',
                'line1' => '14 Adeola Odeku Street',
                'line2' => 'Flat 3B',
                'city' => 'Victoria Island',
                'state' => 'Lagos',
                'postal_code' => '101241',
                'country' => 'NG',
                'is_default' => true,
            ]);

            $customer->addresses()->create([
                'label' => 'Office',
                'recipient_name' => $customer->name,
                'phone' => '+2348000000002',
                'line1' => '1 Commercial Avenue',
                'city' => 'Yaba',
                'state' => 'Lagos',
                'postal_code' => '101245',
                'country' => 'NG',
                'is_default' => false,
            ]);
        }
    }
}
