<?php

namespace kornrunner\Blurhash;

final class Color {
    public static function toLinear(int $value): float {
        $value = $value / 255;
        return ($value <= 0.04045)
            ? $value / 12.92
            : pow(($value + 0.055) / 1.055, 2.4);
    }

    public static function tosRGB(float $value): int {
        $normalized = max(0, min(1, $value));
        $result = ($normalized <= 0.0031308)
            ? (int) round($normalized * 12.92 * 255 + 0.5)
            : (int) round((1.055 * pow($normalized, 1 / 2.4) - 0.055) * 255 + 0.5);
        return max(0, min($result, 255));
    }
}