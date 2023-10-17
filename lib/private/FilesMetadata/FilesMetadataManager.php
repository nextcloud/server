<?php

declare(strict_types=1);

namespace OC\FilesMetadata;

use OC\FilesMetadata\Job\UpdateSingleMetadata;
use OC\FilesMetadata\Listener\MetadataDelete;
use OC\FilesMetadata\Listener\MetadataUpdate;
use OC\FilesMetadata\Model\FilesMetadata;
use OC\FilesMetadata\Model\MetadataQuery;
use OC\FilesMetadata\Service\IndexRequestService;
use OC\FilesMetadata\Service\MetadataRequestService;
use OCP\BackgroundJob\IJobList;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\Node;
use OCP\FilesMetadata\Event\MetadataBackgroundEvent;
use OCP\FilesMetadata\Event\MetadataLiveEvent;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\FilesMetadata\Model\IFilesMetadata;
use OCP\FilesMetadata\Model\IMetadataQuery;
use Psr\Log\LoggerInterface;

class FilesMetadataManager implements IFilesMetadataManager {
	public function __construct(
		private IEventDispatcher $eventDispatcher,
		private IJobList $jobList,
		private LoggerInterface $logger,
		private MetadataRequestService $metadataRequestService,
		private IndexRequestService $indexRequestService,
	) {
	}

	/**
	 * @param int $fileId
	 *
	 * @return IFilesMetadata
	 * @throws FilesMetadataNotFoundException
	 */
	public function getMetadata(int $fileId): IFilesMetadata {
		return $this->metadataRequestService->getMetadataFromFileId($fileId);
	}


	public function refreshMetadata(
		Node $node,
		bool $asBackgroundJob = false,
		bool $fromScratch = false
	): IFilesMetadata {
		$metadata = null;
		if (!$fromScratch) {
			try {
				$metadata = $this->metadataRequestService->getMetadataFromFileId($node->getId());
			} catch (FilesMetadataNotFoundException $e) {
			}
		}

		if (null === $metadata) {
			$metadata = new FilesMetadata($node->getId(), true);
		}

		if ($asBackgroundJob) {
			$event = new MetadataBackgroundEvent($node, $metadata);
		} else {
			$event = new MetadataLiveEvent($node, $metadata);
		}

		$this->eventDispatcher->dispatchTyped($event);
		$this->saveMetadata($event->getMetadata());

		// if requested, we add a new job for next cron to refresh metadata out of main thread
		if ($event instanceof MetadataLiveEvent && $event->isRunAsBackgroundJobRequested()) {
			$this->jobList->add(UpdateSingleMetadata::class, [$node->getOwner()->getUID(), $node->getId()]);
		}

		return $metadata;
	}

	/**
	 * @param IFilesMetadata $filesMetadata
	 *
	 * @return void
	 */
	public function saveMetadata(IFilesMetadata $filesMetadata): void {
		if ($filesMetadata->getFileId() === 0 || !$filesMetadata->updated()) {
			return;
		}

		try {
			// if update request changed no rows, means that new entry is needed, or sync_token not valid anymore
			$updated = $this->metadataRequestService->updateMetadata($filesMetadata);
			if ($updated === 0) {
				$this->metadataRequestService->store($filesMetadata);
			}
		} catch (\OCP\DB\Exception $e) {
			// if duplicate, only means a desync during update. cancel update process.
			if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				$this->logger->warning(
					'issue while saveMetadata', ['exception' => $e, 'metadata' => $filesMetadata]
				);
			}

			return;
		}

//		$this->removeDeprecatedMetadata($filesMetadata);
		foreach ($filesMetadata->getIndexes() as $index) {
			try {
				$this->indexRequestService->updateIndex($filesMetadata, $index);
			} catch (Exception $e) {
				$this->logger->warning('...');
			}
		}
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
		return new MetadataQuery($qb, $fileTableAlias, $fileIdField);
	}

	public static function loadListeners(IEventDispatcher $eventDispatcher): void {
		$eventDispatcher->addServiceListener(NodeCreatedEvent::class, MetadataUpdate::class);
		$eventDispatcher->addServiceListener(NodeWrittenEvent::class, MetadataUpdate::class);
		$eventDispatcher->addServiceListener(NodeDeletedEvent::class, MetadataDelete::class);
	}
}
