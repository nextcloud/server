<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\FilesMetadata;

use OC\BackgroundJob\JobList;
use OC\Files\Storage\Temporary;
use OC\FilesMetadata\FilesMetadataManager;
use OC\FilesMetadata\Service\IndexRequestService;
use OC\FilesMetadata\Service\MetadataRequestService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\FilesMetadata\AMetadataEvent;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Test\TestCase;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;

#[\PHPUnit\Framework\Attributes\Group('DB')]
class FilesMetadataManagerTest extends TestCase {
	use UserTrait;
	use MountProviderTrait;

	private IEventDispatcher $eventDispatcher;
	private JobList $jobList;
	private IAppConfig $appConfig;
	private LoggerInterface $logger;
	private MetadataRequestService $metadataRequestService;
	private IndexRequestService $indexRequestService;
	private FilesMetadataManager $manager;
	private IDBConnection $connection;
	private Folder $userFolder;
	private array $metadata = [];

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->jobList = $this->createMock(JobList::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->eventDispatcher->method('dispatchTyped')->willReturnCallback(function (Event $event): void {
			if ($event instanceof AMetadataEvent) {
				$name = $event->getNode()->getName();
				if (isset($this->metadata[$name])) {
					$meta = $event->getMetadata();
					foreach ($this->metadata[$name] as $key => $value) {
						$meta->setString($key, $value);
					}
				}
			}
		});
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->connection = Server::get(IDBConnection::class);
		$this->metadataRequestService = new MetadataRequestService($this->connection, $this->logger);
		$this->indexRequestService = new IndexRequestService($this->connection, $this->logger);
		$this->manager = new FilesMetadataManager(
			$this->eventDispatcher,
			$this->jobList,
			$this->appConfig,
			$this->logger,
			$this->metadataRequestService,
			$this->indexRequestService,
		);

		$this->createUser('metatest', '');
		$this->registerMount('metatest', new Temporary([]), '/metatest');

		$rootFolder = Server::get(IRootFolder::class);
		$this->userFolder = $rootFolder->getUserFolder('metatest');
	}

	public function testRefreshMetadata(): void {
		$this->metadata['test.txt'] = [
			'istest' => 'yes'
		];
		$file = $this->userFolder->newFile('test.txt', 'test');
		$stored = $this->manager->refreshMetadata($file);
		$this->assertEquals($file->getId(), $stored->getFileId());
		$this->assertEquals('yes', $stored->getString('istest'));

		$retrieved = $this->manager->getMetadata($file->getId());
		$this->assertEquals($file->getId(), $retrieved->getFileId());
		$this->assertEquals('yes', $retrieved->getString('istest'));
	}

	public function testDropMetadataForFilesChunking(): void {
		$connection = $this->createMock(IDBConnection::class);
		$qb = $this->createMock(\OCP\DB\QueryBuilder\IQueryBuilder::class);
		$expr = $this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class);

		$connection->method('getQueryBuilder')->willReturn($qb);
		$qb->method('expr')->willReturn($expr);
		$qb->method('delete')->willReturnSelf();
		$qb->method('where')->willReturnSelf();
		$qb->method('hintShardKey')->willReturnSelf();

		$fileIds = range(1, \OCP\DB\QueryBuilder\IQueryBuilder::MAX_IN_PARAMETERS * 2 + 1);
		$expectedChunks = array_chunk($fileIds, \OCP\DB\QueryBuilder\IQueryBuilder::MAX_IN_PARAMETERS);
		$boundChunks = [];

		$qb->expects($this->exactly(count($expectedChunks)))
			->method('createNamedParameter')
			->willReturnCallback(function (array $chunk, $type) use (&$boundChunks): string {
				$this->assertSame(\OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY, $type);
				$boundChunks[] = $chunk;
				return ':param';
			});

		$qb->expects($this->exactly(count($expectedChunks)))
			->method('executeStatement')
			->willReturn(1);

		$service = new MetadataRequestService($connection, $this->logger);
		$service->dropMetadataForFiles(123, $fileIds);

		$this->assertSame($expectedChunks, $boundChunks);
	}
}
