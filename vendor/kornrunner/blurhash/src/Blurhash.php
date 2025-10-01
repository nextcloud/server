<?php

namespace kornrunner\Blurhash;

use InvalidArgumentException;

class Blurhash {

    public static function encode(array $image, int $components_x = 4, int $components_y = 4, bool $linear = false): string {
        if (($components_x < 1 || $components_x > 9) || ($components_y < 1 || $components_y > 9)) {
            throw new InvalidArgumentException("x and y component counts must be between 1 and 9 inclusive.");
        }
        $height = count($image);
        $width  = count($image[0]);

        $image_linear = $image;
        if (!$linear) {
            $image_linear = [];
            for ($y = 0; $y < $height; $y++) {
                $line = [];
                for ($x = 0; $x < $width; $x++) {
                    $pixel  = $image[$y][$x];
                    $line[] = [
                        Color::toLinear($pixel[0]),
                        Color::toLinear($pixel[1]),
                        Color::toLinear($pixel[2])
                    ];
                }
                $image_linear[] = $line;
            }
        }

        $components = [];
        $scale = 1 / ($width * $height);
        for ($y = 0; $y < $components_y; $y++) {
            for ($x = 0; $x < $components_x; $x++) {
                $normalisation = $x == 0 && $y == 0 ? 1 : 2;
                $r = $g = $b = 0;
                for ($i = 0; $i < $width; $i++) {
                    for ($j = 0; $j < $height; $j++) {
                        $color = $image_linear[$j][$i];
                        $basis = $normalisation
                                * cos(M_PI * $i * $x / $width)
                                * cos(M_PI * $j * $y / $height);

                        $r += $basis * $color[0];
                        $g += $basis * $color[1];
                        $b += $basis * $color[2];
                    }
                }

                $components[] = [
                    $r * $scale,
                    $g * $scale,
                    $b * $scale
                ];
            }
        }

        $dc_value = DC::encode(array_shift($components) ?: []);

        $max_ac_component = 0;
        foreach ($components as $component) {
            $component[] = $max_ac_component;
            $max_ac_component = max ($component);
        }

        $quant_max_ac_component = (int) max(0, min(82, floor($max_ac_component * 166 - 0.5)));
        $ac_component_norm_factor = ($quant_max_ac_component + 1) / 166;

        $ac_values = [];
        foreach ($components as $component) {
            $ac_values[] = AC::encode($component, $ac_component_norm_factor);
        }

        $blurhash  = Base83::encode($components_x - 1 + ($components_y - 1) * 9, 1);
        $blurhash .= Base83::encode($quant_max_ac_component, 1);
        $blurhash .= Base83::encode($dc_value, 4);
        foreach ($ac_values as $ac_value) {
            $blurhash .= Base83::encode((int) $ac_value, 2);
        }

        return $blurhash;
    }

    public static function decode (string $blurhash, int $width, int $height, float $punch = 1.0, bool $linear = false): array {
        if (empty($blurhash) || strlen($blurhash) < 6) {
            throw new InvalidArgumentException("Blurhash string must be at least 6 characters");
        }

        $size_info = Base83::decode($blurhash[0]);
        $size_y = intdiv($size_info, 9) + 1;
        $size_x = ($size_info % 9) + 1;

        $length = strlen($blurhash);
        $expected_length = (int) (4 + (2 * $size_y * $size_x));
        if ($length !== $expected_length) {
            throw new InvalidArgumentException("Blurhash length mismatch: length is {$length} but it should be {$expected_length}");
        }

        $colors = [DC::decode(Base83::decode(substr($blurhash, 2, 4)))];

        $quant_max_ac_component = Base83::decode($blurhash[1]);
        $max_value = ($quant_max_ac_component + 1) / 166;
        for ($i = 1; $i < $size_x * $size_y; $i++) {
            $value = Base83::decode(substr($blurhash, 4 + $i * 2, 2));
            $colors[$i] = AC::decode($value, $max_value * $punch);
        }

        $pixels = [];
        for ($y = 0; $y < $height; $y++) {
            $row = [];
            for ($x = 0; $x < $width; $x++) {
                $r = $g = $b = 0;
                for ($j = 0; $j < $size_y; $j++) {
                    for ($i = 0; $i < $size_x; $i++) {
                        $color = $colors[$i + $j * $size_x];
                        $basis =
                            cos((M_PI * $x * $i) / $width) *
                            cos((M_PI * $y * $j) / $height);

                        $r += $color[0] * $basis;
                        $g += $color[1] * $basis;
                        $b += $color[2] * $basis;
                    }
                }

                $row[] = $linear ? [$r, $g, $b] : [
                    Color::toSRGB($r),
                    Color::toSRGB($g),
                    Color::toSRGB($b)
                ];
            }
            $pixels[] = $row;
        }

        return $pixels;
    }
}
