<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP;

/**
 * This class provides access to the internal filesystem abstraction layer. Use
 * this class exclusively if you want to access files
 * @since 5.0.0
 * @deprecated 14.0.0
 */
class Files {
	/**
	 * Recursive deletion of folders
	 * @return bool
	 * @since 5.0.0
	 * @deprecated 14.0.0
	 */
	public static function rmdirr($dir) {
		return \OC_Helper::rmdirr($dir);
	}

	/**
	 * Get the mimetype form a local file
	 * @param string $path
	 * @return string
	 *                does NOT work for ownClouds filesystem, use OC_FileSystem::getMimeType instead
	 * @since 5.0.0
	 * @deprecated 14.0.0
	 */
	public static function getMimeType($path) {
		return \OC::$server->getMimeTypeDetector()->detect($path);
	}

	/**
	 * Search for files by mimetype
	 * @param string $mimetype
	 * @return array
	 * @since 6.0.0
	 * @deprecated 14.0.0
	 */
	public static function searchByMime($mimetype) {
		return \OC\Files\Filesystem::searchByMime($mimetype);
	}

	/**
	 * Copy the contents of one stream to another
	 * @param resource $source
	 * @param resource $target
	 * @return int the number of bytes copied
	 * @since 5.0.0
	 * @deprecated 14.0.0
	 */
	public static function streamCopy($source, $target) {
		[$count, ] = \OC_Helper::streamCopy($source, $target);
		return $count;
	}

	/**
	 * Adds a suffix to the name in case the file exists
	 * @param string $path
	 * @param string $filename
	 * @return string
	 * @since 5.0.0
	 * @deprecated 14.0.0 use getNonExistingName of the OCP\Files\Folder object
	 */
	public static function buildNotExistingFileName($path, $filename) {
		return \OC_Helper::buildNotExistingFileName($path, $filename);
	}

	/**
	 * Gets the Storage for an app - creates the needed folder if they are not
	 * existent
	 * @param string $app
	 * @return \OC\Files\View
	 * @since 5.0.0
	 * @deprecated 14.0.0 use IAppData instead
	 */
	public static function getStorage($app) {
		return \OC_App::getStorage($app);
	}
}
