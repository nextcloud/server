<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
			$img = new OC_Image($data);
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
		$background = imagecolorallocate($im, $backgroundColor[0], $backgroundColor[1], $backgroundColor[2]);
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
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @return double[] Array containing h s l in [0, 1] range
	 */
	private function rgbToHsl($r, $g, $b) {
		$r /= 255.0;
		$g /= 255.0;
		$b /= 255.0;

		$max = max($r, $g, $b);
		$min = min($r, $g, $b);


		$h = ($max + $min) / 2.0;
		$l = ($max + $min) / 2.0;

		if($max === $min) {
			$h = $s = 0; // Achromatic
		} else {
			$d = $max - $min;
			$s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
			switch($max) {
				case $r:
					$h = ($g - $b) / $d + ($g < $b ? 6 : 0);
					break;
				case $g:
					$h = ($b - $r) / $d + 2.0;
					break;
				case $b:
					$h = ($r - $g) / $d + 4.0;
					break;
			}
			$h /= 6.0;
		}
		return [$h, $s, $l];

	}

	/**
	 * @param string $text
	 * @return int[] Array containting r g b in the range [0, 255]
	 */
	private function avatarBackgroundColor($text) {
		$hash = preg_replace('/[^0-9a-f]+/', '', $text);

		$hash = md5($hash);
		$hashChars = str_split($hash);


		// Init vars
		$result = ['0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0'];
		$rgb = [0, 0, 0];
		$sat = 0.70;
		$lum = 0.68;
		$modulo = 16;


		// Splitting evenly the string
		foreach($hashChars as  $i => $char) {
			$result[$i % $modulo] .= intval($char, 16);
		}

		// Converting our data into a usable rgb format
		// Start at 1 because 16%3=1 but 15%3=0 and makes the repartition even
		for($count = 1; $count < $modulo; $count++) {
			$rgb[$count%3] += (int)$result[$count];
		}

		// Reduce values bigger than rgb requirements
		$rgb[0] %= 255;
		$rgb[1] %= 255;
		$rgb[2] %= 255;

		$hsl = $this->rgbToHsl($rgb[0], $rgb[1], $rgb[2]);

		// Classic formula to check the brightness for our eye
		// If too bright, lower the sat
		$bright = sqrt(0.299 * ($rgb[0] ** 2) + 0.587 * ($rgb[1] ** 2) + 0.114 * ($rgb[2] ** 2));
		if ($bright >= 200) {
			$sat = 0.60;
		}

		return $this->hslToRgb($hsl[0], $sat, $lum);
	}

	/**
	 * @param double $h Hue in range [0, 1]
	 * @param double $s Saturation in range [0, 1]
	 * @param double $l Lightness in range [0, 1]
	 * @return int[] Array containing r g b in the range [0, 255]
	 */
	private function hslToRgb($h, $s, $l){
		$hue2rgb = function ($p, $q, $t){
			if($t < 0) {
				$t += 1;
			}
			if($t > 1) {
				$t -= 1;
			}
			if($t < 1/6) {
				return $p + ($q - $p) * 6 * $t;
			}
			if($t < 1/2) {
				return $q;
			}
			if($t < 2/3) {
				return $p + ($q - $p) * (2/3 - $t) * 6;
			}
			return $p;
		};

		if($s === 0){
			$r = $l;
			$g = $l;
			$b = $l; // achromatic
		}else{
			$q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
			$p = 2 * $l - $q;
			$r = $hue2rgb($p, $q, $h + 1/3);
			$g = $hue2rgb($p, $q, $h);
			$b = $hue2rgb($p, $q, $h - 1/3);
		}

		return array(round($r * 255), round($g * 255), round($b * 255));
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
