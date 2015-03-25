<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Versions;

use OC\Files\Node\Node;
use OCP\Files\File;
use OCP\Files\Folder;

class Store {
	/**
	 * @var \OCP\Files\Folder
	 */
	private $versionsFolder;

	/**
	 * @var \OCP\Files\Folder
	 */
	private $userFolder;

	/**
	 * Store constructor.
	 *
	 * @param \OCP\Files\Folder $versionsFolder
	 * @param \OCP\Files\Folder $userFolder
	 */
	public function __construct(\OCP\Files\Folder $versionsFolder, \OCP\Files\Folder $userFolder) {
		$this->versionsFolder = $versionsFolder;
		$this->userFolder = $userFolder;
	}

	/**
	 * List all versions stored of a file
	 *
	 * @param \OCP\Files\File $file
	 * @return \OCA\Files_Versions\Version[]
	 */
	public function listVersions(File $file) {
		$versionsFolder = $this->getVersionFolder($file);
		$content = $versionsFolder->getDirectoryListing();
		/** @var \OCP\Files\File[] $files */
		$files = array_filter($content, function (Node $node) {
			return $node instanceof File;
		});
		$versionFiles = array_filter($files, function (File $versionFile) use ($file) {
			$sourceName = pathinfo($versionFile->getName(), PATHINFO_FILENAME);
			return $sourceName === $file->getName();
		});
		return array_map(function (File $versionFile) use ($file) {
			$extension = substr(pathinfo($versionFile->getName(), PATHINFO_EXTENSION), 1);
			$mtime = intval($extension);
			return new Version($file, $versionFile, $mtime);
		}, $versionFiles);
	}

	/**
	 * List all versions for the user
	 *
	 * @return \OCA\Files_Versions\Version[]
	 */
	public function listAllVersions() {
		$versions = [];
		/**
		 * @var \OCP\Files\Folder[]
		 */
		$folders = [$this->versionsFolder];
		while (count($folders)) {
			/** @var \OCP\Files\Folder $folder */
			$folder = array_shift($folders);
			$content = $folder->getDirectoryListing();
			/** @var \OCP\Files\File[] $files */
			$files = array_filter($content, function (Node $node) {
				return $node instanceof File;
			});
			$folders = array_merge($folders, $files = array_filter($content, function (Node $node) {
				return $node instanceof Folder;
			}));
			$fileVersions = array_map(function (File $versionFile) {
				$extension = substr(pathinfo($versionFile->getName(), PATHINFO_EXTENSION), 1);
				$mtime = intval($extension);
				return new Version(null, $versionFile, $mtime);
			}, $files);
			$versions = array_merge_recursive($versions, $fileVersions);
		}
	}

	/**
	 * @param \OCA\Files_Versions\Version $version
	 */
	public function restore(Version $version) {
		// disable proxy to prevent multiple fopen calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		$version->getVersionFile()->move($version->getSourceFile()->getPath());

		// reset proxy state
		\OC_FileProxy::$enabled = $proxyStatus;
	}

	/**
	 * @param \OCA\Files_Versions\Version $version
	 */
	public function remove(Version $version) {
		$version->getVersionFile()->delete();
	}

	/**
	 * @param \OCP\Files\File $file
	 */
	public function removeForFile(File $file) {
		$versions = $this->listVersions($file);
		foreach ($versions as $version) {
			$this->remove($version);
		}
	}

	/**
	 * get the folder to store the version of a file in
	 *
	 * @param \OCP\Files\File $file
	 * @return \OCP\Files\Folder
	 */
	private function getVersionFolder(File $file) {
		$relativePath = $this->userFolder->getRelativePath($file->getPath());
		$targetPath = dirname($relativePath);
		if ($this->versionsFolder->nodeExists($targetPath)) {
			return $this->versionsFolder->get($targetPath);
		} else {
			return $this->versionsFolder->newFolder($targetPath);
		}
	}

	/**
	 * Get the path for a version of the file
	 *
	 * @param \OCP\Files\File $file
	 * @param int $time
	 * @return string
	 */
	private function getTargetName(File $file, $time) {
		return $file->getName() . '.v' . $time;
	}

	/**
	 * @param \OCP\Files\File $file
	 * @return \OCA\Files_Versions\Version
	 */
	public function newVersion(File $file) {
		$targetFolder = $this->getVersionFolder($file);

		// disable proxy to prevent multiple fopen calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// store a new version of a file
		$mtime = $file->getMTime();
		/** @var \OCP\Files\File $storedFile */
		$storedFile = $file->copy($targetFolder->getFullPath($this->getTargetName($file, $mtime)));

		// reset proxy state
		\OC_FileProxy::$enabled = $proxyStatus;

		return new Version($file, $storedFile, $mtime);
	}

	/**
	 * Rename all versions of a file
	 *
	 * @param \OCP\Files\File $source
	 * @param \OCP\Files\File $target
	 */
	public function renameVersions(File $source, File $target) {
		$versions = $this->listVersions($source);
		$targetFolder = $this->getVersionFolder($target);
		foreach ($versions as $version) {
			$name = $this->getTargetName($target, $version->getMtime());
			$version->getVersionFile()->move($targetFolder->getFullPath($name));
		}
	}

	/**
	 * Copy all versions of a file
	 *
	 * @param \OCP\Files\File $source
	 * @param \OCP\Files\File $target
	 */
	public function copyVersions(File $source, File $target) {
		$versions = $this->listVersions($source);
		$targetFolder = $this->getVersionFolder($target);
		foreach ($versions as $version) {
			$name = $this->getTargetName($target, $version->getMtime());
			$version->getVersionFile()->copy($targetFolder->getFullPath($name));
		}
	}

	/**
	 * Get the total size of all stored versions
	 *
	 * @return int
	 */
	public function getSize() {
		return $this->versionsFolder->getSize();
	}
}
