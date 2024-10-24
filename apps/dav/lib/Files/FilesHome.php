<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Files;

use OC\Files\Filesystem;
use OCA\DAV\Connector\Sabre\Directory;
use OCP\Files\FileInfo;
use Sabre\DAV\Exception\Forbidden;

class FilesHome extends Directory {

	/**
	 * FilesHome constructor.
	 *
	 * @param array $principalInfo
	 * @param FileInfo $userFolder
	 */
	public function __construct(
		private $principalInfo,
		FileInfo $userFolder,
	) {
		$view = Filesystem::getView();
		parent::__construct($view, $userFolder);
	}

	public function delete() {
		throw new Forbidden('Permission denied to delete home folder');
	}

	public function getName() {
		[,$name] = \Sabre\Uri\split($this->principalInfo['uri']);
		return $name;
	}

	public function setName($name) {
		throw new Forbidden('Permission denied to rename this folder');
	}
}
