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

class DarkTheme extends DefaultTheme implements ITheme {

	public function getId(): string {
		return 'dark';
	}

	public function getMediaQuery(): string {
		return '(prefers-color-scheme: dark)';
	}

	public function getTitle(): string {
		return $this->l->t('Dark theme');
	}

	public function getEnableLabel(): string {
		return $this->l->t('Enable dark theme');
	}

	public function getDescription(): string {
		return $this->l->t('A dark theme to ease your eyes by reducing the overall luminosity and brightness.');
	}

	public function getCSSVariables(): array {
		$defaultVariables = parent::getCSSVariables();

		$colorMainText = '#D8D8D8';
		$colorMainBackground = '#171717';
		$colorMainBackgroundRGB = join(',', $this->util->hexToRGB($colorMainBackground));
		$colorTextMaxcontrast = $this->util->darken($colorMainText, 30);

		$colorBoxShadow = $this->util->darken($colorMainBackground, 70);
		$colorBoxShadowRGB = join(',', $this->util->hexToRGB($colorBoxShadow));

		return array_merge(
			$defaultVariables,
			$this->generatePrimaryVariables($colorMainBackground, $colorMainText),
			[
				'--color-main-text' => $colorMainText,
				'--color-main-background' => $colorMainBackground,
				'--color-main-background-rgb' => $colorMainBackgroundRGB,

				'--color-scrollbar' => $this->util->lighten($colorMainBackground, 15),

				'--color-background-hover' => $this->util->lighten($colorMainBackground, 4),
				'--color-background-dark' => $this->util->lighten($colorMainBackground, 7),
				'--color-background-darker' => $this->util->lighten($colorMainBackground, 14),

				'--color-placeholder-light' => $this->util->lighten($colorMainBackground, 10),
				'--color-placeholder-dark' => $this->util->lighten($colorMainBackground, 20),

				'--color-text-maxcontrast' => $colorTextMaxcontrast,
				'--color-text-maxcontrast-default' => $colorTextMaxcontrast,
				'--color-text-maxcontrast-background-blur' => $this->util->lighten($colorTextMaxcontrast, 2),
				'--color-text-light' => $this->util->darken($colorMainText, 10),
				'--color-text-lighter' => $this->util->darken($colorMainText, 20),

				// used for the icon loading animation
				'--color-loading-light' => '#777',
				'--color-loading-dark' => '#CCC',

				'--color-box-shadow' => $colorBoxShadow,
				'--color-box-shadow-rgb' => $colorBoxShadowRGB,

				'--color-border' => $this->util->lighten($colorMainBackground, 7),
				'--color-border-dark' => $this->util->lighten($colorMainBackground, 14),
				'--color-border-maxcontrast' => $this->util->lighten($colorMainBackground, 30),

				'--background-invert-if-dark' => 'invert(100%)',
				'--background-invert-if-bright' => 'no',
			]
		);
	}
}
