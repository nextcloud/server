<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2017 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Avatars;

use OCP\IAvatarManager;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;
use Sabre\Uri;

class AvatarHome implements ICollection {

	/**
	 * AvatarHome constructor.
	 *
	 * @param array $principalInfo
	 * @param IAvatarManager $avatarManager
	 */
	public function __construct(
		private $principalInfo,
		private IAvatarManager $avatarManager,
	) {
	}

	public function createFile($name, $data = null) {
		throw new Forbidden('Permission denied to create a file');
	}

	public function createDirectory($name) {
		throw new Forbidden('Permission denied to create a folder');
	}

	public function getChild($name) {
		$elements = pathinfo($name);
		$ext = $elements['extension'] ?? '';
		$size = (int)($elements['filename'] ?? '64');
		if (!in_array($ext, ['jpeg', 'png'], true)) {
			throw new MethodNotAllowed('File format not allowed');
		}
		if ($size <= 0 || $size > 1024) {
			throw new MethodNotAllowed('Invalid image size');
		}
		$avatar = $this->avatarManager->getAvatar($this->getName());
		if (!$avatar->exists()) {
			throw new NotFound();
		}
		return new AvatarNode($size, $ext, $avatar);
	}

	public function getChildren() {
		try {
			return [
				$this->getChild('96.jpeg')
			];
		} catch (NotFound $exception) {
			return [];
		}
	}

	public function childExists($name) {
		try {
			$ret = $this->getChild($name);
			return $ret !== null;
		} catch (NotFound $ex) {
			return false;
		} catch (MethodNotAllowed $ex) {
			return false;
		}
	}

	public function delete() {
		throw new Forbidden('Permission denied to delete this folder');
	}

	public function getName() {
		[,$name] = Uri\split($this->principalInfo['uri']);
		return $name;
	}

	public function setName($name) {
		throw new Forbidden('Permission denied to rename this folder');
	}

	/**
	 * Returns the last modification time, as a unix timestamp
	 *
	 * @return int|null
	 */
	public function getLastModified() {
		return null;
	}
}
