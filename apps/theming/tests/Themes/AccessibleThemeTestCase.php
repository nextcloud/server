<?php
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Tests\Themes;

use OCA\Theming\ITheme;
use OCA\Theming\Util;
use Test\TestCase;

class AccessibleThemeTestCase extends TestCase {
	protected ITheme $theme;
	protected Util $util;

	/**
	 * Set to true to check for WCAG AAA level accessibility
	 */
	protected bool $WCAGaaa = false;

	public function dataAccessibilityPairs() {
		$textContrast = $this->WCAGaaa ? 7.0 : 4.5;
		$elementContrast = 3.0;

		return [
			'primary-element on background' => [
				[
					'--color-primary-element',
					'--color-primary-element-hover',
				],
				[
					'--color-main-background',
					'--color-background-hover',
					'--color-background-dark',
					'--color-background-darker',
					'--color-main-background-blur',
				],
				$elementContrast,
			],
			'status color elements on background' => [
				[
					'--color-error',
					'--color-error-hover',
					'--color-warning',
					'--color-warning-hover',
					'--color-info',
					'--color-info-hover',
					'--color-success',
					'--color-success-hover',
					'--color-favorite',
				],
				[
					'--color-main-background',
					'--color-background-hover',
					'--color-background-dark',
					'--color-background-darker',
					'--color-main-background-blur',
				],
				$elementContrast,
			],
			'border-colors' => [
				[
					'--color-border-maxcontrast',
				],
				[
					'--color-main-background',
					'--color-background-hover',
					'--color-background-dark',
					'--color-main-background-blur',
				],
				$elementContrast,
			],
			// Those two colors are used for borders which will be `color-main-text` on focussed state, thus need 3:1 contrast to it
			'success-error-border-colors' => [
				[
					'--color-error',
					'--color-success',
				],
				[
					'--color-main-text',
				],
				$elementContrast,
			],
			'primary-element-text' => [
				[
					'--color-primary-element-text',
					'--color-primary-element-text-dark',
				],
				[
					'--color-primary-element',
					'--color-primary-element-hover',
				],
				$textContrast,
			],
			'primary-element-light-text' => [
				['--color-primary-element-light-text'],
				[
					'--color-primary-element-light',
					'--color-primary-element-light-hover',
				],
				$textContrast,
			],
			'main-text' => [
				['--color-main-text'],
				[
					'--color-main-background',
					'--color-background-hover',
					'--color-background-dark',
					'--color-background-darker',
					'--color-main-background-blur',
				],
				$textContrast,
			],
			'max-contrast-text' => [
				['--color-text-maxcontrast'],
				[
					'--color-main-background',
					'--color-background-hover',
					'--color-background-dark',
				],
				$textContrast,
			],
			'max-contrast text-on blur' => [
				['--color-text-maxcontrast-background-blur'],
				[
					'--color-main-background-blur',
				],
				$textContrast,
			],
			'status-text' => [
				[
					'--color-error-text',
					'--color-warning-text',
					'--color-success-text',
					'--color-info-text',
				],
				[
					'--color-main-background',
					'--color-background-hover',
					'--color-background-dark',
					'--color-main-background-blur',
				],
				$textContrast,
			],
		];
	}

	/**
	 * @dataProvider dataAccessibilityPairs
	 */
	public function testAccessibilityOfVariables($mainColors, $backgroundColors, $minContrast): void {
		if (!isset($this->theme)) {
			$this->markTestSkipped('You need to setup $this->theme in your setUp function');
		} elseif (!isset($this->util)) {
			$this->markTestSkipped('You need to setup $this->util in your setUp function');
		}

		$variables = $this->theme->getCSSVariables();

		// Blur effect does not work so we mockup the color - worst supported case is the default "clouds" background image (on dark themes the clouds with white color are bad on bright themes the primary color as sky is bad)
		$variables['--color-main-background-blur'] = $this->util->mix($variables['--color-main-background'], $this->util->isBrightColor($variables['--color-main-background']) ? '#000000' : '#ffffff', 75);

		foreach ($backgroundColors as $background) {
			$this->assertStringStartsWith('#', $variables[$background], 'Is not a plain color variable - consider to remove or fix this test');
			foreach ($mainColors as $main) {
				$this->assertStringStartsWith('#', $variables[$main], 'Is not a plain color variable - consider to remove or fix this test');
				$realContrast = $this->util->colorContrast($variables[$main], $variables[$background]);
				$this->assertGreaterThanOrEqual($minContrast, $realContrast, "Contrast is not high enough for $main (" . $variables[$main] . ") on $background (" . $variables[$background] . ')');
			}
		}
	}
}
