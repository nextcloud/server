<?php

declare(strict_types=1);
/**
 * @copyright 2023 Maxence Lange <maxence@artificial-owl.com>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
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

namespace OCP\FilesMetadata;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Node;
use OCP\FilesMetadata\Exceptions\FilesMetadataException;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\Model\IFilesMetadata;
use OCP\FilesMetadata\Model\IMetadataQuery;

/**
 * Manager for FilesMetadata; manage files' metadata.
 *
 * @since 28.0.0
 */
interface IFilesMetadataManager {
	/** @since 28.0.0 */
	public const PROCESS_LIVE = 1;
	/** @since 28.0.0 */
	public const PROCESS_BACKGROUND = 2;

	/**
	 * initiate the process of refreshing the metadata in relation to a node
	 * usually, this process:
	 * - get current metadata from database, if available, or create a new one
	 * - dispatch a MetadataLiveEvent,
	 * - save new metadata in database, if metadata have been changed during the event
	 * - refresh metadata indexes if needed,
	 * - prep a new cronjob if an app request it during the event,
	 *
	 * @param Node $node related node
	 * @param int $process type of process
	 *
	 * @return IFilesMetadata
	 * @see self::PROCESS_BACKGROUND
	 * @see self::PROCESS_LIVE
	 * @since 28.0.0
	 */
	public function refreshMetadata(
		Node $node,
		int $process = self::PROCESS_LIVE
	): IFilesMetadata;

	/**
	 * returns metadata from a file id
	 *
	 * @param int $fileId file id
	 * @param boolean $generate Generate if metadata does not exist
	 *
	 * @return IFilesMetadata
	 * @throws FilesMetadataNotFoundException if not found
	 * @since 28.0.0
	 */
	public function getMetadata(int $fileId, bool $generate = false): IFilesMetadata;

	/**
	 * save metadata to database and refresh indexes.
	 * metadata are saved if new data are available.
	 * on update, a check on syncToken is done to avoid conflict (race condition)
	 *
	 * @param IFilesMetadata $filesMetadata
	 *
	 * @throws FilesMetadataException if metadata seems malformed
	 * @since 28.0.0
	 */
	public function saveMetadata(IFilesMetadata $filesMetadata): void;

	/**
	 * delete metadata and its indexes
	 *
	 * @param int $fileId file id
	 *
	 * @return void
	 * @since 28.0.0
	 */
	public function deleteMetadata(int $fileId): void;

	/**
	 * generate and return a MetadataQuery to help building sql queries
	 *
	 * @param IQueryBuilder $qb
	 * @param string $fileTableAlias alias of the table that contains data about files
	 * @param string $fileIdField alias of the field that contains file ids
	 *
	 * @return IMetadataQuery
	 * @see IMetadataQuery
	 * @since 28.0.0
	 */
	public function getMetadataQuery(
		IQueryBuilder $qb,
		string $fileTableAlias,
		string $fileIdField
	): IMetadataQuery;

	/**
	 * returns all type of metadata currently available.
	 * The list is stored in a IFilesMetadata with null values but correct type.
	 *
	 * @return IFilesMetadata
	 * @since 28.0.0
	 */
	public function getKnownMetadata(): IFilesMetadata;

	/**
	 * initiate a metadata key with its type.
	 * The call is mandatory before using the metadata property in a webdav request.
	 * It is not needed to only use this method when the app is enabled: the method can be
	 * called each time during the app loading as the metadata will only be initiated if not known
	 *
	 * @param string $key metadata key
	 * @param string $type metadata type
	 * @param bool $indexed TRUE if metadata can be search
	 * @since 28.0.0
	 */
	public function initMetadata(string $key, string $type, bool $indexed): void;
}
