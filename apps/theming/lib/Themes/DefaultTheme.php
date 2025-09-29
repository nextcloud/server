<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Themes;

use OC\AppFramework\Http\Request;
use OCA\Theming\ImageManager;
use OCA\Theming\ITheme;
use OCA\Theming\ThemingDefaults;
use OCA\Theming\Util;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;

class DefaultTheme implements ITheme {
	use CommonThemeTrait;

	public string $defaultPrimaryColor;
	public string $primaryColor;

	public function __construct(
		public Util $util,
		public ThemingDefaults $themingDefaults,
		public IUserSession $userSession,
		public IURLGenerator $urlGenerator,
		public ImageManager $imageManager,
		public IConfig $config,
		public IL10N $l,
		public IAppManager $appManager,
		private ?IRequest $request,
	) {
		$this->defaultPrimaryColor = $this->themingDefaults->getDefaultColorPrimary();
		$this->primaryColor = $this->themingDefaults->getColorPrimary();
	}

	public function getId(): string {
		return 'default';
	}

	public function getType(): int {
		return ITheme::TYPE_THEME;
	}

	public function getTitle(): string {
		return $this->l->t('System default theme');
	}

	public function getEnableLabel(): string {
		return $this->l->t('Enable the system default');
	}

	public function getDescription(): string {
		return $this->l->t('Using the default system appearance.');
	}

	public function getMediaQuery(): string {
		return '';
	}

	public function getMeta(): array {
		return [];
	}

	public function getCSSVariables(): array {
		$colorMainText = '#222222';
		// Color that still provides enough contrast for text, so we need a ratio of 4.5:1 on main background AND hover
		$colorTextMaxcontrast = '#6b6b6b'; // 4.5 : 1 for hover background and background dark
		$colorMainBackground = '#ffffff';
		$colorMainBackgroundRGB = join(',', $this->util->hexToRGB($colorMainBackground));
		$colorBoxShadow = $this->util->darken($colorMainBackground, 70);
		$colorBoxShadowRGB = join(',', $this->util->hexToRGB($colorBoxShadow));

		/*
		colorX: The background color for e.g. buttons and note-card
		colorXText: The text color on that background
		colorXElement: When that color needs to have element contrast like borders
		*/
		$colorError = '#FFE7E7';
		$colorErrorText = '#8A0000';
		$colorErrorElement = '#c90000';
		$colorWarning = '#FFEEC5';
		$colorWarningText = '#664700';
		$colorWarningElement = '#BF7900';
		$colorSuccess = '#D8F3DA';
		$colorSuccessText = '#005416';
		$colorSuccessElement = '#099f05';
		$colorInfo = '#D5F1FA';
		$colorInfoText = '#0066AC';
		$colorInfoElement = '#0077C7';

		$user = $this->userSession->getUser();
		// Chromium based browsers currently (2024) have huge performance issues with blur filters
		$isChromium = $this->request !== null && $this->request->isUserAgent([Request::USER_AGENT_CHROME, Request::USER_AGENT_MS_EDGE]);
		// Ignore MacOS because they always have hardware accelartion
		$isChromium = $isChromium && !$this->request->isUserAgent(['/Macintosh/']);
		// Allow to force the blur filter
		$forceEnableBlur = $user === null ? false : $this->config->getUserValue(
			$user->getUID(),
			'theming',
			'force_enable_blur_filter',
		);
		$workingBlur = match($forceEnableBlur) {
			'yes' => true,
			'no' => false,
			default => !$isChromium
		};

		$variables = [
			'--color-main-background' => $colorMainBackground,
			'--color-main-background-rgb' => $colorMainBackgroundRGB,
			'--color-main-background-translucent' => 'rgba(var(--color-main-background-rgb), .97)',
			'--color-main-background-blur' => 'rgba(var(--color-main-background-rgb), .8)',
			'--filter-background-blur' => $workingBlur ? 'blur(25px)' : 'none',

			// to use like this: background-image: linear-gradient(0, var('--gradient-main-background));
			'--gradient-main-background' => 'var(--color-main-background) 0%, var(--color-main-background-translucent) 85%, transparent 100%',

			// used for different active/hover/focus/disabled states
			'--color-background-hover' => $this->util->darken($colorMainBackground, 4),
			'--color-background-dark' => $this->util->darken($colorMainBackground, 7),
			'--color-background-darker' => $this->util->darken($colorMainBackground, 14),

			'--color-placeholder-light' => $this->util->darken($colorMainBackground, 10),
			'--color-placeholder-dark' => $this->util->darken($colorMainBackground, 20),

			// max contrast for WCAG compliance
			'--color-main-text' => $colorMainText,
			'--color-text-maxcontrast' => $colorTextMaxcontrast,
			'--color-text-maxcontrast-default' => $colorTextMaxcontrast,
			'--color-text-maxcontrast-background-blur' => $this->util->darken($colorTextMaxcontrast, 7),
			'--color-text-error' => $this->util->darken($colorErrorElement, 2),
			'--color-text-success' => $this->util->darken($colorSuccessElement, 10),

			// border colors
			'--color-border' => $this->util->darken($colorMainBackground, 7),
			'--color-border-dark' => $this->util->darken($colorMainBackground, 14),
			'--color-border-maxcontrast' => $this->util->darken($colorMainBackground, 51),
			'--color-border-error' => 'var(--color-element-error)',
			'--color-border-success' => 'var(--color-element-success)',

			// special colors for elements (providing corresponding contrast) e.g. icons
			'--color-element-error' => $colorErrorElement,
			'--color-element-info' => $colorInfoElement,
			'--color-element-success' => $colorSuccessElement,
			'--color-element-warning' => $colorWarningElement,

			// error/warning/success/info feedback colors
			'--color-error' => $colorError,
			'--color-error-hover' => $this->util->darken($colorError, 7),
			'--color-error-text' => $colorErrorText,
			'--color-warning' => $colorWarning,
			'--color-warning-hover' => $this->util->darken($colorWarning, 7),
			'--color-warning-text' => $colorWarningText,
			'--color-success' => $colorSuccess,
			'--color-success-hover' => $this->util->darken($colorSuccess, 7),
			'--color-success-text' => $colorSuccessText,
			'--color-info' => $colorInfo,
			'--color-info-hover' => $this->util->darken($colorInfo, 7),
			'--color-info-text' => $colorInfoText,
			'--color-favorite' => '#A37200',
			// deprecated
			'--color-error-rgb' => join(',', $this->util->hexToRGB($colorError)),
			'--color-warning-rgb' => join(',', $this->util->hexToRGB($colorWarning)),
			'--color-success-rgb' => join(',', $this->util->hexToRGB($colorSuccess)),
			'--color-info-rgb' => join(',', $this->util->hexToRGB($colorInfo)),

			// used for the icon loading animation
			'--color-loading-light' => '#cccccc',
			'--color-loading-dark' => '#444444',

			// Scrollbar
			'--color-scrollbar' => 'var(--color-border-maxcontrast) transparent',

			// Box shadow of elements
			'--color-box-shadow-rgb' => $colorBoxShadowRGB,
			'--color-box-shadow' => 'rgba(var(--color-box-shadow-rgb), 0.5)',

			// Assistant colors (marking AI generated content)
			'--color-background-assistant' => '#F6F5FF', // Background for AI generated content
			'--color-border-assistant' => 'linear-gradient(125deg, #7398FE 50%, #6104A4 125%)', // Border for AI generated content
			'--color-element-assistant' => 'linear-gradient(214deg, #A569D3 12%, #00679E 39%, #422083 86%)', // Background of primary buttons to interact with the Assistant (e.g. generate content)
			'--color-element-assistant-icon' => 'linear-gradient(214deg, #9669D3 15%, #00679E 40%, #492083 80%)', // The color used for the Assistant icon

			'--font-face' => "system-ui, -apple-system, 'Segoe UI', Roboto, Oxygen-Sans, Cantarell, Ubuntu, 'Helvetica Neue', 'Noto Sans', 'Liberation Sans', Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji'",
			'--default-font-size' => '15px',
			'--font-size-small' => '13px',
			// 1.5 * font-size for accessibility
			'--default-line-height' => '1.5',

			// TODO: support "(prefers-reduced-motion)"
			'--animation-quick' => '100ms',
			'--animation-slow' => '300ms',

			// Default variables --------------------------------------------
			// Border width for input elements such as text fields and selects
			'--border-width-input' => '1px',
			'--border-width-input-focused' => '2px',

			// Border radii (new values)
			'--border-radius-small' => '4px', // For smaller elements
			'--border-radius-element' => '8px', // For interactive elements such as buttons, input, navigation and list items
			'--border-radius-container' => '12px', // For smaller containers like action menus
			'--border-radius-container-large' => '16px', // For bigger containers like body or modals

			// Border radii (deprecated)
			'--border-radius' => 'var(--border-radius-small)',
			'--border-radius-large' => 'var(--border-radius-element)',
			'--border-radius-rounded' => '28px',
			'--border-radius-pill' => '100px',

			'--default-clickable-area' => '34px',
			'--clickable-area-large' => '48px',
			'--clickable-area-small' => '24px',

			'--default-grid-baseline' => '4px',

			// header / navigation bar
			'--header-height' => '50px',
			'--header-menu-item-height' => '44px',
			/* An alpha mask to be applied to all icons on the navigation bar (header menu).
			 * Icons are have a size of 20px but usually we use MDI which have a content of 16px so 2px padding top bottom,
			 * for better gradient we must at first begin at those 2px (10% of height) as start and stop positions.
			 */
			'--header-menu-icon-mask' => 'linear-gradient(var(--color-background-plain-text) 25%, color-mix(in srgb, var(--color-background-plain-text), 55% transparent) 90%) alpha',

			// various structure data
			'--navigation-width' => '300px',
			'--sidebar-min-width' => '300px',
			'--sidebar-max-width' => '500px',

			// Border radius of the body container
			'--body-container-radius' => 'var(--border-radius-container-large)',
			// Margin of the body container
			'--body-container-margin' => 'calc(var(--default-grid-baseline) * 2)',
			// Height of the body container to fully fill the view port
			'--body-height' => 'calc(100% - env(safe-area-inset-bottom) - var(--header-height) - var(--body-container-margin))',

			// mobile. Keep in sync with core/src/init.js
			'--breakpoint-mobile' => '1024px',
			'--background-invert-if-dark' => 'no',
			'--background-invert-if-bright' => 'invert(100%)',
			'--background-image-invert-if-bright' => 'no',
		];

		// Primary variables
		$variables = array_merge($variables, $this->generatePrimaryVariables($colorMainBackground, $colorMainText));
		$variables = array_merge($variables, $this->generateGlobalBackgroundVariables());
		$variables = array_merge($variables, $this->generateUserBackgroundVariables());

		return $variables;
	}

	public function getCustomCss(): string {
		return '';
	}
}
