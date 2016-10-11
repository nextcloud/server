<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author martin.mattel@diemattels.at <martin.mattel@diemattels.at>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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

use Doctrine\DBAL\Connection;
use OC\Core\Command\Base;
use OC\ForbiddenException;
use OCP\Files\StorageNotAvailableException;
use OCP\IDBConnection;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class Scan extends Base {

	/** @var IUserManager $userManager */
	private $userManager;
	/** @var float */
	protected $execTime = 0;
	/** @var int */
	protected $foldersCounter = 0;
	/** @var int */
	protected $filesCounter = 0;

	public function __construct(IUserManager $userManager) {
		$this->userManager = $userManager;
		parent::__construct();
	}

	protected function configure() {
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
				'quiet',
				'q',
				InputOption::VALUE_NONE,
				'suppress any output'
			)
			->addOption(
				'verbose',
				'-v|vv|vvv',
				InputOption::VALUE_NONE,
				'verbose the output'
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
			);
	}

	public function checkScanWarning($fullPath, OutputInterface $output) {
		$normalizedPath = basename(\OC\Files\Filesystem::normalizePath($fullPath));
		$path = basename($fullPath);

		if ($normalizedPath !== $path) {
			$output->writeln("\t<error>Entry \"" . $fullPath . '" will not be accessible due to incompatible encoding</error>');
		}
	}

	protected function scanFiles($user, $path, $verbose, OutputInterface $output, $backgroundScan = false) {
		$connection = $this->reconnectToDatabase($output);
		$scanner = new \OC\Files\Utils\Scanner($user, $connection, \OC::$server->getLogger());
		# check on each file/folder if there was a user interrupt (ctrl-c) and throw an exception
		# printout and count
		if ($verbose) {
			$scanner->listen('\OC\Files\Utils\Scanner', 'scanFile', function ($path) use ($output) {
				$output->writeln("\tFile   <info>$path</info>");
				$this->filesCounter += 1;
				if ($this->hasBeenInterrupted()) {
					throw new \Exception('ctrl-c');
				}
			});
			$scanner->listen('\OC\Files\Utils\Scanner', 'scanFolder', function ($path) use ($output) {
				$output->writeln("\tFolder <info>$path</info>");
				$this->foldersCounter += 1;
				if ($this->hasBeenInterrupted()) {
					throw new \Exception('ctrl-c');
				}
			});
			$scanner->listen('\OC\Files\Utils\Scanner', 'StorageNotAvailable', function (StorageNotAvailableException $e) use ($output) {
				$output->writeln("Error while scanning, storage not available (" . $e->getMessage() . ")");
			});
		# count only
		} else {
			$scanner->listen('\OC\Files\Utils\Scanner', 'scanFile', function () use ($output) {
				$this->filesCounter += 1;
				if ($this->hasBeenInterrupted()) {
					throw new \Exception('ctrl-c');
				}
			});
			$scanner->listen('\OC\Files\Utils\Scanner', 'scanFolder', function () use ($output) {
				$this->foldersCounter += 1;
				if ($this->hasBeenInterrupted()) {
					throw new \Exception('ctrl-c');
				}
			});
		}
		$scanner->listen('\OC\Files\Utils\Scanner', 'scanFile', function($path) use ($output) {
			$this->checkScanWarning($path, $output);
		});
		$scanner->listen('\OC\Files\Utils\Scanner', 'scanFolder', function($path) use ($output) {
			$this->checkScanWarning($path, $output);
		});

		try {
			if ($backgroundScan) {
				$scanner->backgroundScan($path);
			}else {
				$scanner->scan($path);
			}
		} catch (ForbiddenException $e) {
			$output->writeln("<error>Home storage for user $user not writable</error>");
			$output->writeln("Make sure you're running the scan command only as the user the web server runs as");
		} catch (\Exception $e) {
			if ($e->getMessage() !== 'ctrl-c') {
				$output->writeln('<error>Exception while scanning: ' . $e->getMessage() . "\n" . $e->getTraceAsString() . '</error>');
			}
			return;
		}
	}


	protected function execute(InputInterface $input, OutputInterface $output) {
		$inputPath = $input->getOption('path');
		if ($inputPath) {
			$inputPath = '/' . trim($inputPath, '/');
			list (, $user,) = explode('/', $inputPath, 3);
			$users = array($user);
		} else if ($input->getOption('all')) {
			$users = $this->userManager->search('');
		} else {
			$users = $input->getArgument('user_id');
		}

		# no messaging level option means: no full printout but statistics
		# $quiet   means no print at all
		# $verbose means full printout including statistics
		# -q	-v	full	stat
		#  0	 0	no	yes
		#  0	 1	yes	yes
		#  1	--	no	no  (quiet overrules verbose)
		$verbose = $input->getOption('verbose');
		$quiet = $input->getOption('quiet');
		# restrict the verbosity level to VERBOSITY_VERBOSE
		if ($output->getVerbosity()>OutputInterface::VERBOSITY_VERBOSE) {
			$output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
		}
		if ($quiet) {
			$verbose = false;
		}

		# check quantity of users to be process and show it on the command line
		$users_total = count($users);
		if ($users_total === 0) {
			$output->writeln("<error>Please specify the user id to scan, \"--all\" to scan for all users or \"--path=...\"</error>");
			return;
		} else {
			if ($users_total > 1) {
				$output->writeln("\nScanning files for $users_total users");
			}
		}

		$this->initTools();

		$user_count = 0;
		foreach ($users as $user) {
			if (is_object($user)) {
				$user = $user->getUID();
			}
			$path = $inputPath ? $inputPath : '/' . $user;
			$user_count += 1;
			if ($this->userManager->userExists($user)) {
				# add an extra line when verbose is set to optical separate users
				if ($verbose) {$output->writeln(""); }
				$output->writeln("Starting scan for user $user_count out of $users_total ($user)");
				# full: printout data if $verbose was set
				$this->scanFiles($user, $path, $verbose, $output, $input->getOption('unscanned'));
			} else {
				$output->writeln("<error>Unknown user $user_count $user</error>");
			}
			# check on each user if there was a user interrupt (ctrl-c) and exit foreach
			if ($this->hasBeenInterrupted()) {
				break;
			}
		}

		# stat: printout statistics if $quiet was not set
		if (!$quiet) {
			$this->presentStats($output);
		}
	}

	/**
	 * Initialises some useful tools for the Command
	 */
	protected function initTools() {
		// Start the timer
		$this->execTime = -microtime(true);
		// Convert PHP errors to exceptions
		set_error_handler([$this, 'exceptionErrorHandler'], E_ALL);
	}

	/**
	 * Processes PHP errors as exceptions in order to be able to keep track of problems
	 *
	 * @see https://secure.php.net/manual/en/function.set-error-handler.php
	 *
	 * @param int $severity the level of the error raised
	 * @param string $message
	 * @param string $file the filename that the error was raised in
	 * @param int $line the line number the error was raised
	 *
	 * @throws \ErrorException
	 */
	public function exceptionErrorHandler($severity, $message, $file, $line) {
		if (!(error_reporting() & $severity)) {
			// This error code is not included in error_reporting
			return;
		}
		throw new \ErrorException($message, 0, $severity, $file, $line);
	}

	/**
	 * @param OutputInterface $output
	 */
	protected function presentStats(OutputInterface $output) {
		// Stop the timer
		$this->execTime += microtime(true);
		$output->writeln("");

		$headers = [
			'Folders', 'Files', 'Elapsed time'
		];

		$this->showSummary($headers, null, $output);
	}

	/**
	 * Shows a summary of operations
	 *
	 * @param string[] $headers
	 * @param string[] $rows
	 * @param OutputInterface $output
	 */
	protected function showSummary($headers, $rows, OutputInterface $output) {
		$niceDate = $this->formatExecTime();
		if (!$rows) {
			$rows = [
				$this->foldersCounter,
				$this->filesCounter,
				$niceDate,
			];
		}
		$table = new Table($output);
		$table
			->setHeaders($headers)
			->setRows([$rows]);
		$table->render();
	}


	/**
	 * Formats microtime into a human readable format
	 *
	 * @return string
	 */
	protected function formatExecTime() {
		list($secs, $tens) = explode('.', sprintf("%.1f", ($this->execTime)));

		# if you want to have microseconds add this:   . '.' . $tens;
		return date('H:i:s', $secs);
	}

	/**
	 * @return \OCP\IDBConnection
	 */
	protected function reconnectToDatabase(OutputInterface $output) {
		/** @var Connection | IDBConnection $connection*/
		$connection = \OC::$server->getDatabaseConnection();
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
