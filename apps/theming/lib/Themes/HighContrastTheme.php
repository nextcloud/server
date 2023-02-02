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

class HighContrastTheme extends DefaultTheme implements ITheme {

	public function getId(): string {
		return 'light-highcontrast';
	}

	public function getMediaQuery(): string {
		return '(prefers-contrast: more)';
	}

	public function getTitle(): string {
		return $this->l->t('High contrast mode');
	}

	public function getEnableLabel(): string {
		return $this->l->t('Enable high contrast mode');
	}

	public function getDescription(): string {
		return $this->l->t('A high contrast mode to ease your navigation. Visual quality will be reduced but clarity will be increased.');
	}

	/**
	 * Keep this consistent with other HighContrast Themes
	 */
	public function getCSSVariables(): array {
		$defaultVariables = parent::getCSSVariables();

		$colorMainText = '#000000';
		$colorMainBackground = '#ffffff';
		$colorMainBackgroundRGB = join(',', $this->util->hexToRGB($colorMainBackground)); 

		return array_merge(
			$defaultVariables,
			$this->generatePrimaryVariables($colorMainBackground, $colorMainText),
			[
				'--color-main-background' => $colorMainBackground,
				'--color-main-background-rgb' => $colorMainBackgroundRGB,
				'--color-main-background-translucent' => 'rgba(var(--color-main-background-rgb), 1)',
				'--color-main-text' => $colorMainText,

				'--color-background-dark' => $this->util->darken($colorMainBackground, 30),
				'--color-background-darker' => $this->util->darken($colorMainBackground, 30),

				'--color-main-background-blur' => $colorMainBackground,
				'--filter-background-blur' => 'none',

				'--color-placeholder-light' => $this->util->darken($colorMainBackground, 30),
				'--color-placeholder-dark' => $this->util->darken($colorMainBackground, 45),

				'--color-text-maxcontrast' => $colorMainText,
				'--color-text-maxcontrast-background-blur' => $colorMainText,
				'--color-text-light' => $colorMainText,
				'--color-text-lighter' => $colorMainText,

				'--color-scrollbar' => $this->util->darken($colorMainBackground, 25),

				// used for the icon loading animation
				'--color-loading-light' => '#dddddd',
				'--color-loading-dark' => '#000000',

				'--color-box-shadow-rgb' => $colorMainText,
				'--color-box-shadow' => $colorMainText,

				'--color-border' => $this->util->darken($colorMainBackground, 50),
				'--color-border-dark' => $this->util->darken($colorMainBackground, 50),
			]
		);
	}

	public function getCustomCss(): string {
		return "
			[class^='icon-'], [class*=' icon-'],
			.action,
			#appmenu li a,
			.menutoggle {
				opacity: 1 !important;
			}
			#app-navigation {
				border-right: 1px solid var(--color-border);
			}
		";
	}
}
