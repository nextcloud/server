<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP;

use InvalidArgumentException;

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
		self::assertChannelInRange($this->r, 'r');
		self::assertChannelInRange($this->g, 'g');
		self::assertChannelInRange($this->b, 'b');
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
	 * Returns the name of the color in the format "#RRGGBB"
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
	 * @return list<Color>
	 * @since 25.0.0
	 */
	public static function mixPalette(int $steps, Color $color1, Color $color2): array {
		if ($steps < 2) {
			throw new InvalidArgumentException('Palette steps must be at least 2.');
		}

		$palette = [$color1];
		[$rStep, $gStep, $bStep] = self::stepCalc($steps, $color1, $color2);

		for ($i = 1; $i < $steps; $i++) {
			$palette[] = new self(
				(int)round($color1->red() + ($rStep * $i)),
				(int)round($color1->green() + ($gStep * $i)),
				(int)round($color1->blue() + ($bStep * $i)),
			);
		}

		return $palette;
	}

	/**
	 * Alpha blend another color with a given opacity into this color
	 *
	 * @return Color The new color
	 * @since 25.0.0
	 */
	public function alphaBlending(float $opacity, Color $source): Color {
		if ($opacity < 0.0 || $opacity > 1.0) {
			throw new InvalidArgumentException('Opacity must be between 0.0 and 1.0.');
		}

		return new self(
			(int)round((1 - $opacity) * $source->red() + $opacity * $this->red()),
			(int)round((1 - $opacity) * $source->green() + $opacity * $this->green()),
			(int)round((1 - $opacity) * $source->blue() + $opacity * $this->blue()),
		);
	}

	/**
	 * Calculate steps between two colors
	 *
	 * @return array{0: float, 1: float, 2: float}
	 * @since 25.0.0
	 */
	private static function stepCalc(int $steps, Color $start, Color $end): array {
		return [
			($end->red() - $start->red()) / $steps,
			($end->green() - $start->green()) / $steps,
			($end->blue() - $start->blue()) / $steps,
		];
	}

	private static function assertChannelInRange(int $value, string $channel): void {
		if ($value < 0 || $value > 255) {
			throw new InvalidArgumentException(sprintf(
				'Color channel "%s" must be between 0 and 255, got %d.',
				$channel,
				$value,
			));
		}
	}
}
