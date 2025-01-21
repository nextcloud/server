<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2017 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Avatars;

use OCP\IAvatar;
use Sabre\DAV\File;

class AvatarNode extends File {
	/**
	 * AvatarNode constructor.
	 *
	 * @param integer $size
	 * @param string $ext
	 * @param IAvatar $avatar
	 */
	public function __construct(
		private $size,
		private $ext,
		private $avatar,
	) {
	}

	/**
	 * Returns the name of the node.
	 *
	 * This is used to generate the url.
	 *
	 * @return string
	 */
	public function getName() {
		return "$this->size.$this->ext";
	}

	public function get() {
		$image = $this->avatar->get($this->size);
		$res = $image->resource();

		ob_start();
		if ($this->ext === 'png') {
			imagepng($res);
		} else {
			imagejpeg($res);
		}

		return ob_get_clean();
	}

	/**
	 * Returns the mime-type for a file
	 *
	 * If null is returned, we'll assume application/octet-stream
	 *
	 * @return string|null
	 */
	public function getContentType() {
		if ($this->ext === 'png') {
			return 'image/png';
		}
		return 'image/jpeg';
	}

	public function getETag() {
		return $this->avatar->getFile($this->size)->getEtag();
	}

	public function getLastModified() {
		return $this->avatar->getFile($this->size)->getMTime();
	}
}
