<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP\Files;

/**
 * Interface IMimeTypeDetector
 * @since 8.2.0
 *
 * Interface to handle mimetypes (detection and icon retrieval)
 **/
interface IMimeTypeDetector {
	/**
	 * detect mimetype only based on filename, content of file is not used
	 * @param string $path
	 * @return string
	 * @since 8.2.0
	 */
	public function detectPath($path);

	/**
	 * detect mimetype only based on the content of file
	 * @param string $path
	 * @return string
	 * @since 18.0.0
	 */
	public function detectContent(string $path): string;

	/**
	 * detect mimetype based on both filename and content
	 *
	 * @param string $path
	 * @return string
	 * @since 8.2.0
	 */
	public function detect($path);

	/**
	 * Get a secure mimetype that won't expose potential XSS.
	 *
	 * @param string $mimeType
	 * @return string
	 * @since 8.2.0
	 */
	public function getSecureMimeType($mimeType);

	/**
	 * detect mimetype based on the content of a string
	 *
	 * @param string $data
	 * @return string
	 * @since 8.2.0
	 */
	public function detectString($data);

	/**
	 * Get path to the icon of a file type
	 * @param string $mimeType the MIME type
	 * @return string the url
	 * @since 8.2.0
	 */
	public function mimeTypeIcon($mimeType);

	/**
	 * @return array<string,string>
	 * @since 28.0.0
	 */
	public function getAllAliases(): array;
}
