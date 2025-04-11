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
 * Interface to handle MIME type (detection and icon retrieval)
 **/
interface IMimeTypeDetector {
	/**
	 * Detect MIME type only based on filename, content of file is not used
	 * @param string $path
	 * @return string
	 * @since 8.2.0
	 */
	public function detectPath($path);

	/**
	 * Detect MIME type only based on the content of file
	 * @param string $path
	 * @return string
	 * @since 18.0.0
	 */
	public function detectContent(string $path): string;

	/**
	 * Detect MIME type based on both filename and content
	 *
	 * @param string $path
	 * @return string
	 * @since 8.2.0
	 */
	public function detect($path);

	/**
	 * Get a secure MIME type that won't expose potential XSS.
	 *
	 * @param string $mimeType
	 * @return string
	 * @since 8.2.0
	 */
	public function getSecureMimeType($mimeType);

	/**
	 * Detect MIME type based on the content of a string
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

	/**
	 * Get all extension to MIME type mappings.
	 *
	 * The return format is an array of the file extension, as the key,
	 * mapped to a list where the first entry is the MIME type
	 * and the second entry is the secure MIME type (or null if none).
	 * Due to PHP idiosyncrasies if a numeric string is set as the extension,
	 * then also the array key (file extension) is a number instead of a string.
	 *
	 * @return array<list{string, string|null}>
	 * @since 32.0.0
	 */
	public function getAllMappings(): array;
}
