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

use OC\User\User;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IAvatar;
use OCP\IConfig;
use OCP\IImage;
use OCP\IL10N;
use OC_Image;
use OCP\ILogger;

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
	public function get ($size = 64) {
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
	 * sets the users avatar
	 * @param IImage|resource|string $data An image object, imagedata or path to set a new avatar
	 * @throws \Exception if the provided file is not a jpg or png image
	 * @throws \Exception if the provided image is not valid
	 * @throws NotSquareException if the image is not square
	 * @return void
	*/
	public function set ($data) {

		if($data instanceOf IImage) {
			$img = $data;
			$data = $img->data();
		} else {
			$img = new OC_Image();
			if (is_resource($data) && get_resource_type($data) === "gd") {
				$img->setResource($data);
			} elseif(is_resource($data)) {
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
		$file = $this->folder->newFile('avatar.'.$type);
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
	public function remove () {
		$avatars = $this->folder->getDirectoryListing();

		$this->config->setUserValue($this->user->getUID(), 'avatar', 'version',
			(int)$this->config->getUserValue($this->user->getUID(), 'avatar', 'version', 0) + 1);

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
			$data = $this->generateAvatar($this->user->getDisplayName(), 1024);
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
				$data = $this->generateAvatar($this->user->getDisplayName(), $size);

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

		if($this->config->getUserValue($this->user->getUID(), 'avatar', 'generated', null) === null) {
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

		$font = __DIR__ . '/../../core/fonts/OpenSans-Semibold.ttf';

		$fontSize = $size * 0.4;
		$box = imagettfbbox($fontSize, 0, $font, $text);

		$x = ($size - ($box[2] - $box[0])) / 2;
		$y = ($size - ($box[1] - $box[7])) / 2;
		$x += 1;
		$y -= $box[7];
		imagettftext($im, $fontSize, 0, $x, $y, $white, $font, $text);

		ob_start();
		imagepng($im);
		$data = ob_get_contents();
		ob_end_clean();

		return $data;
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
		for ($i=0; $i< strlen($hash); $i++) {
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
	 * @param string $text
	 * @return Color Object containting r g b int in the range [0, 255]
	 */
	function avatarBackgroundColor($text) {
		$hash = preg_replace('/[^0-9a-f]+/', '', $text);

		$hash = md5($hash);
		$hashChars = str_split($hash);

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

		return $finalPalette[$this->hashToInt($hash, $steps * 3 )];
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
