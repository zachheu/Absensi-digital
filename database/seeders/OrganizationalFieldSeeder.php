<?php

namespace Database\Seeders;

use App\Models\OrganizationalField;
use Illuminate\Database\Seeder;

class OrganizationalFieldSeeder extends Seeder
{
    /**
     * Empat bidang organisasi HIMATIF ditambah unit Staff Inti untuk
     * jabatan struktural. Kode dipakai sebagai nama karena kepanjangan
     * resmi belum ditetapkan.
     */
    public function run(): void
    {
        $fields = [
            ['code' => 'HRDO', 'name' => 'HRDO'],
            ['code' => 'RE', 'name' => 'RE'],
            ['code' => 'PR', 'name' => 'PR'],
            ['code' => 'CNB', 'name' => 'CNB'],
            ['code' => 'STAFF_INTI', 'name' => 'Staff Inti', 'description' => 'Unit pimpinan himpunan'],
        ];

        foreach ($fields as $field) {
            OrganizationalField::query()->updateOrCreate(
                ['code' => $field['code']],
                [
                    'name' => $field['name'],
                    'description' => $field['description'] ?? null,
                    'is_active' => true,
                ]
            );
        }
    }
}
