<?php

declare(strict_types=1);
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
	protected static bool $WCAGaaa = false;

	public static function dataAccessibilityPairs(): array {
		$textContrast = static::$WCAGaaa ? 7.0 : 4.5;
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
			'favorite elements on background' => [
				[
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
			'text-on-status-background' => [
				[
					'--color-main-text',
					'--color-text-maxcontrast',
				],
				[
					'--color-error',
					'--color-info',
					'--color-success',
					'--color-warning',
				],
				$textContrast,
			],
			'text-on-status-background-hover' => [
				[
					'--color-main-text',
				],
				[
					'--color-error-hover',
					'--color-info-hover',
					'--color-success-hover',
					'--color-warning-hover',
				],
				$textContrast,
			],
			'status-element-colors-on-background' => [
				[
					'--color-border-error',
					'--color-border-success',
					'--color-element-error',
					'--color-element-info',
					'--color-element-success',
					'--color-element-warning',
				],
				[
					'--color-main-background',
					'--color-background-hover',
					'--color-background-dark',
				],
				$elementContrast,
			],
			'status-text-on-background' => [
				[
					'--color-text-error',
					'--color-text-success',
				],
				[
					'--color-main-background',
					'--color-background-hover',
					'--color-background-dark',
					'--color-main-background-blur',
				],
				$textContrast,
			],
			'error-text-on-error-background' => [
				['--color-error-text'],
				[
					'--color-error',
					'--color-error-hover',
				],
				$textContrast,
			],
			'warning-text-on-warning-background' => [
				['--color-warning-text'],
				[
					'--color-warning',
					'--color-warning-hover',
				],
				$textContrast,
			],
			'success-text-on-success-background' => [
				['--color-success-text'],
				[
					'--color-success',
					'--color-success-hover',
				],
				$textContrast,
			],
			'text-on-assistant-background' => [
				[
					'--color-main-text',
					'--color-text-maxcontrast',
				],
				[
					'--color-background-assistant',
				],
				$textContrast,
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataAccessibilityPairs')]
	public function testAccessibilityOfVariables(array $mainColors, array $backgroundColors, float $minContrast): void {
		if (!isset($this->theme)) {
			$this->markTestSkipped('You need to setup $this->theme in your setUp function');
		} elseif (!isset($this->util)) {
			$this->markTestSkipped('You need to setup $this->util in your setUp function');
		}

		$variables = $this->theme->getCSSVariables();

		// Blur effect does not work so we mockup the color - worst supported case is the default "clouds" background image (on dark themes the clouds with white color are bad on bright themes the primary color as sky is bad)
		$variables['--color-main-background-blur'] = $this->util->mix($variables['--color-main-background'], $this->util->isBrightColor($variables['--color-main-background']) ? '#000000' : '#ffffff', 75);

		foreach ($backgroundColors as $background) {
			$matches = [];
			if (preg_match('/^var\\(([^)]+)\\)$/', $variables[$background], $matches) === 1) {
				$background = $matches[1];
			}
			$this->assertStringStartsWith('#', $variables[$background], 'Is not a plain color variable - consider to remove or fix this test');
			foreach ($mainColors as $main) {
				if (preg_match('/^var\\(([^)]+)\\)$/', $variables[$main], $matches) === 1) {
					$main = $matches[1];
				}
				$this->assertStringStartsWith('#', $variables[$main], 'Is not a plain color variable - consider to remove or fix this test');
				$realContrast = $this->util->colorContrast($variables[$main], $variables[$background]);
				$this->assertGreaterThanOrEqual($minContrast, $realContrast, "Contrast is not high enough for $main (" . $variables[$main] . ") on $background (" . $variables[$background] . ')');
			}
		}
	}
}
