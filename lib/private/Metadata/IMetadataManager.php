<?php

declare(strict_types=1);

namespace OC\Metadata;

use OCP\Files\File;

/**
 * Interface to manage additional metadata for files
 */
interface IMetadataManager {
	/**
	 * @param class-string<IMetadataProvider> $className
	 */
	public function registerProvider(string $className): void;

	/**
	 * Generate the metadata for one file
	 */
	public function generateMetadata(File $file, bool $checkExisting = false): void;

	/**
	 * Clear the metadata for one file
	 */
	public function clearMetadata(int $fileId): void;

	/** @return array<int, FileMetadata> */
	public function fetchMetadataFor(string $group, array $fileIds): array;

	/**
	 * Get the capabilities as an array of mimetype regex to the type provided
	 */
	public function getCapabilities(): array;
}
