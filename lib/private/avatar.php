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
		if ($this->folder->nodeExists('avatar.jpg')) {
			$ext = 'jpg';
		} elseif ($this->folder->nodeExists('avatar.png')) {
			$ext = 'png';
		} else {
			return false;
		}

		$avatar = new OC_Image();
		if ($this->folder->nodeExists('avatar.' . $size . '.' . $ext)) {
			/** @var File $node */
			$node = $this->folder->get('avatar.' . $size . '.' . $ext);
			$avatar->loadFromData($node->getContent());
		} else {
			/** @var File $node */
			$node = $this->folder->get('avatar.' . $ext);
			$avatar->loadFromData($node->getContent());
			$avatar->resize($size);
			$this->folder->newFile('avatar.' . $size . '.' . $ext)->putContent($avatar->data());
		}
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
		try {
			$this->folder->get('avatar.jpg')->delete();
		} catch (\OCP\Files\NotFoundException $e) {}
		try {
			$this->folder->get('avatar.png')->delete();
		} catch (\OCP\Files\NotFoundException $e) {}
	}
}
