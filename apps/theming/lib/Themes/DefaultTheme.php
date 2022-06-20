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

use OCA\Theming\ImageManager;
use OCA\Theming\ThemingDefaults;
use OCA\Theming\Util;
use OCA\Theming\ITheme;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;

class DefaultTheme implements ITheme {
	public Util $util;
	public ThemingDefaults $themingDefaults;
	public IURLGenerator $urlGenerator;
	public ImageManager $imageManager;
	public IConfig $config;
	public IL10N $l;

	public string $primaryColor;

	public function __construct(Util $util,
								ThemingDefaults $themingDefaults,
								IURLGenerator $urlGenerator,
								ImageManager $imageManager,
								IConfig $config,
								IL10N $l) {
		$this->util = $util;
		$this->themingDefaults = $themingDefaults;
		$this->urlGenerator = $urlGenerator;
		$this->imageManager = $imageManager;
		$this->config = $config;
		$this->l = $l;

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

	public function getCSSVariables(): array {
		$colorMainText = '#222222';
		$colorMainBackground = '#ffffff';
		$colorMainBackgroundRGB = join(',', $this->util->hexToRGB($colorMainBackground));
		$colorBoxShadow = $this->util->darken($colorMainBackground, 70);
		$colorBoxShadowRGB = join(',', $this->util->hexToRGB($colorBoxShadow));
		$colorPrimaryLight = $this->util->mix($this->primaryColor, $colorMainBackground, -80);

		$hasCustomLogoHeader = $this->imageManager->hasImage('logo') ||  $this->imageManager->hasImage('logoheader');

		$variables = [
			'--color-main-background' => $colorMainBackground,
			'--color-main-background-rgb' => $colorMainBackgroundRGB,
			'--color-main-background-translucent' => 'rgba(var(--color-main-background-rgb), .97)',

			// to use like this: background-image: linear-gradient(0, var('--gradient-main-background));
			'--gradient-main-background' => 'var(--color-main-background) 0%, var(--color-main-background-translucent) 85%, transparent 100%',

			// used for different active/hover/focus/disabled states
			'--color-background-hover' => $this->util->darken($colorMainBackground, 4),
			'--color-background-dark' => $this->util->darken($colorMainBackground, 7),
			'--color-background-darker' => $this->util->darken($colorMainBackground, 14),

			'--color-placeholder-light' => $this->util->darken($colorMainBackground, 10),
			'--color-placeholder-dark' => $this->util->darken($colorMainBackground, 20),

			// primary related colours
			'--color-primary' => $this->primaryColor,
			'--color-primary-text' => $this->util->invertTextColor($this->primaryColor) ? '#000000' : '#ffffff',
			'--color-primary-hover' => $this->util->mix($this->primaryColor, $colorMainBackground, 60),
			'--color-primary-light' => $colorPrimaryLight,
			'--color-primary-light-text' => $this->primaryColor,
			'--color-primary-light-hover' => $this->util->mix($colorPrimaryLight, $colorMainText, 90),
			'--color-primary-text-dark' => $this->util->darken($this->util->invertTextColor($this->primaryColor) ? '#000000' : '#ffffff', 7),
			// used for buttons, inputs...
			'--color-primary-element' => $this->util->elementColor($this->primaryColor),
			'--color-primary-element-hover' => $this->util->mix($this->util->elementColor($this->primaryColor), $colorMainBackground, 80),
			'--color-primary-element-light' => $this->util->lighten($this->util->elementColor($this->primaryColor), 15),
			'--color-primary-element-lighter' => $this->util->mix($this->util->elementColor($this->primaryColor), $colorMainBackground, -70),
			// to use like this: background-image: var(--gradient-primary-background);
			'--gradient-primary-background' => 'linear-gradient(40deg, var(--color-primary) 0%, var(--color-primary-element-light) 100%)',

			// max contrast for WCAG compliance
			'--color-main-text' => $colorMainText,
			'--color-text-maxcontrast' => $this->util->lighten($colorMainText, 33),
			'--color-text-light' => $colorMainText,
			'--color-text-lighter' => $this->util->lighten($colorMainText, 33),

			// info/warning/success feedback colours
			'--color-error' => '#e9322d',
			'--color-error-hover' => $this->util->mix('#e9322d', $colorMainBackground, 60),
			'--color-warning' => '#eca700',
			'--color-warning-hover' => $this->util->mix('#eca700', $colorMainBackground, 60),
			'--color-success' => '#46ba61',
			'--color-success-hover' => $this->util->mix('#46ba61', $colorMainBackground, 60),

			// used for the icon loading animation
			'--color-loading-light' => '#cccccc',
			'--color-loading-dark' => '#444444',

			'--color-box-shadow-rgb' => $colorBoxShadowRGB,
			'--color-box-shadow' => "rgba(var(--color-box-shadow-rgb), 0.5)",

			'--color-border' => $this->util->darken($colorMainBackground, 7),
			'--color-border-dark' => $this->util->darken($colorMainBackground, 14),

			'--font-face' => "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Cantarell, Ubuntu, 'Helvetica Neue', Arial, sans-serif, 'Noto Color Emoji', 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'",
			'--default-font-size' => '15px',

			// TODO: support "(prefers-reduced-motion)"
			'--animation-quick' => '100ms',
			'--animation-slow' => '300ms',

			// Default variables --------------------------------------------
			'--border-radius' => '3px',
			'--border-radius-large' => '10px',
			// pill-style button, value is large so big buttons also have correct roundness
			'--border-radius-pill' => '100px',

			'--default-line-height' => '24px',

			// various structure data
			'--header-height' => '50px',
			'--navigation-width' => '300px',
			'--sidebar-min-width' => '300px',
			'--sidebar-max-width' => '500px',
			'--list-min-width' => '200px',
			'--list-max-width' => '300px',
			'--header-menu-item-height' => '44px',
			'--header-menu-profile-item-height' => '66px',

			// mobile. Keep in sync with core/js/js.js
			'--breakpoint-mobile' => '1024px',

			// invert filter if primary is too bright
			// to be used for legacy reasons only. Use inline
			// svg with proper css variable instead or material
			// design icons.
			// ⚠️ Using 'no' as a value to make sure we specify an
			// invalid one with no fallback. 'unset' could here fallback to some
			// other theme with media queries
			'--primary-invert-if-bright' => $this->util->invertTextColor($this->primaryColor) ? 'invert(100%)' : 'no',
			'--background-invert-if-dark' => 'no',
		];

		// Register image variables only if custom-defined
		$backgroundDeleted = $this->config->getAppValue('theming', 'backgroundMime', '') === 'backgroundColor';
		foreach(['logo', 'logoheader', 'favicon', 'background'] as $image) {
			if ($this->imageManager->hasImage($image)) {
				// If primary as background has been request, let's not define the background image
				if ($image === 'background' && $backgroundDeleted) {
					$variables["--image-background-plain"] = 'true';
					continue;
				} else if ($image === 'background') {
					$variables['--image-background-size'] = 'cover';
				}
				$variables["--image-$image"] = "url('".$this->imageManager->getImageUrl($image)."')";
			}
		}

		if ($hasCustomLogoHeader) {
			$variables["--image-logoheader-custom"] = 'true';
		}

		return $variables;
	}

	public function getCustomCss(): string {
		return '';
	}
}
