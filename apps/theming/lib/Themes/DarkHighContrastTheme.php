<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
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
namespace OCA\Theming\Themes;

use OCA\Theming\ITheme;

class DarkHighContrastTheme extends DarkTheme implements ITheme {

	public function getId(): string {
		return 'dark-highcontrast';
	}

	public function getMediaQuery(): string {
		return '(prefers-color-scheme: dark) and (prefers-contrast: more)';
	}

	public function getTitle(): string {
		return $this->l->t('Dark theme with high contrast mode');
	}

	public function getEnableLabel(): string {
		return $this->l->t('Enable dark high contrast mode');
	}

	public function getDescription(): string {
		return $this->l->t('Similar to the high contrast mode, but with dark colours.');
	}

	/**
	 * Try to keep this consistent with HighContrastTheme
	 */
	public function getCSSVariables(): array {
		$variables = parent::getCSSVariables();
		$colorMainText = '#ffffff';
		$colorMainBackground = '#000000';
		$colorBoxShadowRGB = join(',', $this->util->hexToRGB($colorMainText));

		$variables['--color-main-background'] = $colorMainBackground;
		$variables['--color-main-text'] = $colorMainText;

		$variables['--color-background-dark'] = $this->util->lighten($colorMainBackground, 30);
		$variables['--color-background-darker'] = $this->util->lighten($colorMainBackground, 30);

		$variables['--color-placeholder-light'] = $this->util->lighten($colorMainBackground, 30);
		$variables['--color-placeholder-dark'] = $this->util->lighten($colorMainBackground, 45);

		$variables['--color-text-maxcontrast'] = $colorMainText;
		$variables['--color-text-light'] = $colorMainText;
		$variables['--color-text-lighter'] = $colorMainText;

		// used for the icon loading animation
		$variables['--color-loading-light'] = '#000000';
		$variables['--color-loading-dark'] = '#dddddd';


		$variables['--color-box-shadow-rgb'] = $colorBoxShadowRGB;
		$variables['--color-box-shadow'] = $colorBoxShadowRGB;


		$variables['--color-border'] = $this->util->lighten($colorMainBackground, 50);
		$variables['--color-border-dark'] = $this->util->lighten($colorMainBackground, 50);

		return $variables;
	}

	public function getCustomCss(string $prefix): string {
		return "
			$prefix [class^='icon-'], $prefix [class*=' icon-'],
			$prefix .action,
			$prefix #appmenu li a,
			$prefix .menutoggle {
				opacity: 1 !important;
			}
		";
	}
}
