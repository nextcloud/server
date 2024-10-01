<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\FilesMetadata;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Node;
use OCP\FilesMetadata\Exceptions\FilesMetadataException;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\Model\IFilesMetadata;
use OCP\FilesMetadata\Model\IMetadataValueWrapper;

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
	/** @since 28.0.0 */
	public const PROCESS_NAMED = 4;

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
	 * @param string $namedEvent limit process to a named event
	 *
	 * @return IFilesMetadata
	 * @see self::PROCESS_BACKGROUND
	 * @see self::PROCESS_LIVE
	 * @see self::PROCESS_NAMED
	 * @since 28.0.0
	 */
	public function refreshMetadata(
		Node $node,
		int $process = self::PROCESS_LIVE,
		string $namedEvent = '',
	): IFilesMetadata;

	/**
	 * returns metadata of a file id
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
	 * returns metadata of multiple file ids
	 *
	 * @param int[] $fileIds file ids
	 *
	 * @return array File ID is the array key, files without metadata are not returned in the array
	 * @psalm-return array<int, IFilesMetadata>
	 * @since 28.0.0
	 */
	public function getMetadataForFiles(array $fileIds): array;

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
		string $fileIdField,
	): IMetadataQuery;

	/**
	 * returns all type of metadata currently available.
	 * The list is stored in a IFilesMetadata with null values but correct type.
	 *
	 * Note: this method loads lazy appconfig values.
	 *
	 * @return IFilesMetadata
	 * @since 28.0.0
	 */
	public function getKnownMetadata(): IFilesMetadata;

	/**
	 * Initiate a metadata key with its type.
	 *
	 * The call is mandatory before using the metadata property in a webdav request.
	 * The call should be part of a migration/repair step and not be called on app's boot
	 * process as it is using lazy-appconfig value
	 *
	 * Note: this method loads lazy appconfig values.
	 *
	 * @param string $key metadata key
	 * @param string $type metadata type
	 * @param bool $indexed TRUE if metadata can be search
	 * @param int $editPermission remote edit permission via Webdav PROPPATCH
	 *
	 * @see IMetadataValueWrapper::TYPE_INT
	 * @see IMetadataValueWrapper::TYPE_FLOAT
	 * @see IMetadataValueWrapper::TYPE_BOOL
	 * @see IMetadataValueWrapper::TYPE_ARRAY
	 * @see IMetadataValueWrapper::TYPE_STRING_LIST
	 * @see IMetadataValueWrapper::TYPE_INT_LIST
	 * @see IMetadataValueWrapper::TYPE_STRING
	 * @see IMetadataValueWrapper::EDIT_FORBIDDEN
	 * @see IMetadataValueWrapper::EDIT_REQ_OWNERSHIP
	 * @see IMetadataValueWrapper::EDIT_REQ_WRITE_PERMISSION
	 * @see IMetadataValueWrapper::EDIT_REQ_READ_PERMISSION
	 * @since 28.0.0
	 * @since 29.0.0 uses lazy config value - do not use this method out of repair steps
	 */
	public function initMetadata(string $key, string $type, bool $indexed, int $editPermission): void;
}
