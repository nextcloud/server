<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Themes;

use OCA\Theming\ITheme;

class DarkHighContrastTheme extends DarkTheme implements ITheme {

	public function getId(): string {
		return 'dark-highcontrast';
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

	public function getMediaQuery(): string {
		return '(prefers-color-scheme: dark) and (prefers-contrast: more)';
	}

	/**
	 * Keep this consistent with other HighContrast Themes
	 */
	public function getCSSVariables(): array {
		$defaultVariables = parent::getCSSVariables();

		$colorMainText = '#ffffff';
		$colorMainBackground = '#000000';
		$colorMainBackgroundRGB = join(',', $this->util->hexToRGB($colorMainBackground));

		$colorError = '#750000';
		$colorWarning = '#423800';
		$colorSuccess = '#1A4020';
		$colorInfo = '#004875';

		return array_merge(
			$defaultVariables,
			$this->generatePrimaryVariables($colorMainBackground, $colorMainText, true),
			[
				'--color-main-background' => $colorMainBackground,
				'--color-main-background-rgb' => $colorMainBackgroundRGB,
				'--color-main-background-translucent' => 'rgba(var(--color-main-background-rgb), 1)',
				'--color-main-text' => $colorMainText,

				'--color-background-dark' => $this->util->lighten($colorMainBackground, 20),
				'--color-background-darker' => $this->util->lighten($colorMainBackground, 20),

				'--color-main-background-blur' => $colorMainBackground,
				'--filter-background-blur' => 'none',

				'--color-placeholder-light' => $this->util->lighten($colorMainBackground, 30),
				'--color-placeholder-dark' => $this->util->lighten($colorMainBackground, 45),

				'--color-text-maxcontrast' => $colorMainText,
				'--color-text-maxcontrast-background-blur' => $colorMainText,
				'--color-text-error' => $this->util->lighten($colorError, 65),
				'--color-text-success' => $this->util->lighten($colorSuccess, 65),
				'--color-text-warning' => $this->util->lighten($colorWarning, 65),

				'--color-element-error' => $this->util->lighten($colorError, 30),
				'--color-element-info' => $this->util->lighten($colorInfo, 30),
				'--color-element-success' => $this->util->lighten($colorSuccess, 30),
				'--color-element-warning' => $this->util->lighten($colorWarning, 30),

				'--color-border' => $this->util->lighten($colorMainBackground, 50),
				'--color-border-dark' => $this->util->lighten($colorMainBackground, 50),
				'--color-border-maxcontrast' => $this->util->lighten($colorMainBackground, 55),

				'--color-error' => $colorError,
				'--color-error-rgb' => join(',', $this->util->hexToRGB($colorError)),
				'--color-error-hover' => $this->util->lighten($colorError, 4),
				'--color-error-text' => $this->util->lighten($colorError, 70),

				'--color-warning' => $colorWarning,
				'--color-warning-rgb' => join(',', $this->util->hexToRGB($colorWarning)),
				'--color-warning-hover' => $this->util->lighten($colorWarning, 5),
				'--color-warning-text' => $this->util->lighten($colorWarning, 65),

				'--color-success' => $colorSuccess,
				'--color-success-rgb' => join(',', $this->util->hexToRGB($colorSuccess)),
				'--color-success-hover' => $this->util->lighten($colorSuccess, 5),
				'--color-success-text' => $this->util->lighten($colorSuccess, 70),

				'--color-info' => $colorInfo,
				'--color-info-rgb' => join(',', $this->util->hexToRGB($colorInfo)),
				'--color-info-hover' => $this->util->lighten($colorInfo, 5),
				'--color-info-text' => $this->util->lighten($colorInfo, 60),

				'--color-scrollbar' => 'auto transparent',

				// used for the icon loading animation
				'--color-loading-light' => '#000000',
				'--color-loading-dark' => '#dddddd',

				'--color-box-shadow-rgb' => $colorMainText,
				'--color-box-shadow' => $colorMainText,

				// remove the gradient from the app icons
				'--header-menu-icon-mask' => 'none',
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
