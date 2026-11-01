<?php

namespace App\Enums;

enum PengurusMembership: string
{
    case Sentral = 'sentral';
    case Muda = 'muda';

    public function label(): string
    {
        return match ($this) {
            self::Sentral => 'Pengurus Sentral',
            self::Muda => 'Pengurus Muda',
        };
    }
}
