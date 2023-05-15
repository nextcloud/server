<?php
/**
 * @copyright Copyright (c) 2016 Julius Härtl <jus@bitgrid.net>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @author Julius Haertl <jus@bitgrid.net>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Michael Weimann <mail@michael-weimann.eu>
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
namespace OCA\Theming;

use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IConfig;
use OCP\IUserSession;
use Mexitek\PHPColors\Color;

class Util {

	private IConfig $config;
	private IAppManager $appManager;
	private IAppData $appData;
	private ImageManager $imageManager;

	public function __construct(IConfig $config, IAppManager $appManager, IAppData $appData, ImageManager $imageManager) {
		$this->config = $config;
		$this->appManager = $appManager;
		$this->appData = $appData;
		$this->imageManager = $imageManager;
	}

	/**
	 * Should we invert the text on this background color?
	 * @param string $color rgb color value
	 * @return bool
	 */
	public function invertTextColor(string $color): bool {
		return $this->isBrightColor($color);
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
	 * @param bool $brightBackground
	 * @return string
	 */
	public function elementColor($color, bool $brightBackground = true) {
		$luminance = $this->calculateLuminance($color);

		if ($brightBackground && $luminance > 0.8) {
			// If the color is too bright in bright mode, we fall back to a darker gray
			return '#aaaaaa';
		}

		if (!$brightBackground && $luminance < 0.2) {
			// If the color is too dark in dark mode, we fall back to a brighter gray
			return '#8c8c8c';
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
	 * @param string $color rgb color value
	 * @return float
	 */
	public function calculateLuma(string $color): float {
		[$red, $green, $blue] = $this->hexToRGB($color);
		return (0.2126 * $red + 0.7152 * $green + 0.0722 * $blue) / 255;
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
		$radioButtonIcon = '<svg xmlns="http://www.w3.org/2000/svg" height="16" width="16">' .
			'<path d="M8 1a7 7 0 0 0-7 7 7 7 0 0 0 7 7 7 7 0 0 0 7-7 7 7 0 0 0-7-7zm0 1a6 6 0 0 1 6 6 6 6 0 0 1-6 6 6 6 0 0 1-6-6 6 6 0 0 1 6-6zm0 2a4 4 0 1 0 0 8 4 4 0 0 0 0-8z" fill="'.$color.'"/></svg>';
		return base64_encode($radioButtonIcon);
	}


	/**
	 * @param $app string app name
	 * @return string|ISimpleFile path to app icon / file of logo
	 */
	public function getAppIcon($app) {
		$app = str_replace(['\0', '/', '\\', '..'], '', $app);
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
	 * @param $app string app name
	 * @param $image string relative path to image in app folder
	 * @return string|false absolute path to image
	 */
	public function getAppImage($app, $image) {
		$app = str_replace(['\0', '/', '\\', '..'], '', $app);
		$image = str_replace(['\0', '\\', '..'], '', $image);
		if ($app === "core") {
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
	 * @param $svg string content of a svg file
	 * @param $color string color to match
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
		$userSession = \OC::$server->get(IUserSession::class);
		$userId = '';
		$user = $userSession->getUser();
		if (!is_null($user)) {
			$userId = $user->getUID();
		}
		$userCacheBuster = '';
		if ($userId) {
			$userCacheBusterValue = (int)$this->config->getUserValue($userId, 'theming', 'userCacheBuster', '0');
			$userCacheBuster = $userId . '_' . $userCacheBusterValue;
		}
		$systemCacheBuster = $this->config->getAppValue('theming', 'cachebuster', '0');
		return substr(sha1($userCacheBuster . $systemCacheBuster), 0, 8);
	}
}
