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

namespace OC\FilesMetadata;

use JsonException;
use OC\FilesMetadata\Job\UpdateSingleMetadata;
use OC\FilesMetadata\Listener\MetadataDelete;
use OC\FilesMetadata\Listener\MetadataUpdate;
use OC\FilesMetadata\Model\FilesMetadata;
use OC\FilesMetadata\Service\IndexRequestService;
use OC\FilesMetadata\Service\MetadataRequestService;
use OCP\BackgroundJob\IJobList;
use OCP\DB\Exception;
use OCP\DB\Exception as DBException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Cache\CacheEntryRemovedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\InvalidPathException;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\FilesMetadata\Event\MetadataBackgroundEvent;
use OCP\FilesMetadata\Event\MetadataLiveEvent;
use OCP\FilesMetadata\Event\MetadataNamedEvent;
use OCP\FilesMetadata\Exceptions\FilesMetadataException;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\FilesMetadata\IMetadataQuery;
use OCP\FilesMetadata\Model\IFilesMetadata;
use OCP\FilesMetadata\Model\IMetadataValueWrapper;
use OCP\IConfig;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * @inheritDoc
 * @since 28.0.0
 */
class FilesMetadataManager implements IFilesMetadataManager {
	public const CONFIG_KEY = 'files_metadata';
	public const MIGRATION_DONE = 'files_metadata_installed';
	private const JSON_MAXSIZE = 100000;

	private ?IFilesMetadata $all = null;

	public function __construct(
		private IEventDispatcher $eventDispatcher,
		private IJobList $jobList,
		private IConfig $config,
		private LoggerInterface $logger,
		private MetadataRequestService $metadataRequestService,
		private IndexRequestService $indexRequestService,
	) {
	}

	/**
	 * @inheritDoc
	 *
	 * @param Node $node related node
	 * @param int $process type of process
	 *
	 * @return IFilesMetadata
	 * @throws FilesMetadataException if metadata are invalid
	 * @throws InvalidPathException if path to file is not valid
	 * @throws NotFoundException if file cannot be found
	 * @see self::PROCESS_BACKGROUND
	 * @see self::PROCESS_LIVE
	 * @since 28.0.0
	 */
	public function refreshMetadata(
		Node $node,
		int $process = self::PROCESS_LIVE,
		string $namedEvent = ''
	): IFilesMetadata {
		try {
			$metadata = $this->metadataRequestService->getMetadataFromFileId($node->getId());
		} catch (FilesMetadataNotFoundException) {
			$metadata = new FilesMetadata($node->getId());
		}

		// if $process is LIVE, we enforce LIVE
		// if $process is NAMED, we go NAMED
		// else BACKGROUND
		if ((self::PROCESS_LIVE & $process) !== 0) {
			$event = new MetadataLiveEvent($node, $metadata);
		} elseif ((self::PROCESS_NAMED & $process) !== 0) {
			$event = new MetadataNamedEvent($node, $metadata, $namedEvent);
		} else {
			$event = new MetadataBackgroundEvent($node, $metadata);
		}

		$this->eventDispatcher->dispatchTyped($event);
		$this->saveMetadata($event->getMetadata());

		// if requested, we add a new job for next cron to refresh metadata out of main thread
		// if $process was set to LIVE+BACKGROUND, we run background process directly
		if ($event instanceof MetadataLiveEvent && $event->isRunAsBackgroundJobRequested()) {
			if ((self::PROCESS_BACKGROUND & $process) !== 0) {
				return $this->refreshMetadata($node, self::PROCESS_BACKGROUND);
			}

			$this->jobList->add(UpdateSingleMetadata::class, [$node->getOwner()?->getUID(), $node->getId()]);
		}

		return $metadata;
	}

	/**
	 * @param int $fileId file id
	 * @param boolean $generate Generate if metadata does not exists
	 *
	 * @inheritDoc
	 * @return IFilesMetadata
	 * @throws FilesMetadataNotFoundException if not found
	 * @since 28.0.0
	 */
	public function getMetadata(int $fileId, bool $generate = false): IFilesMetadata {
		try {
			return $this->metadataRequestService->getMetadataFromFileId($fileId);
		} catch (FilesMetadataNotFoundException $ex) {
			if ($generate) {
				return new FilesMetadata($fileId);
			}

			throw $ex;
		}
	}

	/**
	 * returns metadata of multiple file ids
	 *
	 * @param int[] $fileIds file ids
	 *
	 * @return array File ID is the array key, files without metadata are not returned in the array
	 * @psalm-return array<int, IFilesMetadata>
	 * @since 28.0.0
	 */
	public function getMetadataForFiles(array $fileIds): array {
		return $this->metadataRequestService->getMetadataFromFileIds($fileIds);
	}

	/**
	 * @param IFilesMetadata $filesMetadata metadata
	 *
	 * @inheritDoc
	 * @throws FilesMetadataException if metadata seems malformed
	 * @since 28.0.0
	 */
	public function saveMetadata(IFilesMetadata $filesMetadata): void {
		if ($filesMetadata->getFileId() === 0 || !$filesMetadata->updated()) {
			return;
		}

		$json = json_encode($filesMetadata->jsonSerialize());
		if (strlen($json) > self::JSON_MAXSIZE) {
			throw new FilesMetadataException('json cannot exceed ' . self::JSON_MAXSIZE . ' characters long');
		}

		try {
			if ($filesMetadata->getSyncToken() === '') {
				$this->metadataRequestService->store($filesMetadata);
			} else {
				$this->metadataRequestService->updateMetadata($filesMetadata);
			}
		} catch (DBException $e) {
			// most of the logged exception are the result of race condition
			// between 2 simultaneous process trying to create/update metadata
			$this->logger->warning('issue while saveMetadata', ['exception' => $e, 'metadata' => $filesMetadata]);

			return;
		}

		// update indexes
		foreach ($filesMetadata->getIndexes() as $index) {
			try {
				$this->indexRequestService->updateIndex($filesMetadata, $index);
			} catch (DBException $e) {
				$this->logger->warning('issue while updateIndex', ['exception' => $e]);
			}
		}

		// update metadata types list
		$current = $this->getKnownMetadata();
		$current->import($filesMetadata->jsonSerialize(true));
		$this->config->setAppValue('core', self::CONFIG_KEY, json_encode($current));
	}

	/**
	 * @param int $fileId file id
	 *
	 * @inheritDoc
	 * @since 28.0.0
	 */
	public function deleteMetadata(int $fileId): void {
		try {
			$this->metadataRequestService->dropMetadata($fileId);
		} catch (Exception $e) {
			$this->logger->warning('issue while deleteMetadata', ['exception' => $e, 'fileId' => $fileId]);
		}

		try {
			$this->indexRequestService->dropIndex($fileId);
		} catch (Exception $e) {
			$this->logger->warning('issue while deleteMetadata', ['exception' => $e, 'fileId' => $fileId]);
		}
	}

	/**
	 * @param IQueryBuilder $qb
	 * @param string $fileTableAlias alias of the table that contains data about files
	 * @param string $fileIdField alias of the field that contains file ids
	 *
	 * @inheritDoc
	 * @return IMetadataQuery|null
	 * @see IMetadataQuery
	 * @since 28.0.0
	 */
	public function getMetadataQuery(
		IQueryBuilder $qb,
		string $fileTableAlias,
		string $fileIdField
	): ?IMetadataQuery {
		if (!$this->metadataInitiated()) {
			return null;
		}

		return new MetadataQuery($qb, $this->getKnownMetadata(), $fileTableAlias, $fileIdField);
	}

	/**
	 * @inheritDoc
	 * @return IFilesMetadata
	 * @since 28.0.0
	 */
	public function getKnownMetadata(): IFilesMetadata {
		if (null !== $this->all) {
			return $this->all;
		}
		$this->all = new FilesMetadata();

		try {
			$data = json_decode($this->config->getAppValue('core', self::CONFIG_KEY, '[]'), true, 127, JSON_THROW_ON_ERROR);
			$this->all->import($data);
		} catch (JsonException) {
			$this->logger->warning('issue while reading stored list of metadata. Advised to run ./occ files:scan --all --generate-metadata');
		}

		return $this->all;
	}

	/**
	 * @param string $key metadata key
	 * @param string $type metadata type
	 * @param bool $indexed TRUE if metadata can be search
	 * @param int $editPermission remote edit permission via Webdav PROPPATCH
	 *
	 * @inheritDoc
	 * @since 28.0.0
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
	 */
	public function initMetadata(
		string $key,
		string $type,
		bool $indexed = false,
		int $editPermission = IMetadataValueWrapper::EDIT_FORBIDDEN
	): void {
		$current = $this->getKnownMetadata();
		try {
			if ($current->getType($key) === $type
				&& $indexed === $current->isIndex($key)
				&& $editPermission === $current->getEditPermission($key)) {
				return; // if key exists, with same type and indexed, we do nothing.
			}
		} catch (FilesMetadataNotFoundException) {
			// if value does not exist, we keep on the writing of course
		}

		$current->import([$key => ['type' => $type, 'indexed' => $indexed, 'editPermission' => $editPermission]]);
		$this->config->setAppValue('core', self::CONFIG_KEY, json_encode($current));
		$this->all = $current;
	}

	/**
	 * load listeners
	 *
	 * @param IEventDispatcher $eventDispatcher
	 */
	public static function loadListeners(IEventDispatcher $eventDispatcher): void {
		$eventDispatcher->addServiceListener(NodeWrittenEvent::class, MetadataUpdate::class);
		$eventDispatcher->addServiceListener(CacheEntryRemovedEvent::class, MetadataDelete::class);
	}

	/**
	 * Will confirm that tables were created and store an app value to cache the result.
	 * Can be removed in 29 as this is to avoid strange situation when Nextcloud files were
	 * replaced but the upgrade was not triggered yet.
	 *
	 * @return bool
	 */
	private function metadataInitiated(): bool {
		if ($this->config->getAppValue('core', self::MIGRATION_DONE, '0') === '1') {
			return true;
		}

		$dbConnection = \OCP\Server::get(IDBConnection::class);
		if ($dbConnection->tableExists(MetadataRequestService::TABLE_METADATA)) {
			$this->config->setAppValue('core', self::MIGRATION_DONE, '1');

			return true;
		}

		return false;
	}
}
