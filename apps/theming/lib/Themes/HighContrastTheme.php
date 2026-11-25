<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Theming\Themes;

use OCA\Theming\ITheme;

class HighContrastTheme extends DefaultTheme implements ITheme {

	#[\Override]
	public function getId(): string {
		return 'highcontrast';
	}

	#[\Override]
	public function getType(): int {
		return ITheme::TYPE_SUPPLEMENTARY;
	}

	/**
	 * Light and dark palettes set their own colors.
	 * A single color-scheme meta would force one of them onto both.
	 */
	#[\Override]
	public function getMeta(): array {
		return [];
	}

	#[\Override]
	public function getTitle(): string {
		return $this->l->t('High contrast mode');
	}

	#[\Override]
	public function getEnableLabel(): string {
		return $this->l->t('Enable high contrast mode');
	}

	#[\Override]
	public function getDescription(): string {
		return $this->l->t('A high contrast mode to ease your navigation. Visual quality will be reduced but clarity will be increased.');
	}

	#[\Override]
	public function getMediaQuery(): string {
		return '(prefers-contrast: more)';
	}

	/**
	 * Only the variables that differ from the default theme.
	 * High contrast is supplementary, so emitting the whole default palette would replace the dark theme.
	 */
	#[\Override]
	public function getCSSVariables(): array {
		$defaultVariables = parent::getCSSVariables();

		$colorMainText = '#000000';
		$colorMainBackground = '#ffffff';
		$colorMainBackgroundRGB = join(',', $this->util->hexToRGB($colorMainBackground));

		$colorError = '#FFB3B3';
		$colorWarning = '#FFD888';
		$colorSuccess = '#C4EDCE';
		$colorInfo = '#6BCEFF';

		$primaryVariables = $this->generatePrimaryVariables($colorMainBackground, $colorMainText, true);
		$overrides = array_merge(
			$primaryVariables,
			[
				'--color-primary-element-text-dark' => $primaryVariables['--color-primary-element-text'],

				'--color-main-background' => $colorMainBackground,
				'--color-main-background-rgb' => $colorMainBackgroundRGB,
				'--color-main-background-translucent' => 'rgba(var(--color-main-background-rgb), 1)',
				'--color-main-text' => $colorMainText,

				'--color-background-dark' => $this->util->darken($colorMainBackground, 20),
				'--color-background-darker' => $this->util->darken($colorMainBackground, 20),

				'--color-main-background-blur' => $colorMainBackground,
				'--filter-background-blur' => 'none',

				'--color-placeholder-light' => $this->util->darken($colorMainBackground, 30),
				'--color-placeholder-dark' => $this->util->darken($colorMainBackground, 45),

				'--color-text-maxcontrast' => $colorMainText,
				'--color-text-maxcontrast-background-blur' => $colorMainText,
				'--color-text-error' => $this->util->darken($colorError, 65),
				'--color-text-success' => $this->util->darken($colorSuccess, 70),

				'--color-element-error' => $this->util->darken($colorError, 50),
				'--color-element-info' => $this->util->darken($colorInfo, 50),
				'--color-element-success' => $this->util->darken($colorSuccess, 55),
				'--color-element-warning' => $this->util->darken($colorWarning, 50),

				'--color-error' => $colorError,
				'--color-error-rgb' => join(',', $this->util->hexToRGB($colorError)),
				'--color-error-hover' => $this->util->darken($colorError, 5),
				'--color-error-text' => $this->util->darken($colorError, 70),

				'--color-warning' => $colorWarning,
				'--color-warning-rgb' => join(',', $this->util->hexToRGB($colorWarning)),
				'--color-warning-hover' => $this->util->darken($colorWarning, 8),
				'--color-warning-text' => $this->util->darken($colorWarning, 65),

				'--color-info' => $colorInfo,
				'--color-info-rgb' => join(',', $this->util->hexToRGB($colorInfo)),
				'--color-info-hover' => $this->util->darken($colorInfo, 8),
				'--color-info-text' => $this->util->darken($colorInfo, 65),

				'--color-success' => $colorSuccess,
				'--color-success-rgb' => join(',', $this->util->hexToRGB($colorSuccess)),
				'--color-success-hover' => $this->util->darken($colorSuccess, 8),
				'--color-success-text' => $this->util->darken($colorSuccess, 70),

				'--color-favorite' => '#936B06',
				'--color-background-selection' => 'var(--color-primary-element)',
				'--color-text-selection' => 'var(--color-primary-element-text)',

				'--color-scrollbar' => 'auto transparent',

				// used for the icon loading animation
				'--color-loading-light' => '#dddddd',
				'--color-loading-dark' => '#000000',

				'--color-box-shadow-rgb' => $colorMainText,
				'--color-box-shadow' => $colorMainText,

				'--color-border' => $this->util->darken($colorMainBackground, 50),
				'--color-border-dark' => $this->util->darken($colorMainBackground, 50),
				'--color-border-maxcontrast' => $this->util->darken($colorMainBackground, 56),
				'--color-border-error' => $this->util->darken($colorError, 42),
				'--color-border-success' => $this->util->darken($colorSuccess, 55),

				// remove the gradient from the app icons
				'--header-menu-icon-mask' => 'none',
			]
		);

		return array_diff_assoc($overrides, $defaultVariables);
	}

	/**
	 * Light high-contrast variables come from getCSSVariables().
	 * Dark high-contrast overrides them when dark mode is active, on top of the same attribute.
	 */
	#[\Override]
	public function getCustomCss(): string {
		$darkCss = $this->getDarkHighContrastCss();

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
			&[data-theme-dark] {
				$darkCss
			}
			@media (prefers-color-scheme: dark) {
				&:not([data-theme-light]) {
					$darkCss
				}
				:root:not(:has(> body[data-theme-light])) {
					$darkCss
				}
			}
		";
	}

	/**
	 * Dark high-contrast palette.
	 * Applied for an explicit dark theme and for the system theme while the OS is dark.
	 */
	private function getDarkHighContrastCss(): string {
		$css = 'color-scheme:dark;';
		foreach ($this->getDarkHighContrastOverrides() as $variable => $value) {
			$css .= "$variable:$value;";
		}

		return $css . '
			div.crumb {
				filter: brightness(150%);
			}
			::selection {
				color: var(--color-primary-element-text);
			}
		';
	}

	/**
	 * Dark high-contrast adjustments that differ from the light overrides.
	 * The dark theme already supplies its own palette, so this list is only the high-contrast delta.
	 *
	 * @return array<string, string>
	 */
	private function getDarkHighContrastOverrides(): array {
		$colorMainText = '#ffffff';
		$colorMainBackground = '#000000';
		$colorMainBackgroundRGB = join(',', $this->util->hexToRGB($colorMainBackground));

		$colorError = '#750000';
		$colorWarning = '#423800';
		$colorSuccess = '#1A4020';
		$colorInfo = '#004875';

		$overrides = array_merge(
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

				'--color-background-selection' => 'var(--color-primary-element)',

				'--color-scrollbar' => 'auto transparent',

				'--color-loading-light' => '#000000',
				'--color-loading-dark' => '#dddddd',

				'--color-box-shadow-rgb' => $colorMainText,
				'--color-box-shadow' => $colorMainText,

				'--header-menu-icon-mask' => 'none',
			]
		);

		return array_diff_assoc($overrides, $this->getCSSVariables());
	}
}
