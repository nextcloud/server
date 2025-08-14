<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Themes;

use OCA\Theming\ITheme;

class IonosTheme extends DefaultTheme implements ITheme {

	private const THEME_ID = 'ionos';

	// CSS file paths for custom styling
	private const CSS_FILES = [
		'_layout.css'
	];

	public function getId(): string {
		return self::THEME_ID;
	}

	public function getTitle(): string {
		return $this->l->t('IONOS theme');
	}

	public function getEnableLabel(): string {
		return $this->l->t('Enable the default IONOS theme');
	}

	public function getDescription(): string {
		return $this->l->t('The default IONOS appearance.');
	}

	public function getMediaQuery(): string {
		return '(prefers-color-scheme: light)';
	}

	public function getCustomCss(): string {
		$customCss = $this->loadCustomCssFiles();
		$fontCss = $this->generateFontFacesCss();

		return $customCss . PHP_EOL . $fontCss;
	}

	/**
	 * Load custom CSS files for IONOS theme
	 *
	 * @return string Combined CSS content from all theme files
	 */
	private function loadCustomCssFiles(): string {
		$customCss = '';
		foreach (self::CSS_FILES as $file) {
			$customCss .= file_get_contents(__DIR__ . '/../../css/' . self::THEME_ID . '/' . $file) . PHP_EOL;
		}

		return rtrim($customCss, PHP_EOL);
	}

	/**
	 * Generate CSS font face declarations for Open Sans font variants
	 *
	 * @return string CSS font-face declarations
	 */
	private function generateFontFacesCss(): string {
		$regularEot = $this->urlGenerator->linkTo('theming', 'fonts/OpenSans/OpenSans-Regular-webfont.eot');
		$regularWoff = $this->urlGenerator->linkTo('theming', 'fonts/OpenSans/OpenSans-Regular-webfont.woff');
		$regularWoff2 = $this->urlGenerator->linkTo('theming', 'fonts/OpenSans/OpenSans-Regular-webfont.woff2');
		$regularTtf = $this->urlGenerator->linkTo('theming', 'fonts/OpenSans/OpenSans-Regular-webfont.ttf');
		$regularSvg = $this->urlGenerator->linkTo('theming', 'fonts/OpenSans/OpenSans-Regular-webfont.svg#open_sansregular');

		$semiBoldEot = $this->urlGenerator->linkTo('theming', 'fonts/OpenSans/OpenSans-SemiBold-webfont.eot');
		$semiBoldWoff = $this->urlGenerator->linkTo('theming', 'fonts/OpenSans/OpenSans-SemiBold-webfont.woff');
		$semiBoldWoff2 = $this->urlGenerator->linkTo('theming', 'fonts/OpenSans/OpenSans-SemiBold-webfont.woff2');
		$semiBoldTtf = $this->urlGenerator->linkTo('theming', 'fonts/OpenSans/OpenSans-SemiBold-webfont.ttf');
		$semiBoldSvg = $this->urlGenerator->linkTo('theming', 'fonts/OpenSans/OpenSans-SemiBold-webfont.svg#open_sansregular');

		$boldEot = $this->urlGenerator->linkTo('theming', 'fonts/OpenSans/OpenSans-Bold-webfont.eot');
		$boldWoff = $this->urlGenerator->linkTo('theming', 'fonts/OpenSans/OpenSans-Bold-webfont.woff');
		$boldWoff2 = $this->urlGenerator->linkTo('theming', 'fonts/OpenSans/OpenSans-Bold-webfont.woff2');
		$boldTtf = $this->urlGenerator->linkTo('theming', 'fonts/OpenSans/OpenSans-Bold-webfont.ttf');
		$boldSvg = $this->urlGenerator->linkTo('theming', 'fonts/OpenSans/OpenSans-Bold-webfont.svg#open_sansregular');
		$fontCss = "
		@font-face {
			font-family: 'Open sans';
			src: url('$regularEot') format('embedded-opentype'),
				url('$regularWoff') format('woff'),
				url('$regularWoff2') format('woff2'),
				url('$regularTtf') format('truetype'),
				url('$regularSvg') format('svg');
			font-weight: normal;
			font-style: normal;
			font-display: swap;
		}

		/* Open sans semi-bold variant */
		@font-face {
			font-family: 'Open sans';
			src: url('$semiBoldEot') format('embedded-opentype'),
				url('$semiBoldWoff') format('woff'),
				url($semiBoldWoff2) format('woff2'),
				url('$semiBoldTtf') format('truetype'),
				url('$semiBoldSvg') format('svg');
			font-weight: 600;
			font-style: normal;
			font-display: swap;
		}

		/* Open sans bold variant */
		@font-face {
			font-family: 'Open sans';
			src: url('$boldEot') format('embedded-opentype'),
				url('$boldWoff') format('woff'),
				url('$boldWoff2') format('woff2'),
				url('$boldTtf') format('truetype'),
				url('$boldSvg') format('svg');
			font-weight: bold;
			font-style: normal;
			font-display: swap;
		}
		";
		return $fontCss;
	}

	public function getCSSVariables(): array {
		$defaultVariables = parent::getCSSVariables();
		$originalFontFace = $defaultVariables['--font-face'];

		// IONOS COLORS
		$ionColorMainBackground = '#fff';
		$ionColorPrimary = '#003d8f';
		$ionColorBlueB1 = '#dbedf8';
		$ionColorBlueB2 = '#95caeb';
		$ionColorBlueB3 = '#3196D6';
		$ionColorBlueB4 = '#1474c4';
		$ionColorBlueB5 = '#095BB1';
		$ionColorBlueB6 = '#003D8F';
		$ionColorBlueB7 = '#0B2A63';
		$ionColorBlueB8 = '#001B41';
		$ionColorBlueB9 = '#02102B';
		$ionColorCoolGreyC1 = '#f4f7fa';
		$ionColorCoolGreyC2 = '#dbe2e8';
		$ionColorCoolGreyC3 = '#bcc8d4';
		$ionColorCoolGreyC4 = '#97A3B4';
		$ionColorCoolGreyC5 = '#718095';
		$ionColorCoolGreyC6 = '#465A75';
		$ionColorCoolGreyC7 = '#2E4360';
		$ionColorCoolGreyC8 = '#1D2D42';
		$ionColorTypoMild = $ionColorCoolGreyC7;
		$ionColorLightGrey = '#d7d7d7';
		$ionColorGreenG3 = '#12cf76';
		$ionColorRoseR3 = '#ff6159';
		$ionColorSkyS3 = '#11c7e6';
		$ionColorAmberY3 = '#ffaa00';
		$ionColorAmberY4 = '#EF8300';
		$ionColorAmberY5 = '#c36b00';
		$ionColorAmberY6 = '#8E4E00';

		$ionosVariables = [
			'--ion-color-main-background' => $ionColorMainBackground,
			'--ion-color-primary' => $ionColorPrimary,
			'--ion-color-secondary' => $ionColorBlueB8,
			'--ion-color-blue-b1' => $ionColorBlueB1,
			'--ion-color-blue-b2' => $ionColorBlueB2,
			'--ion-color-blue-b3' => $ionColorBlueB3,
			'--ion-color-blue-b4' => $ionColorBlueB4,
			'--ion-color-blue-b5' => $ionColorBlueB5,
			'--ion-color-blue-b6' => $ionColorBlueB6,
			'--ion-color-blue-b7' => $ionColorBlueB7,
			'--ion-color-blue-b8' => $ionColorBlueB8,
			'--ion-color-blue-b9' => $ionColorBlueB9,
			'--ion-color-cool-grey-c1' => $ionColorCoolGreyC1,
			'--ion-color-cool-grey-c2' => $ionColorCoolGreyC2,
			'--ion-color-cool-grey-c3' => $ionColorCoolGreyC3,
			'--ion-color-cool-grey-c4' => $ionColorCoolGreyC4,
			'--ion-color-cool-grey-c5' => $ionColorCoolGreyC5,
			'--ion-color-cool-grey-c6' => $ionColorCoolGreyC6,
			'--ion-color-cool-grey-c7' => $ionColorCoolGreyC7,
			'--ion-color-cool-grey-c8' => $ionColorCoolGreyC8,
			'--ion-color-typo-mild' => $ionColorTypoMild,
			'--ion-color-light-grey' => $ionColorLightGrey,
			'--ion-color-green-g3' => $ionColorGreenG3,
			'--ion-color-rose-r3' => $ionColorRoseR3,
			'--ion-color-sky-s3' => $ionColorSkyS3,
			'--ion-color-amber-y3' => $ionColorAmberY3,
			'--ion-color-amber-y4' => $ionColorAmberY4,
			'--ion-color-amber-y5' => $ionColorAmberY5,
			'--ion-color-amber-y6' => $ionColorAmberY6,

			'--ion-button-sidebar-background' => 'transparent',
			'--ion-button-sidebar-background-hover' => 'var(--ion-color-cool-grey-c2)',
			'--ion-button-sidebar-background-active' => 'var(--ion-color-cool-grey-c3)',
			'--ion-button-sidebar-text' => 'var(--ion-color-secondary)',

			'--ion-button-sidebar--icon-only-background' => 'var(--ion-color-cool-grey-c2)',
			'--ion-button-sidebar--icon-only-background-hover' => 'var(--ion-color-cool-grey-c3)',
			'--ion-button-sidebar--icon-only-text' => 'var(--ion-color-secondary)',

			'--ion-dropdown-classic' => 'var(--ion-color-cool-grey-c3)',

			'--ion-surface-primary' => 'var(--ion-color-main-background)',
			'--ion-surface-secondary' => 'var(--ion-color-cool-grey-c1)',
			'--ion-surface-dialog' => '#fff',
		];


		$colorPrimary = $ionColorBlueB4;
		$this->primaryColor = $colorPrimary;

		$colorMainText = $ionColorTypoMild;
		$colorMainBackground = '#ffffff';
		$colorMainBackgroundRGB = join(',', $this->util->hexToRGB($colorMainBackground));
		$colorBoxShadow = $this->util->darken($colorMainBackground, 70);
		$colorBoxShadowRGB = join(',', $this->util->hexToRGB($colorBoxShadow));

		$colorError = $ionColorRoseR3;
		$colorWarning = $ionColorAmberY3;
		$colorSuccess = $ionColorGreenG3;
		$colorInfo = $ionColorSkyS3;

		$variables = [
			'--ion-shadow-header' => '0 4px 8px rgba(0, 0, 0, 0.12)',
			'--color-main-background' => $ionColorMainBackground,
			'--color-main-background-rgb' => $colorMainBackgroundRGB,
			'--color-main-background-translucent' => 'rgba(var(--color-main-background-rgb), .97)',
			'--color-main-background-blur' => 'rgba(var(--color-main-background-rgb), .8)',
			'--color-primary' => $colorPrimary,
			'--color-primary-element' => $colorPrimary,

			// to use like this: background-image: linear-gradient(0, var('--gradient-main-background));
			'--gradient-main-background' => 'var(--color-main-background) 0%, var(--color-main-background-translucent) 85%, transparent 100%',

			// used for different active/hover/focus/disabled states
			'--color-background-hover' => 'var(--ion-color-blue-b1)',
			'--color-background-dark' => $this->util->darken($colorMainBackground, 7),
			'--color-background-darker' => $this->util->darken($colorMainBackground, 14),

			'--color-placeholder-light' => $this->util->darken($colorMainBackground, 10),
			'--color-placeholder-dark' => $this->util->darken($colorMainBackground, 20),

			// max contrast for WCAG compliance
			'--color-main-text' => $ionColorTypoMild,
			'--color-text-maxcontrast' => $ionColorTypoMild,
			'--color-text-maxcontrast-default' => $ionColorTypoMild,
			'--color-text-maxcontrast-background-blur' => $ionColorTypoMild,
			'--color-text-light' => 'var(--color-main-text)', // deprecated
			'--color-text-lighter' => 'var(--color-text-maxcontrast)', // deprecated

			'--color-scrollbar' => $ionColorTypoMild,

			// error/warning/success/info feedback colours
			'--color-error' => $colorError,
			'--color-error-rgb' => join(',', $this->util->hexToRGB($colorError)),
			'--color-error-hover' => $this->util->mix($colorError, $colorMainBackground, 75),
			'--color-error-text' => $this->util->darken($colorError, 5),
			'--color-warning' => $colorWarning,
			'--color-warning-rgb' => join(',', $this->util->hexToRGB($colorWarning)),
			'--color-warning-hover' => $this->util->darken($colorWarning, 5),
			'--color-warning-text' => $this->util->darken($colorWarning, 7),
			'--color-success' => $colorSuccess,
			'--color-success-rgb' => join(',', $this->util->hexToRGB($colorSuccess)),
			'--color-success-hover' => $this->util->mix($colorSuccess, $colorMainBackground, 80),
			'--color-success-text' => $this->util->darken($colorSuccess, 4),
			'--color-info' => $colorInfo,
			'--color-info-rgb' => join(',', $this->util->hexToRGB($colorInfo)),
			'--color-info-hover' => $this->util->mix($colorInfo, $colorMainBackground, 80),
			'--color-info-text' => $this->util->darken($colorInfo, 4),
			'--color-favorite' => $ionColorAmberY3,
			// used for the icon loading animation
			'--color-loading-light' => '#cccccc',
			'--color-loading-dark' => '#444444',

			'--color-box-shadow-rgb' => $colorBoxShadowRGB,
			'--color-box-shadow' => 'rgba(var(--color-box-shadow-rgb), 0.5)',

			'--color-border' => $this->util->darken($colorMainBackground, 7),
			'--color-border-dark' => $this->util->darken($colorMainBackground, 14),
			'--color-border-maxcontrast' => $this->util->darken($colorMainBackground, 51),
		];

		return array_merge(
			$defaultVariables,
			$this->generatePrimaryVariables($colorMainBackground, $colorMainText),
			$ionosVariables,
			$variables,
			[
				'--font-face' => '"Open sans", ' . $originalFontFace
			]
		);
	}

	public function getMeta(): array {
		// https://html.spec.whatwg.org/multipage/semantics.html#meta-color-scheme
		return [[
			'name' => 'color-scheme',
			'content' => 'light',
		]];
	}
}
