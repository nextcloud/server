<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command\Object\Multi;

use OC\Core\Command\Base;
use OC\Files\Mount\ObjectHomeMountProvider;
use OC\Files\ObjectStore\PrimaryObjectStoreConfig;
use OC\Files\ObjectStore\S3;
use OC\Files\Storage\StorageFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\FileInfo;
use OCP\Files\IMimeTypeLoader;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Move extends Base {
	public function __construct(
		private PrimaryObjectStoreConfig $objectStoreConfig,
		private IUserManager $userManager,
		private IConfig $config,
		private ObjectHomeMountProvider $mountProvider,
		private IMimeTypeLoader $mimeTypeLoader,
		private IDBConnection $connection,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this
			->setName('files:object:multi:move')
			->setDescription('Migrate user to the specified object store and bucket. The bucket must be created and known beforehand containing the same objects in the user\'s current bucket.')
			->addOption('object-store', 'o', InputOption::VALUE_REQUIRED, 'The name of the object store')
			->addOption('bucket', 'b', InputOption::VALUE_REQUIRED, 'The name of the bucket')
			->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'The user to migrate')
			->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run command without commiting any changes');
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$objectStore = $input->getOption('object-store');
		if (!$objectStore) {
			$output->writeln('Please specify the object store');
		}
		$bucket = $input->getOption('bucket');
		if (!$bucket) {
			$output->writeln('Please specify the bucket');
		}

		$configs = $this->objectStoreConfig->getObjectStoreConfigs();
		if (!isset($configs[$objectStore])) {
			$output->writeln('<error>Unknown object store configuration: ' . $objectStore . '</error>');
			return 1;
		}

		if ($userId = $input->getOption('user')) {
			$user = $this->userManager->get($userId);
			if (!$user) {
				$output->writeln('<error>User ' . $userId . ' not found</error>');
				return 1;
			}
		} else {
			$output->writeln('<comment>Please specify a user id with --user</comment>');
			return 1;
		}

		try {
			$targetValid = $this->validateForUser($user, $objectStore, $bucket);
		} catch (\Exception $e) {
			$output->writeln('Object store <info>' . $objectStore . '</info> and bucket <info>' . $bucket . '</info> invalid for <info>' . $userId . '</info>: ' . $e->getMessage());

			return 1;
		}

		if ($targetValid) {
			if (!$input->getOption('dry-run')) {
				$this->config->setUserValue($user->getUID(), 'homeobjectstore', 'objectstore', $objectStore);
				$this->config->setUserValue($user->getUID(), 'homeobjectstore', 'bucket', $bucket);
			}
			$output->writeln('Moved <info>' . $user->getUID() . '</info> to object store <info>' . $objectStore . '</info> and bucket <info>' . $bucket . '</info>.');
		} else {
			$output->writeln('Object store <info>' . $objectStore . '</info> and bucket <info>' . $bucket . '</info> invalid for <info>' . $userId . '</info>. Bucket doesn\'t exist or contain expected user objects.');
			return 1;
		}

		return 0;
	}

	private function validateForUser(IUser $user, string $targetObjectStore, string $targetBucket): bool {
		$currentObjectStore = $this->config->getUserValue($user->getUID(), 'homeobjectstore', 'objectstore');
		if ($currentObjectStore === '') {
			throw new \Exception('No object store set for ' . $user->getUID() . '. Please set an object store and bucket before proceeding.');
		}

		$currentBucket = $this->objectStoreConfig->getSetBucketForUser($user);
		if ($currentBucket === null || $currentBucket === '') {
			throw new \Exception('No bucket set for ' . $user->getUID() . '. Please set a bucket before proceeding.');
		}
		if ($currentBucket === $targetBucket) {
			if ($currentObjectStore !== $targetObjectStore) {
				throw new \Exception('Bucket names must be unique');
			}

			return true;
		}

		$storageFactory = new StorageFactory();
		$homeMount = $this->mountProvider->getHomeMountForUser($user, $storageFactory);
		if ($homeMount === null) {
			throw new \Exception('Failed to get home mount for ' . $user->getUID());
		}

		$homeStorage = $homeMount->getStorage();
		$storageId = $homeStorage?->getCache()->getNumericStorageId();
		if ($storageId === null) {
			throw new \Exception('Failed to get the user\'s home storage.');
		}
		$folderMimetype = $this->mimeTypeLoader->getId(FileInfo::MIMETYPE_FOLDER);

		$query = $this->connection->getQueryBuilder();
		$query->select('fileid')
			->from('filecache')
			->where($query->expr()->eq('storage', $query->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)))
			->andWhere($query->expr()->neq('mimetype', $query->createNamedParameter($folderMimetype, IQueryBuilder::PARAM_INT)))
			->setMaxResults(10);
		$result = $query->execute();
		$fileIds = $result->fetchAll(\PDO::FETCH_COLUMN);

		// Use a new S3 client to 'peek' into the target bucket since it's not yet mounted
		$targetConfig = $this->objectStoreConfig->getObjectStoreConfiguration($targetObjectStore);
		$targetConfig['arguments']['bucket'] = $targetBucket;
		$s3 = new S3($targetConfig['arguments']);

		foreach ($fileIds as $fileId) {
			if (!$s3->objectExists('urn:oid:' . $fileId)) {
				return false;
			}
		}

		return true;
	}
}
