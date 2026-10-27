<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command;

use OC\DB\Connection;
use OC\DB\ConnectionAdapter;
use OC\Files\SetupManager;
use OC\Files\Utils\Scanner;
use OC\ForbiddenException;
use OC\Preview\Storage\StorageFactory;
use OCP\Console\Attribute\Argument;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\Exception\InterruptedException;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;
use OCP\Console\ISignalHandler;
use OCP\Console\Verbosity;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\StorageNotAvailableException;
use OCP\IConfig;
use OCP\Server;
use Psr\Log\LoggerInterface;

#[AsCommand(
	name: 'files:scan-app-data',
	description: 'rescan the AppData folder',
	supportsOutputFormat: true,
)]
class ScanAppData {
	protected float $execTime = 0;

	protected int $foldersCounter = 0;

	protected int $filesCounter = 0;
	protected int $previewsCounter = -1;

	public function __construct(
		protected IRootFolder $rootFolder,
		protected IConfig $config,
		private StorageFactory $previewStorage,
		private IEventDispatcher $eventDispatcher,
		private LoggerInterface $logger,
		private SetupManager $setupManager,
	) {
	}

	public function __invoke(
		IOutput $output,
		ISignalHandler $signalHandler,
		#[Argument(description: 'The appdata subfolder to scan')]
		string $folder = '',
	): ExitCode {
		# restrict the verbosity level to VERBOSITY_VERBOSE
		if ($output->getVerbosity()->value > Verbosity::Verbose->value) {
			$output->setVerbosity(Verbosity::Verbose);
		}

		$output->writeln('Scanning AppData for files');
		$output->writeln('');

		// Start the timer
		$this->execTime = -microtime(true);

		$this->initTools();

		$exitCode = $this->scanFiles($output, $signalHandler, $folder);
		if ($exitCode === ExitCode::Success) {
			$this->presentStats($output);
		}
		return $exitCode;
	}

	protected function getScanner(IOutput $output): Scanner {
		$connection = $this->reconnectToDatabase($output);
		return new Scanner(
			null,
			new ConnectionAdapter($connection),
			$this->eventDispatcher,
			$this->logger,
			$this->setupManager,
		);
	}

	protected function scanFiles(IOutput $output, ISignalHandler $signalHandler, string $folder): ExitCode {
		if ($folder === 'preview' || $folder === '') {
			$this->previewsCounter = $this->previewStorage->scan();

			if ($folder === 'preview') {
				return ExitCode::Success;
			}
		}

		try {
			/** @var Folder $appData */
			$appData = $this->getAppDataFolder();
		} catch (NotFoundException $e) {
			$output->writeln('<error>NoAppData folder found</error>');
			return ExitCode::Failure;
		}

		if ($folder !== '') {
			try {
				$appData = $appData->get($folder);
			} catch (NotFoundException $e) {
				$output->writeln('<error>Could not find folder: ' . $folder . '</error>');
				return ExitCode::Failure;
			}
		}

		$scanner = $this->getScanner($output);

		# check on each file/folder if there was a user interrupt (ctrl-c) and throw an exception
		$scanner->listen('\OC\Files\Utils\Scanner', 'scanFile', function ($path) use ($output, $signalHandler): void {
			$output->writeln("\tFile   <info>$path</info>", Verbosity::Verbose);
			++$this->filesCounter;
			$signalHandler->abortIfInterrupted();
		});

		$scanner->listen('\OC\Files\Utils\Scanner', 'scanFolder', function ($path) use ($output, $signalHandler): void {
			$output->writeln("\tFolder <info>$path</info>", Verbosity::Verbose);
			++$this->foldersCounter;
			$signalHandler->abortIfInterrupted();
		});

		$scanner->listen('\OC\Files\Utils\Scanner', 'StorageNotAvailable', function (StorageNotAvailableException $e) use ($output): void {
			$output->writeln('Error while scanning, storage not available (' . $e->getMessage() . ')', Verbosity::Verbose);
		});

		$scanner->listen('\OC\Files\Utils\Scanner', 'normalizedNameMismatch', function ($fullPath) use ($output): void {
			$output->writeln("\t<error>Entry \"" . $fullPath . '" will not be accessible due to incompatible encoding</error>');
		});

		try {
			$scanner->scan($appData->getPath());
		} catch (ForbiddenException $e) {
			$output->writeln('<error>Storage not writable</error>');
			$output->writeln('<info>Make sure you\'re running the scan command only as the user the web server runs as</info>');
			return ExitCode::Failure;
		} catch (InterruptedException $e) {
			# exit the function if ctrl-c has been pressed
			$output->writeln('<info>Interrupted by user</info>');
			return ExitCode::Failure;
		} catch (NotFoundException $e) {
			$output->writeln('<error>Path not found: ' . $e->getMessage() . '</error>');
			return ExitCode::Failure;
		} catch (\Exception $e) {
			$output->writeln('<error>Exception during scan: ' . $e->getMessage() . '</error>');
			$output->writeln('<error>' . $e->getTraceAsString() . '</error>');
			return ExitCode::Failure;
		}

		return ExitCode::Success;
	}

	/**
	 * Initialises some useful tools for the Command
	 */
	protected function initTools(): void {
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

	protected function presentStats(IOutput $output): void {
		// Stop the timer
		$this->execTime += microtime(true);
		$row = [];
		if ($this->previewsCounter !== -1) {
			$row['Previews'] = $this->previewsCounter;
		}
		$row['Folders'] = $this->foldersCounter;
		$row['Files'] = $this->filesCounter;
		$row['Elapsed time'] = $this->formatExecTime();

		$output->writeTableInOutputFormat([$row]);
	}

	/**
	 * Formats microtime into a human-readable format
	 */
	protected function formatExecTime(): string {
		$secs = round($this->execTime);
		# convert seconds into HH:MM:SS form
		return sprintf('%02d:%02d:%02d', (int)($secs / 3600), ((int)($secs / 60) % 60), (int)$secs % 60);
	}

	protected function reconnectToDatabase(IOutput $output): Connection {
		/** @var Connection $connection */
		$connection = Server::get(Connection::class);
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

	/**
	 * @throws NotFoundException
	 */
	private function getAppDataFolder(): Node {
		$instanceId = $this->config->getSystemValueString('instanceid', '');

		if ($instanceId === '') {
			throw new NotFoundException();
		}

		return $this->rootFolder->get('appdata_' . $instanceId);
	}
}
