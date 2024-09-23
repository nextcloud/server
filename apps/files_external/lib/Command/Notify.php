<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Command;

use Doctrine\DBAL\Exception\DriverException;
use OCA\Files_External\Service\GlobalStoragesService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Notify\IChange;
use OCP\Files\Notify\INotifyHandler;
use OCP\Files\Notify\IRenameChange;
use OCP\Files\Storage\INotifyStorage;
use OCP\Files\Storage\IStorage;
use OCP\IDBConnection;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Notify extends StorageAuthBase {
	public function __construct(
		private IDBConnection $connection,
		private LoggerInterface $logger,
		GlobalStoragesService $globalService,
		IUserManager $userManager,
	) {
		parent::__construct($globalService, $userManager);
	}

	protected function configure(): void {
		$this
			->setName('files_external:notify')
			->setDescription('Listen for active update notifications for a configured external mount')
			->addArgument(
				'mount_id',
				InputArgument::REQUIRED,
				'the mount id of the mount to listen to'
			)->addOption(
				'user',
				'u',
				InputOption::VALUE_REQUIRED,
				'The username for the remote mount (required only for some mount configuration that don\'t store credentials)'
			)->addOption(
				'password',
				'p',
				InputOption::VALUE_REQUIRED,
				'The password for the remote mount (required only for some mount configuration that don\'t store credentials)'
			)->addOption(
				'path',
				'',
				InputOption::VALUE_REQUIRED,
				'The directory in the storage to listen for updates in',
				'/'
			)->addOption(
				'no-self-check',
				'',
				InputOption::VALUE_NONE,
				'Disable self check on startup'
			)->addOption(
				'dry-run',
				'',
				InputOption::VALUE_NONE,
				'Don\'t make any changes, only log detected changes'
			);
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		[$mount, $storage] = $this->createStorage($input, $output);
		if ($storage === null) {
			return self::FAILURE;
		}

		if (!$storage instanceof INotifyStorage) {
			$output->writeln('<error>Mount of type "' . $mount->getBackend()->getText() . '" does not support active update notifications</error>');
			return self::FAILURE;
		}

		$dryRun = $input->getOption('dry-run');
		if ($dryRun && $output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
			$output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
		}

		$path = trim($input->getOption('path'), '/');
		$notifyHandler = $storage->notify($path);

		if (!$input->getOption('no-self-check')) {
			$this->selfTest($storage, $notifyHandler, $output);
		}

		$notifyHandler->listen(function (IChange $change) use ($mount, $output, $dryRun): void {
			$this->logUpdate($change, $output);
			if ($change instanceof IRenameChange) {
				$this->markParentAsOutdated($mount->getId(), $change->getTargetPath(), $output, $dryRun);
			}
			$this->markParentAsOutdated($mount->getId(), $change->getPath(), $output, $dryRun);
		});
		return self::SUCCESS;
	}

	private function markParentAsOutdated($mountId, $path, OutputInterface $output, bool $dryRun): void {
		$parent = ltrim(dirname($path), '/');
		if ($parent === '.') {
			$parent = '';
		}

		try {
			$storages = $this->getStorageIds($mountId, $parent);
		} catch (DriverException $ex) {
			$this->logger->warning('Error while trying to find correct storage ids.', ['exception' => $ex]);
			$this->connection = $this->reconnectToDatabase($this->connection, $output);
			$output->writeln('<info>Needed to reconnect to the database</info>');
			$storages = $this->getStorageIds($mountId, $path);
		}
		if (count($storages) === 0) {
			$output->writeln("  no users found with access to '$parent', skipping", OutputInterface::VERBOSITY_VERBOSE);
			return;
		}

		$users = array_map(function (array $storage) {
			return $storage['user_id'];
		}, $storages);

		$output->writeln("  marking '$parent' as outdated for " . implode(', ', $users), OutputInterface::VERBOSITY_VERBOSE);

		$storageIds = array_map(function (array $storage) {
			return intval($storage['storage_id']);
		}, $storages);
		$storageIds = array_values(array_unique($storageIds));

		if ($dryRun) {
			$output->writeln('  dry-run: skipping database write');
		} else {
			$result = $this->updateParent($storageIds, $parent);
			if ($result === 0) {
				//TODO: Find existing parent further up the tree in the database and register that folder instead.
				$this->logger->info('Failed updating parent for "' . $path . '" while trying to register change. It may not exist in the filecache.');
			}
		}
	}

	private function logUpdate(IChange $change, OutputInterface $output): void {
		$text = match ($change->getType()) {
			INotifyStorage::NOTIFY_ADDED => 'added',
			INotifyStorage::NOTIFY_MODIFIED => 'modified',
			INotifyStorage::NOTIFY_REMOVED => 'removed',
			INotifyStorage::NOTIFY_RENAMED => 'renamed',
			default => '',
		};

		if ($text === '') {
			return;
		}

		$text .= ' ' . $change->getPath();
		if ($change instanceof IRenameChange) {
			$text .= ' to ' . $change->getTargetPath();
		}

		$output->writeln($text, OutputInterface::VERBOSITY_VERBOSE);
	}

	private function getStorageIds(int $mountId, string $path): array {
		$pathHash = md5(trim((string)\OC_Util::normalizeUnicode($path), '/'));
		$qb = $this->connection->getQueryBuilder();
		return $qb
			->select('storage_id', 'user_id')
			->from('mounts', 'm')
			->innerJoin('m', 'filecache', 'f', $qb->expr()->eq('m.storage_id', 'f.storage'))
			->where($qb->expr()->eq('mount_id', $qb->createNamedParameter($mountId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('path_hash', $qb->createNamedParameter($pathHash, IQueryBuilder::PARAM_STR)))
			->execute()
			->fetchAll();
	}

	private function updateParent(array $storageIds, string $parent): int {
		$pathHash = md5(trim((string)\OC_Util::normalizeUnicode($parent), '/'));
		$qb = $this->connection->getQueryBuilder();
		return $qb
			->update('filecache')
			->set('size', $qb->createNamedParameter(-1, IQueryBuilder::PARAM_INT))
			->where($qb->expr()->in('storage', $qb->createNamedParameter($storageIds, IQueryBuilder::PARAM_INT_ARRAY, ':storage_ids')))
			->andWhere($qb->expr()->eq('path_hash', $qb->createNamedParameter($pathHash, IQueryBuilder::PARAM_STR)))
			->executeStatement();
	}

	private function reconnectToDatabase(IDBConnection $connection, OutputInterface $output): IDBConnection {
		try {
			$connection->close();
		} catch (\Exception $ex) {
			$this->logger->warning('Error while disconnecting from DB', ['exception' => $ex]);
			$output->writeln("<info>Error while disconnecting from database: {$ex->getMessage()}</info>");
		}
		$connected = false;
		while (!$connected) {
			try {
				$connected = $connection->connect();
			} catch (\Exception $ex) {
				$this->logger->warning('Error while re-connecting to database', ['exception' => $ex]);
				$output->writeln("<info>Error while re-connecting to database: {$ex->getMessage()}</info>");
				sleep(60);
			}
		}
		return $connection;
	}


	private function selfTest(IStorage $storage, INotifyHandler $notifyHandler, OutputInterface $output): void {
		usleep(100 * 1000); //give time for the notify to start
		if (!$storage->file_put_contents('/.nc_test_file.txt', 'test content')) {
			$output->writeln('Failed to create test file for self-test');
			return;
		}
		$storage->mkdir('/.nc_test_folder');
		$storage->file_put_contents('/.nc_test_folder/subfile.txt', 'test content');

		usleep(100 * 1000); //time for all changes to be processed
		$changes = $notifyHandler->getChanges();

		$storage->unlink('/.nc_test_file.txt');
		$storage->unlink('/.nc_test_folder/subfile.txt');
		$storage->rmdir('/.nc_test_folder');

		usleep(100 * 1000); //time for all changes to be processed
		$notifyHandler->getChanges(); // flush

		$foundRootChange = false;
		$foundSubfolderChange = false;

		foreach ($changes as $change) {
			if ($change->getPath() === '/.nc_test_file.txt' || $change->getPath() === '.nc_test_file.txt') {
				$foundRootChange = true;
			} elseif ($change->getPath() === '/.nc_test_folder/subfile.txt' || $change->getPath() === '.nc_test_folder/subfile.txt') {
				$foundSubfolderChange = true;
			}
		}

		if ($foundRootChange && $foundSubfolderChange) {
			$output->writeln('<info>Self-test successful</info>', OutputInterface::VERBOSITY_VERBOSE);
		} elseif ($foundRootChange) {
			$output->writeln('<error>Error while running self-test, change is subfolder not detected</error>');
		} else {
			$output->writeln('<error>Error while running self-test, no changes detected</error>');
		}
	}
}
