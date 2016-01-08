<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

use OCP\Files\Folder;
use OCP\Files\File;
use OCP\Files\NotFoundException;
use OCP\IL10N;
use OC_Image;

/**
 * This class gets and sets users avatars.
 */

class Avatar implements \OCP\IAvatar {
	/** @var Folder */
	private $folder;

	/** @var IL10N */
	private $l;

	/**
	 * constructor
	 *
	 * @param Folder $folder The folder where the avatars are
	 * @param IL10N $l
	 */
	public function __construct (Folder $folder, IL10N $l) {
		$this->folder = $folder;
		$this->l = $l;
	}

	/**
	 * get the users avatar
	 * @param int $size size in px of the avatar, avatars are square, defaults to 64
	 * @return boolean|\OCP\IImage containing the avatar or false if there's no image
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
		return $this->folder->nodeExists('avatar.jpg') || $this->folder->nodeExists('avatar.png');
	}

	/**
	 * sets the users avatar
	 * @param \OCP\IImage|resource|string $data An image object, imagedata or path to set a new avatar
	 * @throws \Exception if the provided file is not a jpg or png image
	 * @throws \Exception if the provided image is not valid
	 * @throws \OC\NotSquareException if the image is not square
	 * @return void
	*/
	public function set ($data) {

		if($data instanceOf \OCP\IImage) {
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
			throw new \Exception($this->l->t("Unknown filetype"));
		}

		if (!$img->valid()) {
			throw new \Exception($this->l->t("Invalid image"));
		}

		if (!($img->height() === $img->width())) {
			throw new \OC\NotSquareException();
		}

		$this->remove();
		$this->folder->newFile('avatar.'.$type)->putContent($data);
	}

	/**
	 * remove the users avatar
	 * @return void
	*/
	public function remove () {
		$regex = '/^avatar\.([0-9]+\.)?(jpg|png)$/';
		$avatars = $this->folder->search('avatar');

		foreach ($avatars as $avatar) {
			if (preg_match($regex, $avatar->getName())) {
				$avatar->delete();
			}
		}
	}

	/**
	 * Get the File of an avatar of size $size.
	 *
	 * @param int $size
	 * @return File
	 * @throws NotFoundException
	 */
	public function getFile($size) {
		$ext = $this->getExtention();

		$path = 'avatar.' . $size . '.' . $ext;

		try {
			$file = $this->folder->get($path);
		} catch (NotFoundException $e) {
			if ($size <= 0) {
				throw new NotFoundException;
			}

			$avatar = new OC_Image();
			/** @var File $file */
			$file = $this->folder->get('avatar.' . $ext);
			$avatar->loadFromData($file->getContent());
			$avatar->resize($size);
			$file = $this->folder->newFile($path);
			$file->putContent($avatar->data());
		}

		return $file;
	}

	/**
	 * Get the extention of the avatar. If there is no avatar throw Exception
	 *
	 * @return string
	 * @throws NotFoundException
	 */
	private function getExtention() {
		if ($this->folder->nodeExists('avatar.jpg')) {
			return 'jpg';
		} elseif ($this->folder->nodeExists('avatar.png')) {
			return 'png';
		}
		throw new NotFoundException;
	}
}
