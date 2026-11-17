<?php

namespace App\Enums;

enum Grade: string
{
    case A = 'A';
    case B = 'B';
    case C = 'C';
    case D = 'D';
    case E = 'E';

    public static function fromScore(float $score): self
    {
        return match (true) {
            $score >= 85 => self::A,
            $score >= 70 => self::B,
            $score >= 55 => self::C,
            $score >= 40 => self::D,
            default => self::E,
        };
    }

    public function points(): float
    {
        return match ($this) {
            self::A => 4.0,
            self::B => 3.0,
            self::C => 2.0,
            self::D => 1.0,
            self::E => 0.0,
        };
    }
}
