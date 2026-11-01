<?php

namespace App\Enums;

enum StructuralPosition: string
{
    case Walikota = 'walikota';
    case WakilWalikota = 'wakil_walikota';
    case SekretarisUmum = 'sekretaris_umum';
    case BendaharaUmum = 'bendahara_umum';
    case KepalaBidang = 'kepala_bidang';

    public function label(): string
    {
        return match ($this) {
            self::Walikota => 'Walikota Himpunan',
            self::WakilWalikota => 'Wakil Walikota Himpunan',
            self::SekretarisUmum => 'Sekretaris Umum',
            self::BendaharaUmum => 'Bendahara Umum',
            self::KepalaBidang => 'Kepala Bidang',
        };
    }
}
