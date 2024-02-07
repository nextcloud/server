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
use OC\Files\Node\Node;
use OC\DB\ConnectionAdapter;
use OC\FilesMetadata\FilesMetadataManager;
use OC\ForbiddenException;
use OCP\EventDispatcher\IEventDispatcher;
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

class ListFiles extends Base {

	protected array $fileInfo = [];
	protected array $dirInfo = [];
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
			->setName('files:list')
			->setDescription('rescan filesystem')
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
			)->addOption(
				'type',
				'',
				InputArgument::OPTIONAL,
				'filter by type'
			)->addOption(
				'minSize',
				0,
				InputArgument::OPTIONAL,
				'filter by min size'
			)->addOption(
				'maxSize',
				0,
				InputArgument::OPTIONAL,
				'filter by max size'
			)->addOption(
				'sort',
				'name',
				InputArgument::OPTIONAL,
				'name, path, size, owner, type'
			)->addOption(
				'order',
				'ASC',
				InputArgument::OPTIONAL,
				'ASC, DESC'
			);
	}

	private function getNodeInfo(Node $node, string $path): array {
		return [
			"name" => $node->getName(),
			'path' => $path,
			"size" => $node->getSize() . " bytes",
			"perm" => $node->getPermissions(),
			"owner" => $node->getOwner()->getDisplayName(),
			"created-at" => $node->getCreationTime(),
			"type" => $node->getMimePart()
		];
	}

	protected function scanFiles(
		string $user,
		string $path,
		?string $scanMetadata,
		OutputInterface $output,
		bool $backgroundScan = false,
		bool $recursive = true,
		bool $homeOnly = false,
		?string $type = '',
		?int $minSize = 0,
		?int $maxSize = 0
	): void {
		$connection = $this->reconnectToDatabase($output);
		$scanner = new \OC\Files\Utils\Scanner(
			$user,
			new ConnectionAdapter($connection),
			\OC::$server->get(IEventDispatcher::class),
			\OC::$server->get(LoggerInterface::class)
		);

		# check on each file/folder if there was a user interrupt (ctrl-c) and throw an exception
		$scanner->listen('\OC\Files\Utils\Scanner', 'scanFile', function (string $path) use ($output, $scanMetadata, $type, $minSize, $maxSize) {
			$node = $this->rootFolder->get($path);

			$includeType = $includeMin = $includeMax = true;
			if($type != '' && $type != $node->getMimePart()) {
				$includeType = false;
			}
			if($minSize > 0) {
				$includeMin = $node->getSize() >= $minSize;
			}
			if($maxSize > 0) {
				$includeMax = $node->getSize() <= $maxSize;
			}

			if($includeType && $includeMin && $includeMax) {
				$this->fileInfo[] = $this->getNodeInfo($node, $path);
			}
			$this->abortIfInterrupted();
			if ($scanMetadata !== null) {
				$this->filesMetadataManager->refreshMetadata(
					$node,
					($scanMetadata !== '') ? IFilesMetadataManager::PROCESS_NAMED : IFilesMetadataManager::PROCESS_LIVE | IFilesMetadataManager::PROCESS_BACKGROUND,
					$scanMetadata
				);
			}
		});

		$scanner->listen('\OC\Files\Utils\Scanner', 'scanFolder', function ($path) use ($output) {
			$node = $this->rootFolder->get($path);

			$this->dirInfo[] = $this->getNodeInfo($node, $path);

			$this->abortIfInterrupted();
		});

		$scanner->listen('\OC\Files\Utils\Scanner', 'StorageNotAvailable', function (StorageNotAvailableException $e) use ($output) {
			$output->writeln('Error while scanning, storage not available (' . $e->getMessage() . ')', OutputInterface::VERBOSITY_VERBOSE);
		});

		$scanner->listen('\OC\Files\Utils\Scanner', 'normalizedNameMismatch', function ($fullPath) use ($output) {
			$output->writeln("\t<error>Entry \"" . $fullPath . '" will not be accessible due to incompatible encoding</error>');
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
		} catch (InterruptedException $e) {
			# exit the function if ctrl-c has been pressed
			$output->writeln('Interrupted by user');
		} catch (NotFoundException $e) {
			$output->writeln('<error>Path not found: ' . $e->getMessage() . '</error>');
		} catch (\Exception $e) {
			$output->writeln('<error>Exception during scan: ' . $e->getMessage() . '</error>');
			$output->writeln('<error>' . $e->getTraceAsString() . '</error>');
		}
	}

	public function filterHomeMount(IMountPoint $mountPoint): bool {
		// any mountpoint inside '/$user/files/'
		return substr_count($mountPoint->getMountPoint(), '/') <= 3;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$inputPath = $input->getOption('path');
		$users = [];
		if ($inputPath) {
			$inputPath = '/' . trim($inputPath, '/');
			[, $user,] = explode('/', $inputPath, 3);
			$users = [$user];
		} elseif ($input->getOption('all')) {
			$users = $this->userManager->search('');
		}

		# check quantity of users to be process and show it on the command line
		$users_total = count($users);
		if ($users_total === 0) {
			$output->writeln('<error>Please specify the path to scan, --all to scan for all users or --path=...</error>');
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
				$this->scanFiles(
					$user,
					$path,
					$metadata,
					$output,
					$input->getOption('unscanned'),
					!$input->getOption('shallow'),
					$input->getOption('home-only'),
					$input->getOption('type'),
					$input->getOption('minSize'),
					$input->getOption('maxSize'),

				);
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

		$this->presentStats($input, $output);
		return self::SUCCESS;
	}

	/**
	 * Initialises some useful tools for the Command
	 */
	protected function initTools(OutputInterface $output): void {
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
		return true;
	}

	protected function presentStats(InputInterface $input, OutputInterface $output): void {
		$headers = [
			'Permission',
			'Size',
			'Owner',
			'Created at',
			'Filename',
			'Path',
			'Type'
		];
		$rows = [];
		$fileInfo = $this->fileInfo[0] ?? [];
		$sortKey = array_key_exists($input->getOption('sort'), $fileInfo) ? $input->getOption('sort') : 'name';
		$order = ($input->getOption('order') == 'ASC') ? SORT_ASC : SORT_DESC;
		array_multisort(array_column($this->fileInfo, $sortKey), $order, $this->fileInfo);
		array_multisort(array_column($this->dirInfo, $sortKey), $order, $this->dirInfo);

		foreach ($this->fileInfo as $k => $item) {
			$rows[$k] = [
				$item['perm'],
				$item['size'],
				$item['owner'],
				$item['created-at'],
				$item['name'],
				$item['path'],
				$item['type']
			];
		}
		foreach ($this->dirInfo as $k => $item) {
			$rows[] = [
				$item['perm'],
				$item['size'],
				$item['owner'],
				$item['created-at'],
				$item['name'],
				$item['path'],
				$item['type']
			];
		}

		$table = new Table($output);
		$table
			->setHeaders($headers)
			->setRows($rows);
		$table->render();

		//		$this->writeArrayInOutputFormat($input, $output, $this->fileInfo);
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
