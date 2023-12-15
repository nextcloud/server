<?php
/**
 * @copyright Copyright (c) 2023 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Theming\Tests\Themes;

use OCA\Theming\ITheme;
use OCA\Theming\Util;
use Test\TestCase;

class AccessibleThemeTestCase extends TestCase {
	protected ITheme $theme;
	protected Util $util;

	public function dataAccessibilityPairs() {
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
				3.0,
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
				4.5,
			],
			'primary-element-light-text' => [
				['--color-primary-element-light-text'],
				[
					'--color-primary-element-light',
					'--color-primary-element-light-hover',
				],
				4.5,
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
				4.5,
			],
			'max-contrast-text' => [
				['--color-text-maxcontrast'],
				[
					'--color-main-background',
					'--color-background-hover',
					'--color-background-dark',
				],
				4.5,
			],
			'max-contrast text-on blur' => [
				['--color-text-maxcontrast-background-blur'],
				[
					'--color-main-background-blur',
				],
				4.5,
			],
		];
	}

	/**
	 * @dataProvider dataAccessibilityPairs
	 */
	public function testAccessibilityOfVariables($mainColors, $backgroundColors, $minContrast) {
		if (!isset($this->theme)) {
			$this->markTestSkipped('You need to setup $this->theme in your setUp function');
		} elseif (!isset($this->util)) {
			$this->markTestSkipped('You need to setup $this->util in your setUp function');
		}

		$variables = $this->theme->getCSSVariables();

		// Blur effect does not work so we mockup the color - worst supported case is the default "clouds" background image (on dark themes the clouds with white color are bad on bright themes the primary color as sky is bad)
		$variables['--color-main-background-blur'] = $this->util->mix($variables['--color-main-background'], $this->util->isBrightColor($variables['--color-main-background']) ? $variables['--color-primary'] : '#ffffff', 75);

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
