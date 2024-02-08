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
	 * Keep this consistent with other HighContrast Themes
	 */
	public function getCSSVariables(): array {
		$defaultVariables = parent::getCSSVariables();

		$colorMainText = '#ffffff';
		$colorMainBackground = '#000000';
		$colorMainBackgroundRGB = join(',', $this->util->hexToRGB($colorMainBackground));

		$colorError = '#ff5252';
		$colorWarning = '#ffcc00';
		$colorSuccess = '#42a942';
		$colorInfo = '#38c0ff';

		return array_merge(
			$defaultVariables,
			$this->generatePrimaryVariables($colorMainBackground, $colorMainText, true),
			[
				'--color-main-background' => $colorMainBackground,
				'--color-main-background-rgb' => $colorMainBackgroundRGB,
				'--color-main-background-translucent' => 'rgba(var(--color-main-background-rgb), 1)',
				'--color-main-text' => $colorMainText,

				'--color-background-dark' => $this->util->lighten($colorMainBackground, 25),
				'--color-background-darker' => $this->util->lighten($colorMainBackground, 25),

				'--color-main-background-blur' => $colorMainBackground,
				'--filter-background-blur' => 'none',

				'--color-placeholder-light' => $this->util->lighten($colorMainBackground, 30),
				'--color-placeholder-dark' => $this->util->lighten($colorMainBackground, 45),

				'--color-text-maxcontrast' => $colorMainText,
				'--color-text-maxcontrast-background-blur' => $colorMainText,
				'--color-text-light' => $colorMainText,
				'--color-text-lighter' => $colorMainText,

				'--color-error' => $colorError,
				'--color-error-rgb' => join(',', $this->util->hexToRGB($colorError)),
				'--color-error-hover' => $this->util->lighten($colorError, 10),
				'--color-error-text' => $this->util->lighten($colorError, 25),

				'--color-warning' => $colorWarning,
				'--color-warning-rgb' => join(',', $this->util->hexToRGB($colorWarning)),
				'--color-warning-hover' => $this->util->lighten($colorWarning, 10),
				'--color-warning-text' => $this->util->lighten($colorWarning, 10),

				'--color-success' => $colorSuccess,
				'--color-success-rgb' => join(',', $this->util->hexToRGB($colorSuccess)),
				'--color-success-hover' => $this->util->lighten($colorSuccess, 10),
				'--color-success-text' => $this->util->lighten($colorSuccess, 35),

				'--color-info' => $colorInfo,
				'--color-info-rgb' => join(',', $this->util->hexToRGB($colorInfo)),
				'--color-info-hover' => $this->util->lighten($colorInfo, 10),
				'--color-info-text' => $this->util->lighten($colorInfo, 20),

				'--color-scrollbar' => $this->util->lighten($colorMainBackground, 35),

				// used for the icon loading animation
				'--color-loading-light' => '#000000',
				'--color-loading-dark' => '#dddddd',

				'--color-box-shadow-rgb' => $colorMainText,
				'--color-box-shadow' => $colorMainText,

				'--color-border' => $this->util->lighten($colorMainBackground, 50),
				'--color-border-dark' => $this->util->lighten($colorMainBackground, 50),
				'--color-border-maxcontrast' => $this->util->lighten($colorMainBackground, 55),
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
			div.crumb {
				filter: brightness(150%);
			}
		";
	}
}
