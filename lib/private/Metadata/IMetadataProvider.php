<?php

namespace OC\Metadata;

use OCP\Files\File;

/**
 * Interface for the metadata providers. If you want an application to provide
 * some metadata, you can use this to store them.
 */
interface IMetadataProvider {
	/**
	 * The list of groups that this metadata provider is able to provide.
	 *
	 * @return string[]
	 */
	public static function groupsProvided(): array;

	/**
	 * Check if the metadata provider is available. A metadata provider might be
	 * unavailable due to a php extension not being installed.
	 */
	public static function isAvailable(): bool;

	/**
	 * Get the mimetypes supported as a regex.
	 */
	public static function getMimetypesSupported(): string;

	/**
	 * Execute the extraction on the specified file. The metadata should be
	 * grouped by metadata
	 *
	 * Each group should be json serializable and the string representation
	 * shouldn't be longer than 4000 characters.
	 *
	 * @param File $file The file to extract the metadata from
	 * @param array<string, FileMetadata> An array containing all the metadata fetched.
	 */
	public function execute(File $file): array;
}
