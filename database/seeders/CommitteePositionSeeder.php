<?php

namespace Database\Seeders;

use App\Enums\CommitteePositionCategory;
use App\Models\CommitteePosition;
use Illuminate\Database\Seeder;

class CommitteePositionSeeder extends Seeder
{
    /**
     * Dua belas jabatan kepanitiaan awal.
     */
    public function run(): void
    {
        $positions = [
            ['name' => 'Penanggung Jawab Kegiatan', 'category' => CommitteePositionCategory::Structural],
            ['name' => 'Penanggung Jawab Panitia', 'category' => CommitteePositionCategory::Structural],
            ['name' => 'Ketua Pelaksana', 'category' => CommitteePositionCategory::Structural],
            ['name' => 'Sekretaris Pelaksana', 'category' => CommitteePositionCategory::Structural],
            ['name' => 'Bendahara Pelaksana', 'category' => CommitteePositionCategory::Structural],
            ['name' => 'Divisi Acara', 'category' => CommitteePositionCategory::Division],
            ['name' => 'Divisi Logistik', 'category' => CommitteePositionCategory::Division],
            ['name' => 'Divisi Humas', 'category' => CommitteePositionCategory::Division],
            ['name' => 'Divisi PDD', 'category' => CommitteePositionCategory::Division],
            ['name' => 'Divisi Konsumsi', 'category' => CommitteePositionCategory::Division],
            ['name' => 'Divisi P3K', 'category' => CommitteePositionCategory::Division],
            ['name' => 'Divisi Keamanan', 'category' => CommitteePositionCategory::Division],
        ];

        foreach ($positions as $position) {
            CommitteePosition::query()->updateOrCreate(
                ['name' => $position['name']],
                [
                    'category' => $position['category'],
                    'is_active' => true,
                ]
            );
        }
    }
}
