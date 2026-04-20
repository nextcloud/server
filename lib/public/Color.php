<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP;

/**
 * Simple RGB color container
 *
 * @since 25.0.0
 */
class Color {
	/**
	 * @since 25.0.0
	 */
	public function __construct(
		private int $r,
		private int $g,
		private int $b,
	) {
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
	 * Returns the blue color component of this color as an int from 0 to 255
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
		return $this->b / 255;
	}

	/**
	 * Returns the hex triplet color value as a string ("#RRGGBB")
	 *
	 * @since 25.0.0
	 */
	public function name(): string {
		return sprintf('#%02x%02x%02x', $this->r, $this->g, $this->b);
	}

	// Utility Functions

	/**
	 * Generate a progression of colors starting with $color1 and moving toward $color2.
	 *
	 * @param int $steps Total number of colors to return (including $color1, but excluding $color2); should be at least 2
	 * @param Color $color1 The starting color (index 0 of the returned list)
	 * @param Color $color2 The target color used to calculate the transition
	 * @return list<Color> The list of colors starting with $color1 up to but not including $color2
	 * @since 25.0.0
	 */
	public static function mixPalette(int $steps, Color $color1, Color $color2): array {
		$palette = [$color1];
		[$rDelta, $gDelta, $bDelta] = self::calculateDeltas($steps, $color1, $color2);

		for ($i = 1; $i < $steps; $i++) {
			$palette[] = new Color(
				// TODO: Consider using round() instead of (int) truncation for more accurate color transitions.
				(int)($color1->red() + ($rDelta * $i)),
				(int)($color1->green() + ($gDelta * $i)),
				(int)($color1->blue() + ($bDelta * $i)),
			);
		}

		return $palette;
	}

	/**
	 * Blend this color over a source color.
	 *
	 * An opacity of 0 returns $source, and 1 returns this color.
	 *
	 * @param float $opacity Opacity of this color, expected in the range 0.0 to 1.0
	 * @param Color $source The source/background color
	 * @return Color The blended color
	 * @since 25.0.0
	 */
	public function alphaBlending(float $opacity, Color $source): Color {
		return new Color(
			// TODO: Consider using round() instead of (int) truncation for more accurate color transitions.
			(int)((1 - $opacity) * $source->red() + $opacity * $this->red()),
			(int)((1 - $opacity) * $source->green() + $opacity * $this->green()),
			(int)((1 - $opacity) * $source->blue() + $opacity * $this->blue())
		);
	}

	/**
	 * Calculate the per-channel change (RGB deltas) required to transition between two colors.
	 *
	 * @param int $count The number of intervals to divide the transition into >0
	 * @param Color $start The starting color
	 * @param Color $end The target color
	 * @return array{0: float, 1: float, 2: float} The per-channel [r, g, b] increment required for each interval
	 * @since 25.0.0
	 */
	private static function calculateDeltas(int $count, Color $start, Color $end): array {
		$deltas = [];

		$deltas[0] = ($end->red() - $start->red()) / $count;
		$deltas[1] = ($end->green() - $start->green()) / $count;
		$deltas[2] = ($end->blue() - $start->blue()) / $count;

		return $deltas;
	}
}
