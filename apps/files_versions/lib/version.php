<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Versions;

use OCP\Files\File;

class Version {
	/**
	 * @var \OCP\Files\File
	 */
	private $sourceFile;

	/**
	 * @var \OCP\Files\File
	 */
	private $versionFile;

	/**
	 * @var int
	 */
	private $mtime;

	/**
	 * @param File $sourceFile
	 * @param File $versionFile
	 * @param int $mtime
	 */
	public function __construct(File $sourceFile, File $versionFile, $mtime) {
		$this->sourceFile = $sourceFile;
		$this->versionFile = $versionFile;
		$this->mtime = $mtime;
	}

	/**
	 * @return \OCP\Files\File
	 */
	public function getSourceFile() {
		return $this->sourceFile;
	}

	/**
	 * @return \OCP\Files\File
	 */
	public function getVersionFile() {
		return $this->versionFile;
	}

	/**
	 * @return int
	 */
	public function getMtime() {
		return $this->mtime;
	}
}
