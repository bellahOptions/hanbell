<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Full demo dataset for HanbellShop.
 *
 * Order matters: roles must exist before users are given them, and the catalogue
 * needs vendors and categories before it can create products.
 *
 * NOTE: `WithoutModelEvents` is deliberately NOT used here. The HasUuid trait
 * assigns a uuid in a `creating` model event, so muting model events during
 * seeding leaves `users.uuid` null and the insert fails on a NOT NULL
 * constraint. Anything that relies on model events must keep them enabled.
 *
 * Everything here is synthetic demonstration data. Real Unsplash photography is
 * used for imagery with attribution; the brands, products, prices and stock
 * figures are invented. See DemoCatalogueSeeder's docblock.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            DemoUsersSeeder::class,
            PageSeeder::class,
            DemoCatalogueSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('HanbellShop demo data seeded.');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Admin', 'admin@hanbellshop.demo', 'password'],
                ['Customer', 'customer@hanbellshop.demo', 'password'],
                ['Customer (two-factor on)', 'secured@hanbellshop.demo', 'password'],
            ],
        );
        $this->command->warn('These are demo credentials. Change them before any public deployment.');
    }
}
