<?php

namespace Database\Seeders;

use App\Enums\PengurusMembership;
use App\Enums\StructuralPosition;
use App\Enums\UserRole;
use App\Models\OrganizationalField;
use App\Models\User;
use Illuminate\Database\Seeder;

class HimatifUserSeeder extends Seeder
{
    /**
     * Kepengurusan HIMATIF periode 2026-2027 dari Surat Keputusan
     * No. 002/WALMA/Skep/HIMATIF-USBYPKP/X/2026.
     * Password awal = NPM masing-masing (cast 'hashed' di model).
     */
    public function run(): void
    {
        $fields = OrganizationalField::query()->get()->keyBy('code');

        // [npp, npm, name, field_code, structural_position|null, role]
        $users = [
            ['1.3.3.04.002', '2113241036', 'Rizki Nurfathir', 'STAFF INTI', 'walikota', 'pengurus', 'sentral'],
            ['1.3.3.04.001', '2113241015', 'Rehan Muhamad Ikbal', 'STAFF INTI', 'wakil_walikota', 'pengurus', 'sentral'],
            ['1.3.3.04.011', '2113241012', 'Paulina Dela Consita Dima', 'STAFF INTI', 'bendahara_umum', 'pengurus', 'sentral'],
            ['1.3.3.04.007', '2113241025', 'Muchamad FebriNurmansyah', 'HRDO', 'kepala_bidang', 'admin', 'sentral'],
            ['1.3.3.04.015', '2113241041', 'Gilbert Gabrielta Keliat', 'HRDO', null, 'pengurus', 'sentral'],
            ['1.3.3.05.001', '2113251015', 'Agnia Nur Nazhifa', 'HRDO', null, 'admin', 'muda'],
            ['1.3.3.05.002', '2113251012', 'Lia Ulpiah', 'HRDO', null, 'admin', 'muda'],
            ['1.3.3.05.008', '2113251042', 'Rafi Pratama Maulana', 'HRDO', null, 'pengurus', 'muda'],
            ['1.3.3.05.015', '2113251037', 'Naufal Rifqi Hanifan', 'HRDO', null, 'pengurus', 'muda'],
            ['1.3.3.05.016', '2113251029', 'Sani Gaida Bilqis Saffanah', 'HRDO', null, 'admin', 'muda'],
            ['1.3.3.04.019', '2113241039', 'R. Dzakki Nur Fauzi Mutaqin', 'RE', 'kepala_bidang', 'pengurus', 'sentral'],
            ['1.3.3.04.010', '2113241087', 'Kaneka Raya Fadhli Ahsan', 'RE', null, 'pengurus', 'sentral'],
            ['1.3.3.04.031', '2113241085', 'Ahmad Sidqi Mustopa', 'RE', null, 'pengurus', 'sentral'],
            ['1.3.3.04.023', '2113241086', 'Salman Faruch', 'RE', null, 'pengurus', 'sentral'],
            ['1.3.3.05.007', '2113251033', 'Jizdan Ikbar Rizqulloh', 'RE', null, 'pengurus', 'muda'],
            ['1.3.3.05.003', '2113251057', 'Rizalul Fikri', 'RE', null, 'pengurus', 'muda'],
            ['1.3.3.05.019', '2113251045', 'Rio Reyvan Putra', 'RE', null, 'pengurus', 'muda'],
            ['1.3.3.04.036', '2113241044', 'Shahlan Mubarak Krisna', 'PR', 'kepala_bidang', 'pengurus', 'sentral'],
            ['1.3.3.05.004', '2113251036', 'Tristan Andana Putranto', 'PR', null, 'pengurus', 'muda'],
            ['1.3.3.05.010', '2113251067', 'Akbar Rizki Pratama', 'PR', null, 'pengurus', 'muda'],
            ['1.3.3.04.028', '2113241069', 'Ludovikus Meiman Zebua', 'PR', null, 'pengurus', 'sentral'],
            ['1.3.3.05.011', '2113251059', 'Gita Mey Sita', 'PR', null, 'pengurus', 'muda'],
            ['1.3.3.05.018', '2113251011', 'Arini Nuraghniya Darlian', 'PR', null, 'pengurus', 'muda'],
            ['1.3.3.05.005', '2113251035', 'Muhammad Farhan Firmansyah', 'PR', null, 'pengurus', 'muda'],
            ['1.3.3.04.006', '2113241017', 'Dzaki Naufal Adrian', 'CNB', 'kepala_bidang', 'pengurus', 'sentral'],
            ['1.3.3.04.017', '2113241095', 'Mochammad Ramadhan Nur Haerukman', 'CNB', null, 'pengurus', 'sentral'],
            ['1.3.3.05.012', '2113251028', 'Nadhif Haidar Prima', 'CNB', null, 'pengurus', 'muda'],
            ['1.3.3.05.006', '2113251007', 'Andhika Rizky Saktiawan', 'CNB', null, 'pengurus', 'muda'],
            ['1.3.3.05.017', '2113251061', 'Syifa Aprilia Fauziah', 'CNB', null, 'pengurus', 'muda'],
            ['1.3.3.05.009', '2113251031', 'Jovita Jinan Riyadi', 'CNB', null, 'pengurus', 'muda'],
            ['1.3.3.05.020', '2113251058', 'Lindah', 'CNB', null, 'pengurus', 'muda'],
        ];

        foreach ($users as [$npp, $npm, $name, $fieldCode, $structural, $role, $membership]) {
            User::query()->updateOrCreate(
                ['npp' => $npp],
                [
                    'npm' => $npm,
                    'name' => $name,
                    'email' => $npm.'@himatif.local',
                    'role' => $role === 'admin' ? UserRole::Admin : UserRole::Pengurus,
                    'organizational_field_id' => $fields[$fieldCode]->id,
                    'structural_position' => $structural === null ? null : StructuralPosition::from($structural),
                    'is_active' => true,
                    'password' => $npm,
                    'membership_type' => $membership === null ? null : PengurusMembership::from($membership),
                ]
            );
        }
    }
}
