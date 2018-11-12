<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright 2018 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Olivier Mehani <shtrom@ssji.net>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC;

use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IAvatar;
use OCP\IConfig;
use OCP\IImage;
use OCP\IL10N;
use OCP\ILogger;
use OC\User\User;
use OC_Image;
use Imagick;

/**
 * This class gets and sets users avatars.
 */

class Avatar implements IAvatar {
	/** @var ISimpleFolder */
	private $folder;
	/** @var IL10N */
	private $l;
	/** @var User */
	private $user;
	/** @var ILogger  */
	private $logger;
	/** @var IConfig */
	private $config;

	/**
	 * https://github.com/sebdesign/cap-height -- for 500px height
	 * Automated check: https://codepen.io/skjnldsv/pen/PydLBK/
	 * Nunito cap-height is 0.716 and we want a 200px caps height size
	 * (0.4 letter-to-total-height ratio, 500*0.4=200), so: 200/0.716 = 279px.
	 * Since we start from the baseline (text-anchor) we need to
	 * shift the y axis by 100px (half the caps height): 500/2+100=350
	 *
	 * @var string
	 */
	private $svgTemplate = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
		<svg width="{size}" height="{size}" version="1.1" viewBox="0 0 500 500" xmlns="http://www.w3.org/2000/svg">
			<rect width="100%" height="100%" fill="#{fill}"></rect>
			<text x="50%" y="350" style="font-weight:normal;font-size:279px;font-family:\'Nunito\';text-anchor:middle;fill:#fff">{letter}</text>
		</svg>';

	/**
	 * constructor
	 *
	 * @param ISimpleFolder $folder The folder where the avatars are
	 * @param IL10N $l
	 * @param User $user
	 * @param ILogger $logger
	 * @param IConfig $config
	 */
	public function __construct(ISimpleFolder $folder,
								IL10N $l,
								$user,
								ILogger $logger,
								IConfig $config) {
		$this->folder = $folder;
		$this->l = $l;
		$this->user = $user;
		$this->logger = $logger;
		$this->config = $config;
	}

	/**
	 * @inheritdoc
	 */
	public function get($size = 64) {
		try {
			$file = $this->getFile($size);
		} catch (NotFoundException $e) {
			return false;
		}

		$avatar = new OC_Image();
		$avatar->loadFromData($file->getContent());
		return $avatar;
	}

	/**
	 * Check if an avatar exists for the user
	 *
	 * @return bool
	 */
	public function exists() {

		return $this->folder->fileExists('avatar.jpg') || $this->folder->fileExists('avatar.png');
	}

	/**
	 * Check if the avatar of a user is a custom uploaded one
	 *
	 * @return bool
	 */
	public function isCustomAvatar(): bool {
		return $this->config->getUserValue($this->user->getUID(), 'avatar', 'generated', 'false') !== 'true';
	}

	/**
	 * sets the users avatar
	 * @param IImage|resource|string $data An image object, imagedata or path to set a new avatar
	 * @throws \Exception if the provided file is not a jpg or png image
	 * @throws \Exception if the provided image is not valid
	 * @throws NotSquareException if the image is not square
	 * @return void
	 */
	public function set($data) {

		if ($data instanceof IImage) {
			$img = $data;
			$data = $img->data();
		} else {
			$img = new OC_Image();
			if (is_resource($data) && get_resource_type($data) === "gd") {
				$img->setResource($data);
			} elseif (is_resource($data)) {
				$img->loadFromFileHandle($data);
			} else {
				try {
					// detect if it is a path or maybe the images as string
					$result = @realpath($data);
					if ($result === false || $result === null) {
						$img->loadFromData($data);
					} else {
						$img->loadFromFile($data);
					}
				} catch (\Error $e) {
					$img->loadFromData($data);
				}
			}
		}
		$type = substr($img->mimeType(), -3);
		if ($type === 'peg') {
			$type = 'jpg';
		}
		if ($type !== 'jpg' && $type !== 'png') {
			throw new \Exception($this->l->t('Unknown filetype'));
		}

		if (!$img->valid()) {
			throw new \Exception($this->l->t('Invalid image'));
		}

		if (!($img->height() === $img->width())) {
			throw new NotSquareException($this->l->t('Avatar image is not square'));
		}

		$this->remove();
		$file = $this->folder->newFile('avatar.' . $type);
		$file->putContent($data);

		try {
			$generated = $this->folder->getFile('generated');
			$this->config->setUserValue($this->user->getUID(), 'avatar', 'generated', 'false');
			$generated->delete();
		} catch (NotFoundException $e) {
			//
		}
		$this->user->triggerChange('avatar', $file);
	}

	/**
	 * remove the users avatar
	 * @return void
	 */
	public function remove() {
		$avatars = $this->folder->getDirectoryListing();

		$this->config->setUserValue($this->user->getUID(), 'avatar', 'version',
			(int) $this->config->getUserValue($this->user->getUID(), 'avatar', 'version', 0) + 1);

		foreach ($avatars as $avatar) {
			$avatar->delete();
		}
		$this->config->setUserValue($this->user->getUID(), 'avatar', 'generated', 'true');
		$this->user->triggerChange('avatar', '');
	}

	/**
	 * @inheritdoc
	 */
	public function getFile($size) {
		try {
			$ext = $this->getExtension();
		} catch (NotFoundException $e) {
			if (!$data = $this->generateAvatarFromSvg(1024)) {
				$data = $this->generateAvatar($this->user->getDisplayName(), 1024);
			}
			$avatar = $this->folder->newFile('avatar.png');
			$avatar->putContent($data);
			$ext = 'png';

			$this->folder->newFile('generated');
			$this->config->setUserValue($this->user->getUID(), 'avatar', 'generated', 'true');
		}

		if ($size === -1) {
			$path = 'avatar.' . $ext;
		} else {
			$path = 'avatar.' . $size . '.' . $ext;
		}

		try {
			$file = $this->folder->getFile($path);
		} catch (NotFoundException $e) {
			if ($size <= 0) {
				throw new NotFoundException;
			}

			if ($this->folder->fileExists('generated')) {
				if (!$data = $this->generateAvatarFromSvg($size)) {
					$data = $this->generateAvatar($this->user->getDisplayName(), $size);
				}

			} else {
				$avatar = new OC_Image();
				/** @var ISimpleFile $file */
				$file = $this->folder->getFile('avatar.' . $ext);
				$avatar->loadFromData($file->getContent());
				$avatar->resize($size);
				$data = $avatar->data();
			}

			try {
				$file = $this->folder->newFile($path);
				$file->putContent($data);
			} catch (NotPermittedException $e) {
				$this->logger->error('Failed to save avatar for ' . $this->user->getUID());
				throw new NotFoundException();
			}

		}

		if ($this->config->getUserValue($this->user->getUID(), 'avatar', 'generated', null) === null) {
			$generated = $this->folder->fileExists('generated') ? 'true' : 'false';
			$this->config->setUserValue($this->user->getUID(), 'avatar', 'generated', $generated);
		}

		return $file;
	}

	/**
	 * Get the extension of the avatar. If there is no avatar throw Exception
	 *
	 * @return string
	 * @throws NotFoundException
	 */
	private function getExtension() {
		if ($this->folder->fileExists('avatar.jpg')) {
			return 'jpg';
		} elseif ($this->folder->fileExists('avatar.png')) {
			return 'png';
		}
		throw new NotFoundException;
	}

	/**
	 * {size} = 500
	 * {fill} = hex color to fill
	 * {letter} = Letter to display
	 *
	 * Generate SVG avatar
	 * @return string
	 *
	 */
	private function getAvatarVector(int $size): string {
		$userDisplayName = $this->user->getDisplayName();

		$bgRGB = $this->avatarBackgroundColor($userDisplayName);
		$bgHEX = sprintf("%02x%02x%02x", $bgRGB->r, $bgRGB->g, $bgRGB->b);
		$letter = mb_strtoupper(mb_substr($userDisplayName, 0, 1), 'UTF-8');

		$toReplace = ['{size}', '{fill}', '{letter}'];
		return str_replace($toReplace, [$size, $bgHEX, $letter], $this->svgTemplate);
	}

	/**
	 * Generate png avatar from svg with Imagick
	 *
	 * @param int $size
	 * @return string|boolean
	 */
	private function generateAvatarFromSvg(int $size) {
		if (!extension_loaded('imagick')) {
			return false;
		}
		try {
			$font = __DIR__ . '/../../core/fonts/Nunito-Regular.ttf';
			$svg = $this->getAvatarVector($size);
			$avatar = new Imagick();
			$avatar->setFont($font);
			$avatar->readImageBlob($svg);
			$avatar->setImageFormat('png');
			$image = new OC_Image();
			$image->loadFromData($avatar);
			return $image->data();
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Generate png avatar with GD
	 *
	 * @param string $userDisplayName
	 * @param int $size
	 * @return string
	 */
	private function generateAvatar($userDisplayName, $size) {
		$text = mb_strtoupper(mb_substr($userDisplayName, 0, 1), 'UTF-8');
		$backgroundColor = $this->avatarBackgroundColor($userDisplayName);

		$im = imagecreatetruecolor($size, $size);
		$background = imagecolorallocate($im, $backgroundColor->r, $backgroundColor->g, $backgroundColor->b);
		$white = imagecolorallocate($im, 255, 255, 255);
		imagefilledrectangle($im, 0, 0, $size, $size, $background);

		$font = __DIR__ . '/../../core/fonts/Nunito-Regular.ttf';

		$fontSize = $size * 0.4;

		list($x, $y) = $this->imageTTFCenter($im, $text, $font, $fontSize);

		imagettftext($im, $fontSize, 0, $x, $y, $white, $font, $text);

		ob_start();
		imagepng($im);
		$data = ob_get_contents();
		ob_end_clean();

		return $data;
	}

	/**
	 * Calculate real image ttf center
	 *
	 * @param resource $image
	 * @param string $text text string
	 * @param string $font font path
	 * @param int $size font size
	 * @param int $angle
	 * @return array
	 */
	protected function imageTTFCenter($image, string $text, string $font, int $size, $angle = 0): array {
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

		return array($x, $y);
	}

	/**
	 * Calculate steps between two Colors
	 * @param object Color $steps start color
	 * @param object Color $ends end color
	 * @return array [r,g,b] steps for each color to go from $steps to $ends
	 */
	private function stepCalc($steps, $ends) {
		$step = array();
		$step[0] = ($ends[1]->r - $ends[0]->r) / $steps;
		$step[1] = ($ends[1]->g - $ends[0]->g) / $steps;
		$step[2] = ($ends[1]->b - $ends[0]->b) / $steps;
		return $step;
	}

	/**
	 * Convert a string to an integer evenly
	 * @param string $hash the text to parse
	 * @param int $maximum the maximum range
	 * @return int between 0 and $maximum
	 */
	private function mixPalette($steps, $color1, $color2) {
		$count = $steps + 1;
		$palette = array($color1);
		$step = $this->stepCalc($steps, [$color1, $color2]);
		for ($i = 1; $i < $steps; $i++) {
			$r = intval($color1->r + ($step[0] * $i));
			$g = intval($color1->g + ($step[1] * $i));
			$b = intval($color1->b + ($step[2] * $i));
			$palette[] = new Color($r, $g, $b);
		}
		return $palette;
	}

	/**
	 * Convert a string to an integer evenly
	 * @param string $hash the text to parse
	 * @param int $maximum the maximum range
	 * @return int between 0 and $maximum
	 */
	private function hashToInt($hash, $maximum) {
		$final = 0;
		$result = array();

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
	 * @param string $hash
	 * @return Color Object containting r g b int in the range [0, 255]
	 */
	public function avatarBackgroundColor(string $hash) {
		// Normalize hash
		$hash = strtolower($hash);

		// Already a md5 hash?
		if( preg_match('/^([0-9a-f]{4}-?){8}$/', $hash, $matches) !== 1 ) {
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

		$palette1 = $this->mixPalette($steps, $red, $yellow);
		$palette2 = $this->mixPalette($steps, $yellow, $blue);
		$palette3 = $this->mixPalette($steps, $blue, $red);

		$finalPalette = array_merge($palette1, $palette2, $palette3);

		return $finalPalette[$this->hashToInt($hash, $steps * 3)];
	}

	public function userChanged($feature, $oldValue, $newValue) {
		// We only change the avatar on display name changes
		if ($feature !== 'displayName') {
			return;
		}

		// If the avatar is not generated (so an uploaded image) we skip this
		if (!$this->folder->fileExists('generated')) {
			return;
		}

		$this->remove();
	}

}
