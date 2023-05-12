<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
 * @author luz paz <luzpaz@github.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
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
