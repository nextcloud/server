<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Themes;

use OCA\Theming\ITheme;

class DarkTheme extends DefaultTheme implements ITheme {

	protected bool $isDarkVariant = true;

	public function getId(): string {
		return 'dark';
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

	public function getMediaQuery(): string {
		return '(prefers-color-scheme: dark)';
	}

	public function getMeta(): array {
		// https://html.spec.whatwg.org/multipage/semantics.html#meta-color-scheme
		return [[
			'name' => 'color-scheme',
			'content' => 'dark',
		]];
	}

	public function getCSSVariables(): array {
		$defaultVariables = parent::getCSSVariables();

		$colorMainText = '#EBEBEB';
		$colorMainBackground = '#171717';
		$colorMainBackgroundRGB = join(',', $this->util->hexToRGB($colorMainBackground));
		$colorTextMaxcontrast = $this->util->darken($colorMainText, 32);

		$colorBoxShadow = $this->util->darken($colorMainBackground, 70);
		$colorBoxShadowRGB = join(',', $this->util->hexToRGB($colorBoxShadow));

		$colorError = '#552121';
		$colorErrorText = '#FFCCCC';
		$colorErrorElement = '#FF5050';
		$colorWarning = '#3D3010';
		$colorWarningText = '#FFEEC5';
		$colorWarningElement = '#FFCC00';
		$colorSuccess = '#11321A';
		$colorSuccessText = '#D5F2DC';
		$colorSuccessElement = '#40A330';
		$colorInfo = '#003553';
		$colorInfoText = '#00AEFF';
		$colorInfoElement = '#0099E0';

		return array_merge(
			$defaultVariables,
			$this->generatePrimaryVariables($colorMainBackground, $colorMainText),
			[
				'--color-main-text' => $colorMainText,
				'--color-main-background' => $colorMainBackground,
				'--color-main-background-rgb' => $colorMainBackgroundRGB,
				'--color-main-background-blur' => 'rgba(var(--color-main-background-rgb), .85)',

				'--color-background-hover' => $this->util->lighten($colorMainBackground, 4),
				'--color-background-dark' => $this->util->lighten($colorMainBackground, 7),
				'--color-background-darker' => $this->util->lighten($colorMainBackground, 14),

				'--color-placeholder-light' => $this->util->lighten($colorMainBackground, 10),
				'--color-placeholder-dark' => $this->util->lighten($colorMainBackground, 20),

				'--color-text-maxcontrast' => $colorTextMaxcontrast,
				'--color-text-maxcontrast-default' => $colorTextMaxcontrast,
				'--color-text-maxcontrast-background-blur' => $this->util->lighten($colorTextMaxcontrast, 6),
				'--color-text-error' => $this->util->lighten($colorErrorElement, 6),
				'--color-text-success' => $this->util->lighten($colorSuccessElement, 6),

				'--color-border' => $this->util->lighten($colorMainBackground, 7),
				'--color-border-dark' => $this->util->lighten($colorMainBackground, 14),
				'--color-border-maxcontrast' => $this->util->lighten($colorMainBackground, 40),

				// Assistant colors (see default theme)
				'--color-background-assistant' => '#221D2B',
				'--color-border-assistant' => 'linear-gradient(125deg, #0C3A65 50%, #6204A5 125%)',
				'--color-element-assistant' => 'linear-gradient(214deg, #C8A3E8 12%, #007CFF 39%, #C297DD 86%)',
				'--color-element-assistant-icon' => 'linear-gradient(214deg, #CDACE7 15.28%, #008FDB 39.98%, #A180E0 82.05%)',

				'--color-element-error' => $colorErrorElement,
				'--color-element-info' => $colorInfoElement,
				'--color-element-success' => $colorSuccessElement,
				'--color-element-warning' => $colorWarningElement,

				'--color-error' => $colorError,
				'--color-error-hover' => $this->util->lighten($colorError, 10),
				'--color-error-text' => $colorErrorText,
				'--color-warning' => $colorWarning,
				'--color-warning-hover' => $this->util->lighten($colorWarning, 10),
				'--color-warning-text' => $colorWarningText,
				'--color-success' => $colorSuccess,
				'--color-success-hover' => $this->util->lighten($colorSuccess, 10),
				'--color-success-text' => $colorSuccessText,
				'--color-info' => $colorInfo,
				'--color-info-hover' => $this->util->lighten($colorInfo, 10),
				'--color-info-text' => $colorInfoText,
				'--color-favorite' => '#ffde00',
				// deprecated
				'--color-error-rgb' => join(',', $this->util->hexToRGB($colorError)),
				'--color-warning-rgb' => join(',', $this->util->hexToRGB($colorWarning)),
				'--color-success-rgb' => join(',', $this->util->hexToRGB($colorSuccess)),
				'--color-info-rgb' => join(',', $this->util->hexToRGB($colorInfo)),

				// used for the icon loading animation
				'--color-loading-light' => '#777',
				'--color-loading-dark' => '#CCC',

				'--color-box-shadow' => $colorBoxShadow,
				'--color-box-shadow-rgb' => $colorBoxShadowRGB,

				'--background-invert-if-dark' => 'invert(100%)',
				'--background-invert-if-bright' => 'no',
			]
		);
	}
}
