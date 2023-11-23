<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Blaok <i@blaok.me>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author J0WI <J0WI@users.noreply.github.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Joel S <joel.devbox@protonmail.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author martin.mattel@diemattels.at <martin.mattel@diemattels.at>
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files\Command;

use OC\Core\Command\Base;
use OC\Core\Command\InterruptedException;
use OC\DB\Connection;
use OC\DB\ConnectionAdapter;
use OC\FilesMetadata\FilesMetadataManager;
use OC\ForbiddenException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Events\FileCacheUpdated;
use OCP\Files\Events\NodeAddedToCache;
use OCP\Files\Events\NodeRemovedFromCache;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\NotFoundException;
use OCP\Files\StorageNotAvailableException;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Scan extends Base {
	protected float $execTime = 0;
	protected int $foldersCounter = 0;
	protected int $filesCounter = 0;
	protected int $errorsCounter = 0;
	protected int $newCounter = 0;
	protected int $updatedCounter = 0;
	protected int $removedCounter = 0;

	public function __construct(
		private IUserManager $userManager,
		private IRootFolder $rootFolder,
		private FilesMetadataManager $filesMetadataManager,
		private IEventDispatcher $eventDispatcher,
		private LoggerInterface $logger,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();

		$this
			->setName('files:scan')
			->setDescription('rescan filesystem')
			->addArgument(
				'user_id',
				InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
				'will rescan all files of the given user(s)'
			)
			->addOption(
				'path',
				'p',
				InputArgument::OPTIONAL,
				'limit rescan to this path, eg. --path="/alice/files/Music", the user_id is determined by the path and the user_id parameter and --all are ignored'
			)
			->addOption(
				'generate-metadata',
				null,
				InputOption::VALUE_OPTIONAL,
				'Generate metadata for all scanned files; if specified only generate for named value',
				''
			)
			->addOption(
				'all',
				null,
				InputOption::VALUE_NONE,
				'will rescan all files of all known users'
			)->addOption(
				'unscanned',
				null,
				InputOption::VALUE_NONE,
				'only scan files which are marked as not fully scanned'
			)->addOption(
				'shallow',
				null,
				InputOption::VALUE_NONE,
				'do not scan folders recursively'
			)->addOption(
				'home-only',
				null,
				InputOption::VALUE_NONE,
				'only scan the home storage, ignoring any mounted external storage or share'
			);
	}

	protected function scanFiles(string $user, string $path, ?string $scanMetadata, OutputInterface $output, bool $backgroundScan = false, bool $recursive = true, bool $homeOnly = false): void {
		$connection = $this->reconnectToDatabase($output);
		$scanner = new \OC\Files\Utils\Scanner(
			$user,
			new ConnectionAdapter($connection),
			\OC::$server->get(IEventDispatcher::class),
			\OC::$server->get(LoggerInterface::class)
		);

		# check on each file/folder if there was a user interrupt (ctrl-c) and throw an exception
		$scanner->listen('\OC\Files\Utils\Scanner', 'scanFile', function (string $path) use ($output, $scanMetadata) {
			$output->writeln("\tFile\t<info>$path</info>", OutputInterface::VERBOSITY_VERBOSE);
			++$this->filesCounter;
			$this->abortIfInterrupted();
			if ($scanMetadata !== null) {
				$node = $this->rootFolder->get($path);
				$this->filesMetadataManager->refreshMetadata(
					$node,
					($scanMetadata !== '') ? IFilesMetadataManager::PROCESS_NAMED : IFilesMetadataManager::PROCESS_LIVE | IFilesMetadataManager::PROCESS_BACKGROUND,
					$scanMetadata
				);
			}
		});

		$scanner->listen('\OC\Files\Utils\Scanner', 'scanFolder', function ($path) use ($output) {
			$output->writeln("\tFolder\t<info>$path</info>", OutputInterface::VERBOSITY_VERBOSE);
			++$this->foldersCounter;
			$this->abortIfInterrupted();
		});

		$scanner->listen('\OC\Files\Utils\Scanner', 'StorageNotAvailable', function (StorageNotAvailableException $e) use ($output) {
			$output->writeln('Error while scanning, storage not available (' . $e->getMessage() . ')', OutputInterface::VERBOSITY_VERBOSE);
			++$this->errorsCounter;
		});

		$scanner->listen('\OC\Files\Utils\Scanner', 'normalizedNameMismatch', function ($fullPath) use ($output) {
			$output->writeln("\t<error>Entry \"" . $fullPath . '" will not be accessible due to incompatible encoding</error>');
			++$this->errorsCounter;
		});

		$this->eventDispatcher->addListener(NodeAddedToCache::class, function () {
			++$this->newCounter;
		});
		$this->eventDispatcher->addListener(FileCacheUpdated::class, function () {
			++$this->updatedCounter;
		});
		$this->eventDispatcher->addListener(NodeRemovedFromCache::class, function () {
			++$this->removedCounter;
		});

		try {
			if ($backgroundScan) {
				$scanner->backgroundScan($path);
			} else {
				$scanner->scan($path, $recursive, $homeOnly ? [$this, 'filterHomeMount'] : null);
			}
		} catch (ForbiddenException $e) {
			$output->writeln("<error>Home storage for user $user not writable or 'files' subdirectory missing</error>");
			$output->writeln('  ' . $e->getMessage());
			$output->writeln('Make sure you\'re running the scan command only as the user the web server runs as');
			++$this->errorsCounter;
		} catch (InterruptedException $e) {
			# exit the function if ctrl-c has been pressed
			$output->writeln('Interrupted by user');
		} catch (NotFoundException $e) {
			$output->writeln('<error>Path not found: ' . $e->getMessage() . '</error>');
			++$this->errorsCounter;
		} catch (\Exception $e) {
			$output->writeln('<error>Exception during scan: ' . $e->getMessage() . '</error>');
			$output->writeln('<error>' . $e->getTraceAsString() . '</error>');
			++$this->errorsCounter;
		}
	}

	public function filterHomeMount(IMountPoint $mountPoint): bool {
		// any mountpoint inside '/$user/files/'
		return substr_count($mountPoint->getMountPoint(), '/') <= 3;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$inputPath = $input->getOption('path');
		if ($inputPath) {
			$inputPath = '/' . trim($inputPath, '/');
			[, $user,] = explode('/', $inputPath, 3);
			$users = [$user];
		} elseif ($input->getOption('all')) {
			$users = $this->userManager->search('');
		} else {
			$users = $input->getArgument('user_id');
		}

		# check quantity of users to be process and show it on the command line
		$users_total = count($users);
		if ($users_total === 0) {
			$output->writeln('<error>Please specify the user id to scan, --all to scan for all users or --path=...</error>');
			return self::FAILURE;
		}

		$this->initTools($output);

		// getOption() logic on VALUE_OPTIONAL
		$metadata = null; // null if --generate-metadata is not set, empty if option have no value, value if set
		if ($input->getOption('generate-metadata') !== '') {
			$metadata = $input->getOption('generate-metadata') ?? '';
		}

		$user_count = 0;
		foreach ($users as $user) {
			if (is_object($user)) {
				$user = $user->getUID();
			}
			$path = $inputPath ?: '/' . $user;
			++$user_count;
			if ($this->userManager->userExists($user)) {
				$output->writeln("Starting scan for user $user_count out of $users_total ($user)");
				$this->scanFiles($user, $path, $metadata, $output, $input->getOption('unscanned'), !$input->getOption('shallow'), $input->getOption('home-only'));
				$output->writeln('', OutputInterface::VERBOSITY_VERBOSE);
			} else {
				$output->writeln("<error>Unknown user $user_count $user</error>");
				$output->writeln('', OutputInterface::VERBOSITY_VERBOSE);
			}

			try {
				$this->abortIfInterrupted();
			} catch (InterruptedException $e) {
				break;
			}
		}

		$this->presentStats($output);
		return self::SUCCESS;
	}

	/**
	 * Initialises some useful tools for the Command
	 */
	protected function initTools(OutputInterface $output): void {
		// Start the timer
		$this->execTime = -microtime(true);
		// Convert PHP errors to exceptions
		set_error_handler(
			fn (int $severity, string $message, string $file, int $line): bool =>
				$this->exceptionErrorHandler($output, $severity, $message, $file, $line),
			E_ALL
		);
	}

	/**
	 * Processes PHP errors in order to be able to show them in the output
	 *
	 * @see https://www.php.net/manual/en/function.set-error-handler.php
	 *
	 * @param int $severity the level of the error raised
	 * @param string $message
	 * @param string $file the filename that the error was raised in
	 * @param int $line the line number the error was raised
	 */
	public function exceptionErrorHandler(OutputInterface $output, int $severity, string $message, string $file, int $line): bool {
		if (($severity === E_DEPRECATED) || ($severity === E_USER_DEPRECATED)) {
			// Do not show deprecation warnings
			return false;
		}
		$e = new \ErrorException($message, 0, $severity, $file, $line);
		$output->writeln('<error>Error during scan: ' . $e->getMessage() . '</error>');
		$output->writeln('<error>' . $e->getTraceAsString() . '</error>', OutputInterface::VERBOSITY_VERY_VERBOSE);
		++$this->errorsCounter;
		return true;
	}

	protected function presentStats(OutputInterface $output): void {
		// Stop the timer
		$this->execTime += microtime(true);

		$this->logger->info("Completed scan of {$this->filesCounter} files in {$this->foldersCounter} folder. Found {$this->newCounter} new, {$this->updatedCounter} updated and {$this->removedCounter} removed items");

		$headers = [
			'Folders',
			'Files',
			'New',
			'Updated',
			'Removed',
			'Errors',
			'Elapsed time',
		];
		$niceDate = $this->formatExecTime();
		$rows = [
			$this->foldersCounter,
			$this->filesCounter,
			$this->newCounter,
			$this->updatedCounter,
			$this->removedCounter,
			$this->errorsCounter,
			$niceDate,
		];
		$table = new Table($output);
		$table
			->setHeaders($headers)
			->setRows([$rows]);
		$table->render();
	}


	/**
	 * Formats microtime into a human-readable format
	 */
	protected function formatExecTime(): string {
		$secs = (int)round($this->execTime);
		# convert seconds into HH:MM:SS form
		return sprintf('%02d:%02d:%02d', (int)($secs / 3600), ((int)($secs / 60) % 60), $secs % 60);
	}

	protected function reconnectToDatabase(OutputInterface $output): Connection {
		/** @var Connection $connection */
		$connection = \OC::$server->get(Connection::class);
		try {
			$connection->close();
		} catch (\Exception $ex) {
			$output->writeln("<info>Error while disconnecting from database: {$ex->getMessage()}</info>");
		}
		while (!$connection->isConnected()) {
			try {
				$connection->connect();
			} catch (\Exception $ex) {
				$output->writeln("<info>Error while re-connecting to database: {$ex->getMessage()}</info>");
				sleep(60);
			}
		}
		return $connection;
	}
}
