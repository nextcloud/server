<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\IntegrityCheck\Iterator;

/**
 * Class ExcludeFileByNameFilterIterator provides a custom iterator which excludes
 * entries with the specified file name from the file list. These file names are matched exactly.
 *
 * @package OC\Integritycheck\Iterator
 */
class ExcludeFileByNameFilterIterator extends \RecursiveFilterIterator {
	/**
	 * Array of excluded file names. Those are not scanned by the integrity checker.
	 * This is used to exclude files which administrators could upload by mistakes
	 * such as .DS_Store files.
	 *
	 * @var array
	 */
	private $excludedFilenames = [
		'.DS_Store', // Mac OS X
		'.directory', // Dolphin (KDE)
		'.rnd',
		'.webapp', // Gentoo/Funtoo & derivatives use a tool known as webapp-config to manage web-apps.
		'Thumbs.db', // Microsoft Windows
		'nextcloud-init-sync.lock' // Used by nextcloud/docker to prevent running the initialization script on multiple containers at the same time: https://github.com/nextcloud/docker/issues/2299.
	];

	/**
	 * Array of excluded file name parts. Those are not scanned by the integrity checker.
	 * These strings are regular expressions and any file names
	 * matching these expressions are ignored.
	 *
	 * @var array
	 */
	private $excludedFilenamePatterns = [
		'/^\.webapp-nextcloud-(\d+\.){2}(\d+)(-r\d+)?$/', // Gentoo/Funtoo & derivatives use a tool known as webapp-config to manage wep-apps.
	];

	public function accept(): bool {
		/** @var \SplFileInfo $current */
		$current = $this->current();

		if ($current->isDir()) {
			return true;
		}

		$currentFileName = $current->getFilename();
		if (in_array($currentFileName, $this->excludedFilenames, true)) {
			return false;
		}

		foreach ($this->excludedFilenamePatterns as $pattern) {
			if (preg_match($pattern, $currentFileName) > 0) {
				return false;
			}
		}

		return true;
	}
}
