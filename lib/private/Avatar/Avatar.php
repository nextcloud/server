<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Avatar;

use Imagick;
use OCP\Color;
use OCP\Files\NotFoundException;
use OCP\IAvatar;
use Psr\Log\LoggerInterface;
use OC\User\User;
use OCP\IConfig;

/**
 * This class gets and sets users avatars.
 */
abstract class Avatar implements IAvatar {
	protected LoggerInterface $logger;

	/**
	 * https://github.com/sebdesign/cap-height -- for 500px height
	 * Automated check: https://codepen.io/skjnldsv/pen/PydLBK/
	 * Noto Sans cap-height is 0.715 and we want a 200px caps height size
	 * (0.4 letter-to-total-height ratio, 500*0.4=200), so: 200/0.715 = 280px.
	 * Since we start from the baseline (text-anchor) we need to
	 * shift the y axis by 100px (half the caps height): 500/2+100=350
	 */
	private string $svgTemplate = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
		<svg width="{size}" height="{size}" version="1.1" viewBox="0 0 500 500" xmlns="http://www.w3.org/2000/svg">
			<rect width="100%" height="100%" fill="#{fill}"></rect>
			<text x="50%" y="350" style="font-weight:normal;font-size:280px;font-family:\'Noto Sans\';text-anchor:middle;fill:#{fgFill}">{letter}</text>
		</svg>';

	public function __construct(
		LoggerInterface $logger,
		private User $user,
		private IConfig $config,
	) {
		$this->logger = $logger;
		$this->user = $user;
		$this->config = $config;
	}

	/**
	 * Returns the first letter of the display name, or "?" if no name given.
	 */
	private function getAvatarText(): string {
		$displayName = $this->user->getDisplayName();
		if (empty($displayName) === true) {
			return '?';
		}
		$firstTwoLetters = array_map(function ($namePart) {
			return mb_strtoupper(mb_substr($namePart, 0, 1), 'UTF-8');
		}, explode(' ', $displayName, 2));
		return implode('', $firstTwoLetters);
	}

	/**
	 * @inheritdoc
	 */
	public function get(int $size = 64, bool $darkTheme = false) {
		try {
			$file = $this->getFile($size, $darkTheme);
		} catch (NotFoundException $e) {
			return false;
		}

		$avatar = new \OCP\Image();
		$avatar->loadFromData($file->getContent());
		return $avatar;
	}

	/**
	 * {size} = 500
	 * {fill} = hex color to fill
	 * {letter} = Letter to display
	 *
	 * Generate SVG avatar
	 *
	 * @param int $size The requested image size in pixel
	 * @return string
	 *
	 */
	protected function getAvatarVector(string $userDisplayName, int $size, bool $darkTheme): string {
		$fgRGB = $this->avatarBackgroundColor($userDisplayName);
		$bgRGB = $fgRGB->alphaBlending(0.1, $darkTheme ? new Color(0, 0, 0) : new Color(255, 255, 255));
		$fill = sprintf('%02x%02x%02x', $bgRGB->red(), $bgRGB->green(), $bgRGB->blue());
		$fgFill = sprintf('%02x%02x%02x', $fgRGB->red(), $fgRGB->green(), $fgRGB->blue());
		$text = $this->getAvatarText();
		$toReplace = ['{size}', '{fill}', '{fgFill}', '{letter}'];
		return str_replace($toReplace, [$size, $fill, $fgFill, $text], $this->svgTemplate);
	}

	protected function getFont(string $userDisplayName): string {
		if (preg_match('/\p{Han}/u', $userDisplayName) === 1) {
			switch ($this->config->getUserValue($this->user->getUID(), 'core', 'lang', '')) {
				case 'zh_TW':
					return __DIR__ . '/../../../core/fonts/NotoSansTC-Regular.ttf';
				case 'zh_HK':
					return __DIR__ . '/../../../core/fonts/NotoSansHK-Regular.ttf';
				case 'ja':
					return __DIR__ . '/../../../core/fonts/NotoSansJP-Regular.ttf';
				case 'ko':
					return __DIR__ . '/../../../core/fonts/NotoSansKR-Regular.ttf';
				default:
					return __DIR__ . '/../../../core/fonts/NotoSansSC-Regular.ttf';
			}
		}
		return __DIR__ . '/../../../core/fonts/NotoSans-Regular.ttf';
	}

	/**
	 * Generate png avatar from svg with Imagick
	 */
	protected function generateAvatarFromSvg(int $size, bool $darkTheme): ?string {
		if (!extension_loaded('imagick')) {
			return null;
		}
		$formats = Imagick::queryFormats();
		// Avatar generation breaks if RSVG format is enabled. Fall back to gd in that case
		if (in_array('RSVG', $formats, true)) {
			return null;
		}
		try {
			$font = $this->getFont($userDisplayName);
			$svg = $this->getAvatarVector($userDisplayName, $size, $darkTheme);
			$avatar = new Imagick();
			$avatar->setFont($font);
			$avatar->readImageBlob($svg);
			$avatar->setImageFormat('png');
			$image = new \OCP\Image();
			$image->loadFromData((string)$avatar);
			return $image->data();
		} catch (\Exception $e) {
			return null;
		}
	}

	/**
	 * Generate png avatar with GD
	 * @throws \Exception when an error occurs in gd calls
	 */
	protected function generateAvatar(string $userDisplayName, int $size, bool $darkTheme): string {
		$text = $this->getAvatarText();
		$textColor = $this->avatarBackgroundColor($userDisplayName);
		$backgroundColor = $textColor->alphaBlending(0.1, $darkTheme ? new Color(0, 0, 0) : new Color(255, 255, 255));

		$im = imagecreatetruecolor($size, $size);
		if ($im === false) {
			throw new \Exception('Failed to create avatar image');
		}
		$background = imagecolorallocate(
			$im,
			$backgroundColor->red(),
			$backgroundColor->green(),
			$backgroundColor->blue()
		);
		$textColor = imagecolorallocate($im,
			$textColor->red(),
			$textColor->green(),
			$textColor->blue()
		);
		if ($background === false || $textColor === false) {
			throw new \Exception('Failed to create avatar image color');
		}
		imagefilledrectangle($im, 0, 0, $size, $size, $background);

		$font = $this->getFont($userDisplayName);

		$fontSize = $size * 0.4;
		[$x, $y] = $this->imageTTFCenter(
			$im, $text, $font, (int)$fontSize
		);

		imagettftext($im, $fontSize, 0, $x, $y, $textColor, $font, $text);

		ob_start();
		imagepng($im);
		$data = ob_get_contents();
		ob_end_clean();

		return $data;
	}

	/**
	 * Calculate real image ttf center
	 *
	 * @param \GdImage $image
	 * @param string $text text string
	 * @param string $font font path
	 * @param int $size font size
	 * @param int $angle
	 * @return array
	 */
	protected function imageTTFCenter(
		$image,
		string $text,
		string $font,
		int $size,
		int $angle = 0,
	): array {
		// Image width & height
		$xi = imagesx($image);
		$yi = imagesy($image);

		// bounding box
		$box = imagettfbbox($size, $angle, $font, $text);

		// imagettfbbox can return negative int
		$xr = abs(max($box[2], $box[4]));
		$yr = abs(max($box[5], $box[7]));

		// calculate bottom left placement
		$x = intval(($xi - $xr) / 2);
		$y = intval(($yi + $yr) / 2);

		return [$x, $y];
	}


	/**
	 * Convert a string to an integer evenly
	 * @param string $hash the text to parse
	 * @param int $maximum the maximum range
	 * @return int between 0 and $maximum
	 */
	private function hashToInt(string $hash, int $maximum): int {
		$final = 0;
		$result = [];

		// Splitting evenly the string
		for ($i = 0; $i < strlen($hash); $i++) {
			// chars in md5 goes up to f, hex:16
			$result[] = intval(substr($hash, $i, 1), 16) % 16;
		}
		// Adds up all results
		foreach ($result as $value) {
			$final += $value;
		}
		// chars in md5 goes up to f, hex:16
		return intval($final % $maximum);
	}

	/**
	 * @return Color Object containing r g b int in the range [0, 255]
	 */
	public function avatarBackgroundColor(string $hash): Color {
		// Normalize hash
		$hash = strtolower($hash);

		// Already a md5 hash?
		if (preg_match('/^([0-9a-f]{4}-?){8}$/', $hash, $matches) !== 1) {
			$hash = md5($hash);
		}

		// Remove unwanted char
		$hash = preg_replace('/[^0-9a-f]+/', '', $hash);

		$red = new Color(182, 70, 157);
		$yellow = new Color(221, 203, 85);
		$blue = new Color(0, 130, 201); // Nextcloud blue

		// Number of steps to go from a color to another
		// 3 colors * 6 will result in 18 generated colors
		$steps = 6;

		$palette1 = Color::mixPalette($steps, $red, $yellow);
		$palette2 = Color::mixPalette($steps, $yellow, $blue);
		$palette3 = Color::mixPalette($steps, $blue, $red);

		$finalPalette = array_merge($palette1, $palette2, $palette3);

		return $finalPalette[$this->hashToInt($hash, $steps * 3)];
	}
}
