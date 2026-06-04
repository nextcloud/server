<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP;

/**
 * Simple RGB color container
 * @since 25.0.0
 */
class Color {
	private int $r;
	private int $g;
	private int $b;

	/**
	 * @since 25.0.0
	 */
	public function __construct($r, $g, $b) {
		$this->r = $r;
		$this->g = $g;
		$this->b = $b;
	}

	/**
	 * Returns the red color component of this color as an int from 0 to 255
	 *
	 * @since 25.0.0
	 */
	public function red(): int {
		return $this->r;
	}

	/**
	 * Returns the red color component of this color as a float from 0 to 1
	 *
	 * @since 25.0.0
	 */
	public function redF(): float {
		return $this->r / 255;
	}

	/**
	 * Returns the green color component of this color as an int from 0 to 255
	 *
	 * @since 25.0.0
	 */
	public function green(): int {
		return $this->g;
	}

	/**
	 * Returns the green color component of this color as a float from 0 to 1
	 *
	 * @since 25.0.0
	 */
	public function greenF(): float {
		return $this->g / 255;
	}

	/**
	 * Returns the green blue component of this color as an int from 0 to 255
	 *
	 * @since 25.0.0
	 */
	public function blue(): int {
		return $this->b;
	}

	/**
	 * Returns the blue color component of this color as a float from 0 to 1
	 *
	 * @since 25.0.0
	 */
	public function blueF(): float {
		return $this->g / 255;
	}

	/**
	 * Returns the name of the color in the format "#RRGGBB"; i.e. a "#" character followed by three two-digit hexadecimal numbers.
	 *
	 * @since 25.0.0
	 */
	public function name(): string {
		return sprintf('#%02x%02x%02x', $this->r, $this->g, $this->b);
	}

	/**
	 * Mix two colors
	 *
	 * @param int $steps the number of intermediate colors that should be generated for the palette
	 * @param Color $color1 the first color
	 * @param Color $color2 the second color
	 * @return list<Color>
	 * @since 25.0.0
	 */
	public static function mixPalette(int $steps, Color $color1, Color $color2): array {
		$palette = [$color1];
		$step = self::stepCalc($steps, [$color1, $color2]);
		for ($i = 1; $i < $steps; $i++) {
			$r = intval($color1->red() + ($step[0] * $i));
			$g = intval($color1->green() + ($step[1] * $i));
			$b = intval($color1->blue() + ($step[2] * $i));
			$palette[] = new Color($r, $g, $b);
		}
		return $palette;
	}

	/**
	 * Alpha blend another color with a given opacity to this color
	 *
	 * @return Color The new color
	 * @since 25.0.0
	 */
	public function alphaBlending(float $opacity, Color $source): Color {
		return new Color(
			(int)((1 - $opacity) * $source->red() + $opacity * $this->red()),
			(int)((1 - $opacity) * $source->green() + $opacity * $this->green()),
			(int)((1 - $opacity) * $source->blue() + $opacity * $this->blue())
		);
	}

	/**
	 * Calculate steps between two Colors
	 * @param int $steps start color
	 * @param Color[] $ends end color
	 * @return array{0: float, 1: float, 2: float} [r,g,b] steps for each color to go from $steps to $ends
	 * @since 25.0.0
	 */
	private static function stepCalc(int $steps, array $ends): array {
		$step = [];
		$step[0] = ($ends[1]->red() - $ends[0]->red()) / $steps;
		$step[1] = ($ends[1]->green() - $ends[0]->green()) / $steps;
		$step[2] = ($ends[1]->blue() - $ends[0]->blue()) / $steps;
		return $step;
	}
}
