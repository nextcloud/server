<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
use OCP\Files\Node;
use OCP\FilesMetadata\Event\MetadataBackgroundEvent;
use OCP\FilesMetadata\Event\MetadataLiveEvent;
use OCP\FilesMetadata\Event\MetadataNamedEvent;
use OCP\FilesMetadata\Exceptions\FilesMetadataException;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\FilesMetadata\IMetadataQuery;
use OCP\FilesMetadata\Model\IFilesMetadata;
use OCP\FilesMetadata\Model\IMetadataValueWrapper;
use OCP\IAppConfig;
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
		private IAppConfig $appConfig,
		private LoggerInterface $logger,
		private MetadataRequestService $metadataRequestService,
		private IndexRequestService $indexRequestService,
	) {
	}

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

			$this->jobList->add(UpdateSingleMetadata::class, [$node->getOwner()->getUID(), $node->getId()]);
		}

		return $metadata;
	}

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

	public function getMetadataForFiles(array $fileIds): array {
		return $this->metadataRequestService->getMetadataFromFileIds($fileIds);
	}

	public function saveMetadata(IFilesMetadata $filesMetadata): void {
		if ($filesMetadata->getFileId() === 0 || !$filesMetadata->updated()) {
			return;
		}

		$json = json_encode($filesMetadata->jsonSerialize());
		if (strlen($json) > self::JSON_MAXSIZE) {
			$this->logger->debug('huge metadata content detected: ' . $json);
			throw new FilesMetadataException('json cannot exceed ' . self::JSON_MAXSIZE . ' characters long; fileId: ' . $filesMetadata->getFileId() . '; size: ' . strlen($json));
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
		$this->appConfig->setValueArray('core', self::CONFIG_KEY, $current->jsonSerialize(), lazy: true);
	}

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

	public function getMetadataQuery(
		IQueryBuilder $qb,
		string $fileTableAlias,
		string $fileIdField
	): IMetadataQuery {
		return new MetadataQuery($qb, $this, $fileTableAlias, $fileIdField);
	}

	public function getKnownMetadata(): IFilesMetadata {
		if ($this->all !== null) {
			return $this->all;
		}
		$this->all = new FilesMetadata();

		try {
			$this->all->import($this->appConfig->getValueArray('core', self::CONFIG_KEY, lazy: true));
		} catch (JsonException) {
			$this->logger->warning('issue while reading stored list of metadata. Advised to run ./occ files:scan --all --generate-metadata');
		}

		return $this->all;
	}

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
		$this->appConfig->setValueArray('core', self::CONFIG_KEY, $current->jsonSerialize(), lazy: true);
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
}
