<?php

namespace kornrunner\Blurhash;

final class AC {

    public static function encode(array $value, float $max_value): float {
        $quant_r = static::quantise($value[0] / $max_value);
        $quant_g = static::quantise($value[1] / $max_value);
        $quant_b = static::quantise($value[2] / $max_value);
        return $quant_r * 19 * 19 + $quant_g * 19 + $quant_b;
    }

    public static function decode(int $value, float $max_value): array {
        $quant_r = intdiv($value, 19 * 19);
        $quant_g = intdiv($value, 19) % 19;
        $quant_b = $value % 19;

        return [
            static::signPow(($quant_r - 9) / 9, 2) * $max_value,
            static::signPow(($quant_g - 9) / 9, 2) * $max_value,
            static::signPow(($quant_b - 9) / 9, 2) * $max_value
        ];
    }

    private static function quantise(float $value): float {
        return floor(max(0, min(18, floor(static::signPow($value, 0.5) * 9 + 9.5))));
    }

    private static function signPow(float $base, float $exp): float {
        $sign = $base <=> 0;
        return $sign * pow(abs($base), $exp);
    }
}