<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed minimal yang aman dijalankan berulang kali.
     */
    public function run(): void
    {
        $this->call([
            OrganizationalFieldSeeder::class,
            CommitteePositionSeeder::class,
            PermissionSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
