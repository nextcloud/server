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
		$colorMainTextRgb = join(',', $this->util->hexToRGB($colorMainText));
		// Color that still provides enough contrast for text, so we need a ratio of 4.5:1 on main background AND hover
		$colorTextMaxcontrast = '#6b6b6b'; // 4.5 : 1 for hover background and background dark
		$colorMainBackground = '#ffffff';
		$colorMainBackgroundRGB = join(',', $this->util->hexToRGB($colorMainBackground));
		$colorBoxShadow = $this->util->darken($colorMainBackground, 70);
		$colorBoxShadowRGB = join(',', $this->util->hexToRGB($colorBoxShadow));

		$colorError = '#DB0606';
		$colorWarning = '#A37200';
		$colorSuccess = '#2d7b41';
		$colorInfo = '#0071ad';

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
			'--color-text-light' => 'var(--color-main-text)', // deprecated
			'--color-text-lighter' => 'var(--color-text-maxcontrast)', // deprecated

			'--color-scrollbar' => 'var(--color-border-maxcontrast) transparent',

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
			'--color-favorite' => '#A37200',

			// used for the icon loading animation
			'--color-loading-light' => '#cccccc',
			'--color-loading-dark' => '#444444',

			'--color-box-shadow-rgb' => $colorBoxShadowRGB,
			'--color-box-shadow' => 'rgba(var(--color-box-shadow-rgb), 0.5)',

			'--color-border' => $this->util->darken($colorMainBackground, 7),
			'--color-border-dark' => $this->util->darken($colorMainBackground, 14),
			'--color-border-maxcontrast' => $this->util->darken($colorMainBackground, 51),

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

			// various structure data
			'--header-height' => '50px',
			'--header-menu-item-height' => '44px',
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
