<?php
/**
 * @copyright Copyright (c) 2021, Louis Chemineau <louis@chmn.me>
 *
 * @author Louis Chemineau <louis@chmn.me>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use OC\ForbiddenException;
use OC\Core\Command\Base;
use OC\Core\Command\InterruptedException;
use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchOrder;
use OC\Files\Search\SearchQuery;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOrder;
use OCP\Files\NotFoundException;
use OCP\Files\IRootFolder;
use OCP\IUserManager;
use OCP\IDBConnection;

class RepairMtime extends Base {
	private IUserManager $userManager;
	private IRootFolder $rootFolder;
	protected IDBConnection $connection;

	protected float $execTime = 0;
	protected int $filesCounter = 0;

	public function __construct(IDBConnection $connection, IUserManager $userManager, IRootFolder $rootFolder) {
		$this->connection = $connection;
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();

		$this
			->setName('files:repair-mtime')
			->setDescription('Repair files\' mtime')
			->addArgument(
				'user_id',
				InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
				'will repair mtime for all files of the given user(s)'
			)
			->addOption(
				'all',
				null,
				InputOption::VALUE_NONE,
				'will repair all files of all known users'
			)
			->addOption(
				'dry-run',
				null,
				InputOption::VALUE_NONE,
				'will list files instead of repairing them'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		if ($input->getOption('all')) {
			$users = $this->userManager->search('');
		} else {
			$users = $input->getArgument('user_id');
		}

		# check quantity of users to be process and show it on the command line
		$users_total = count($users);
		if ($users_total === 0) {
			$output->writeln('<error>Please specify the user id or --all for all users</error>');
			return 1;
		}

		$this->initTools();

		$user_count = 0;
		foreach ($users as $user) {
			if (is_object($user)) {
				$user = $user->getUID();
			}
			++$user_count;
			if ($this->userManager->userExists($user)) {
				$this->repairMtimeForUser(
					$user,
					$input->getOption('dry-run'),
					$output,
				);
			} else {
				$output->writeln("<error>Unknown user $user_count $user</error>");
			}

			try {
				$this->abortIfInterrupted();
			} catch (InterruptedException $e) {
				break;
			}
		}

		$this->presentStats($output, $input->getOption('dry-run'));
		return 0;
	}

	public function repairMtimeForUser(string $userId, bool $dryRun, OutputInterface $output): void {
		$userFolder = $this->rootFolder->getUserFolder($userId);
		$user = $this->userManager->get($userId);

		$offset = 0;

		do {
			$invalidFiles = $userFolder
				->search(
					new SearchQuery(
						new SearchComparison(ISearchComparison::COMPARE_LESS_THAN_EQUAL, 'mtime', 86400),
						0, // 0 = no limits.
						$offset,
						[new SearchOrder(ISearchOrder::DIRECTION_DESCENDING, 'mtime')],
						$user
					)
				);

			$offset += count($invalidFiles);

			$this->connection->beginTransaction();

			foreach ($invalidFiles as $file) {
				$this->filesCounter++;

				try {
					$filePath = $file->getPath();
					$fileId = $file->getId();
					$fileStorage = $file->getStorage();

					// Default new mtime to the current time.
					$mtime = time();

					if ($fileStorage->instanceOfStorage(\OC\Files\ObjectStore\ObjectStoreStorage::class)) {
						// Get LastModified property for S3 as primary storage.
						/** @var \OC\Files\ObjectStore\ObjectStoreStorage $fileStorage */
						$headResult = $fileStorage->getObjectStore()->headObject("urn:oid:$fileId");
						if ($headResult !== false) {
							$date = \DateTime::createFromFormat(\DateTimeInterface::ISO8601, $headResult['LastModified']);
							$mtime = $date->getTimestamp();
						}
					} elseif ($file->getStorage()->instanceOfStorage(\OCA\Files_External\Lib\Storage\AmazonS3::class)) {
						// Get LastModified property for S3 as external storage.
						/** @var \OCA\Files_External\Lib\Storage\AmazonS3 $fileStorage */
						$headResult = $fileStorage->headObject("urn:oid:$fileId");
						if ($headResult !== false) {
							$date = \DateTime::createFromFormat(\DateTimeInterface::ISO8601, $headResult['LastModified']);
							$mtime = $date->getTimestamp();
						}
					}

					$humanMtime = date(DATE_RFC2822, $mtime);
					if ($dryRun) {
						$output->writeln("- Found '$filePath', would set the mtime to $mtime ($humanMtime).", OutputInterface::VERBOSITY_VERBOSE);
					} else {
						$file->touch($mtime);
						$output->writeln("- Fixed $filePath with $mtime ($humanMtime)", OutputInterface::VERBOSITY_VERBOSE);
					}
				} catch (ForbiddenException $e) {
					$output->writeln("<error>Home storage for user $userId not writable</error>");
					$output->writeln('Make sure you\'re running the command only as the user the web server runs as');
				} catch (InterruptedException $e) {
					# exit the function if ctrl-c has been pressed
					$output->writeln('Interrupted by user');
				} catch (NotFoundException $e) {
					$output->writeln('<error>Path not found: ' . $e->getMessage() . '</error>');
				} catch (\Exception $e) {
					$output->writeln('<error>Exception: ' . $e->getMessage() . '</error>');
					$output->writeln('<error>' . $e->getTraceAsString() . '</error>');
				}
			}

			$this->connection->commit();
		} while (count($invalidFiles) > 0);
	}

	/**
	 * Initialises some useful tools for the Command
	 */
	protected function initTools(): void {
		// Start the timer
		$this->execTime = -microtime(true);
		// Convert PHP errors to exceptions
		set_error_handler([$this, 'exceptionErrorHandler'], E_ALL);
	}

	/**
	 * Processes PHP errors as exceptions in order to be able to keep track of problems
	 *
	 * @see https://www.php.net/manual/en/function.set-error-handler.php
	 *
	 * @param int $severity the level of the error raised
	 * @param string $message
	 * @param string $file the filename that the error was raised in
	 * @param int $line the line number the error was raised
	 *
	 * @throws \ErrorException
	 */
	public function exceptionErrorHandler(int $severity, string $message, string $file, int $line): void {
		if (!(error_reporting() & $severity)) {
			// This error code is not included in error_reporting
			return;
		}
		throw new \ErrorException($message, 0, $severity, $file, $line);
	}

	protected function presentStats(OutputInterface $output, bool $dryRun): void {
		// Stop the timer
		$this->execTime += microtime(true);

		$columnName = 'Fixed files';
		if ($dryRun) {
			$columnName = 'Found files';
		}

		$table = new Table($output);
		$table
			->setHeaders([$columnName, 'Elapsed time'])
			->setRows([[$this->filesCounter, $this->formatExecTime()]])
			->render();
	}

	/**
	 * Formats microtime into a human readable format
	 */
	protected function formatExecTime(): string {
		$secs = round($this->execTime);
		# convert seconds into HH:MM:SS form
		return sprintf('%02d:%02d:%02d', ($secs / 3600), ($secs / 60 % 60), $secs % 60);
	}
}
