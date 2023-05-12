<?php
/**
 * @copyright Copyright (c) 2016 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
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
