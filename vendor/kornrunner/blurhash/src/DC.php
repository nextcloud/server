<?php

namespace kornrunner\Blurhash;

final class DC {

    public static function encode(array $value): int {
        $rounded_r = Color::tosRGB($value[0]);
        $rounded_g = Color::tosRGB($value[1]);
        $rounded_b = Color::tosRGB($value[2]);
        return ($rounded_r << 16) + ($rounded_g << 8) + $rounded_b;
    }

    public static function decode(int $value): array {
        $r = $value >> 16;
        $g = ($value >> 8) & 255;
        $b = $value & 255;
        return [
            Color::toLinear($r),
            Color::toLinear($g),
            Color::toLinear($b)
        ];
    }
}