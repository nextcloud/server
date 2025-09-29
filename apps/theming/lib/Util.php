<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming;

use Mexitek\PHPColors\Color;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\Server;
use OCP\ServerVersion;

class Util {
	public function __construct(
		private ServerVersion $serverVersion,
		private IConfig $config,
		private IAppManager $appManager,
		private IAppData $appData,
		private ImageManager $imageManager,
	) {
	}

	/**
	 * Should we invert the text on this background color?
	 * @param string $color rgb color value
	 * @return bool
	 */
	public function invertTextColor(string $color): bool {
		return $this->colorContrast($color, '#ffffff') < 4.5;
	}

	/**
	 * Get the best text color contrast-wise for the given color.
	 *
	 * @since 32.0.0
	 */
	public function getTextColor(string $color): string {
		return $this->invertTextColor($color) ? '#000000' : '#ffffff';
	}

	/**
	 * Is this color too bright ?
	 * @param string $color rgb color value
	 * @return bool
	 */
	public function isBrightColor(string $color): bool {
		$l = $this->calculateLuma($color);
		if ($l > 0.6) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * get color for on-page elements:
	 * theme color by default, grey if theme color is to bright
	 * @param string $color
	 * @param ?bool $brightBackground
	 * @return string
	 */
	public function elementColor($color, ?bool $brightBackground = null, ?string $backgroundColor = null, bool $highContrast = false) {
		if ($backgroundColor !== null) {
			$brightBackground = $brightBackground ?? $this->isBrightColor($backgroundColor);
			// Minimal amount that is possible to change the luminance
			$epsilon = 1.0 / 255.0;
			// Current iteration to prevent infinite loops
			$iteration = 0;
			// We need to keep blurred backgrounds in mind which might be mixed with the background
			$blurredBackground = $this->mix($backgroundColor, $brightBackground ? $color : '#ffffff', 66);
			$contrast = $this->colorContrast($color, $blurredBackground);

			// Min. element contrast is 3:1 but we need to keep hover states in mind -> min 3.2:1
			$minContrast = $highContrast ? 5.6 : 3.2;

			while ($contrast < $minContrast && $iteration++ < 100) {
				$hsl = Color::hexToHsl($color);
				$hsl['L'] = max(0, min(1, $hsl['L'] + ($brightBackground ? -$epsilon : $epsilon)));
				$color = '#' . Color::hslToHex($hsl);
				$contrast = $this->colorContrast($color, $blurredBackground);
			}
			return $color;
		}

		// Fallback for legacy calling
		$luminance = $this->calculateLuminance($color);

		if ($brightBackground !== false && $luminance > 0.8) {
			// If the color is too bright in bright mode, we fall back to a darkened color
			return $this->darken($color, 30);
		}

		if ($brightBackground !== true && $luminance < 0.2) {
			// If the color is too dark in dark mode, we fall back to a brightened color
			return $this->lighten($color, 30);
		}

		return $color;
	}

	public function mix(string $color1, string $color2, int $factor): string {
		$color = new Color($color1);
		return '#' . $color->mix($color2, $factor);
	}

	public function lighten(string $color, int $factor): string {
		$color = new Color($color);
		return '#' . $color->lighten($factor);
	}

	public function darken(string $color, int $factor): string {
		$color = new Color($color);
		return '#' . $color->darken($factor);
	}

	/**
	 * Convert RGB to HSL
	 *
	 * Copied from cssphp, copyright Leaf Corcoran, licensed under MIT
	 *
	 * @param int $red
	 * @param int $green
	 * @param int $blue
	 *
	 * @return float[]
	 */
	public function toHSL(int $red, int $green, int $blue): array {
		$color = new Color(Color::rgbToHex(['R' => $red, 'G' => $green, 'B' => $blue]));
		return array_values($color->getHsl());
	}

	/**
	 * @param string $color rgb color value
	 * @return float
	 */
	public function calculateLuminance(string $color): float {
		[$red, $green, $blue] = $this->hexToRGB($color);
		$hsl = $this->toHSL($red, $green, $blue);
		return $hsl[2];
	}

	/**
	 * Calculate the Luma according to WCAG 2
	 * http://www.w3.org/TR/WCAG20/#relativeluminancedef
	 * @param string $color rgb color value
	 * @return float
	 */
	public function calculateLuma(string $color): float {
		$rgb = $this->hexToRGB($color);

		// Normalize the values by converting to float and applying the rules from WCAG2.0
		$rgb = array_map(function (int $color) {
			$color = $color / 255.0;
			if ($color <= 0.03928) {
				return $color / 12.92;
			} else {
				return pow((($color + 0.055) / 1.055), 2.4);
			}
		}, $rgb);

		[$red, $green, $blue] = $rgb;
		return (0.2126 * $red + 0.7152 * $green + 0.0722 * $blue);
	}

	/**
	 * Calculat the color contrast according to WCAG 2
	 * http://www.w3.org/TR/WCAG20/#contrast-ratiodef
	 * @param string $color1 The first color
	 * @param string $color2 The second color
	 */
	public function colorContrast(string $color1, string $color2): float {
		$luminance1 = $this->calculateLuma($color1) + 0.05;
		$luminance2 = $this->calculateLuma($color2) + 0.05;
		return max($luminance1, $luminance2) / min($luminance1, $luminance2);
	}

	/**
	 * @param string $color rgb color value
	 * @return int[]
	 * @psalm-return array{0: int, 1: int, 2: int}
	 */
	public function hexToRGB(string $color): array {
		$color = new Color($color);
		return array_values($color->getRgb());
	}

	/**
	 * @param $color
	 * @return string base64 encoded radio button svg
	 */
	public function generateRadioButton($color) {
		$radioButtonIcon = '<svg xmlns="http://www.w3.org/2000/svg" height="16" width="16">'
			. '<path d="M8 1a7 7 0 0 0-7 7 7 7 0 0 0 7 7 7 7 0 0 0 7-7 7 7 0 0 0-7-7zm0 1a6 6 0 0 1 6 6 6 6 0 0 1-6 6 6 6 0 0 1-6-6 6 6 0 0 1 6-6zm0 2a4 4 0 1 0 0 8 4 4 0 0 0 0-8z" fill="' . $color . '"/></svg>';
		return base64_encode($radioButtonIcon);
	}


	/**
	 * @param string $app app name
	 * @return string|ISimpleFile path to app icon / file of logo
	 */
	public function getAppIcon($app) {
		$app = $this->appManager->cleanAppId($app);
		try {
			$appPath = $this->appManager->getAppPath($app);
			$icon = $appPath . '/img/' . $app . '.svg';
			if (file_exists($icon)) {
				return $icon;
			}
			$icon = $appPath . '/img/app.svg';
			if (file_exists($icon)) {
				return $icon;
			}
		} catch (AppPathNotFoundException $e) {
		}

		if ($this->config->getAppValue('theming', 'logoMime', '') !== '') {
			$logoFile = null;
			try {
				$folder = $this->appData->getFolder('global/images');
				return $folder->getFile('logo');
			} catch (NotFoundException $e) {
			}
		}
		return \OC::$SERVERROOT . '/core/img/logo/logo.svg';
	}

	/**
	 * @param string $app app name
	 * @param string $image relative path to image in app folder
	 * @return string|false absolute path to image
	 */
	public function getAppImage($app, $image) {
		$app = $this->appManager->cleanAppId($app);
		/**
		 * @psalm-taint-escape file
		 */
		$image = str_replace(['\0', '\\', '..'], '', $image);
		if ($app === 'core') {
			$icon = \OC::$SERVERROOT . '/core/img/' . $image;
			if (file_exists($icon)) {
				return $icon;
			}
		}

		try {
			$appPath = $this->appManager->getAppPath($app);
		} catch (AppPathNotFoundException $e) {
			return false;
		}

		$icon = $appPath . '/img/' . $image;
		if (file_exists($icon)) {
			return $icon;
		}
		$icon = $appPath . '/img/' . $image . '.svg';
		if (file_exists($icon)) {
			return $icon;
		}
		$icon = $appPath . '/img/' . $image . '.png';
		if (file_exists($icon)) {
			return $icon;
		}
		$icon = $appPath . '/img/' . $image . '.gif';
		if (file_exists($icon)) {
			return $icon;
		}
		$icon = $appPath . '/img/' . $image . '.jpg';
		if (file_exists($icon)) {
			return $icon;
		}

		return false;
	}

	/**
	 * replace default color with a custom one
	 *
	 * @param string $svg content of a svg file
	 * @param string $color color to match
	 * @return string
	 */
	public function colorizeSvg($svg, $color) {
		$svg = preg_replace('/#0082c9/i', $color, $svg);
		return $svg;
	}

	/**
	 * Check if a custom theme is set in the server configuration
	 *
	 * @return bool
	 */
	public function isAlreadyThemed() {
		$theme = $this->config->getSystemValue('theme', '');
		if ($theme !== '') {
			return true;
		}
		return false;
	}

	public function isBackgroundThemed() {
		$backgroundLogo = $this->config->getAppValue('theming', 'backgroundMime', '');
		return $backgroundLogo !== '' && $backgroundLogo !== 'backgroundColor';
	}

	public function isLogoThemed() {
		return $this->imageManager->hasImage('logo')
			|| $this->imageManager->hasImage('logoheader');
	}

	public function getCacheBuster(): string {
		$userSession = Server::get(IUserSession::class);
		$userId = '';
		$user = $userSession->getUser();
		if (!is_null($user)) {
			$userId = $user->getUID();
		}
		$serverVersion = $this->serverVersion->getVersionString();
		$themingAppVersion = $this->appManager->getAppVersion('theming');
		$userCacheBuster = '';
		if ($userId) {
			$userCacheBusterValue = (int)$this->config->getUserValue($userId, 'theming', 'userCacheBuster', '0');
			$userCacheBuster = $userId . '_' . $userCacheBusterValue;
		}
		$systemCacheBuster = $this->config->getAppValue('theming', 'cachebuster', '0');
		return substr(sha1($serverVersion . $themingAppVersion . $userCacheBuster . $systemCacheBuster), 0, 8);
	}
}
