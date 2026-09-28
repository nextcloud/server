<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Command;

use OC\Files\Cache\Scanner;
use OCA\Files_External\Service\GlobalStoragesService;
use OCP\IUserManager;
use OCP\Lock\LockedException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Scan extends StorageAuthBase {
	protected float $execTime = 0;
	protected int $foldersCounter = 0;
	protected int $filesCounter = 0;

	public function __construct(
		GlobalStoragesService $globalService,
		IUserManager $userManager,
	) {
		parent::__construct($globalService, $userManager);
	}

	protected function configure(): void {
		$this
			->setName('files_external:scan')
			->setDescription('Scan an external storage for changed files')
			->addArgument(
				'mount_id',
				InputArgument::REQUIRED,
				'the mount id of the mount to scan'
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
				InputOption::VALUE_OPTIONAL,
				'The path in the storage to scan',
				''
			)->addOption(
				'unscanned',
				'',
				InputOption::VALUE_NONE,
				'only scan files which are marked as not fully scanned'
			);
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		[, $storage] = $this->createStorage($input, $output);
		if ($storage === null) {
			return 1;
		}

		$path = $input->getOption('path');

		$this->execTime = -microtime(true);

		/** @var Scanner $scanner */
		$scanner = $storage->getScanner();

		$scanner->listen('\OC\Files\Cache\Scanner', 'scanFile', function (string $path) use ($output): void {
			$output->writeln("\tFile\t<info>$path</info>", OutputInterface::VERBOSITY_VERBOSE);
			++$this->filesCounter;
			$this->abortIfInterrupted();
		});

		$scanner->listen('\OC\Files\Cache\Scanner', 'scanFolder', function (string $path) use ($output): void {
			$output->writeln("\tFolder\t<info>$path</info>", OutputInterface::VERBOSITY_VERBOSE);
			++$this->foldersCounter;
			$this->abortIfInterrupted();
		});

		try {
			if ($input->getOption('unscanned')) {
				if ($path !== '') {
					$output->writeln('<error>--unscanned is mutually exclusive with --path</error>');
					return 1;
				}
				$scanner->backgroundScan();
			} else {
				$scanner->scan($path);
			}
		} catch (LockedException $e) {
			if (is_string($e->getReadablePath()) && str_starts_with($e->getReadablePath(), 'scanner::')) {
				if ($e->getReadablePath() === 'scanner::') {
					$output->writeln('<error>Another process is already scanning this storage</error>');
				} else {
					$output->writeln('<error>Another process is already scanning \'' . substr($e->getReadablePath(), strlen('scanner::')) . '\' in this storage</error>');
				}
			} else {
				throw $e;
			}
		}

		$this->presentStats($output);

		return 0;
	}

	/**
	 * @param OutputInterface $output
	 */
	protected function presentStats(OutputInterface $output): void {
		// Stop the timer
		$this->execTime += microtime(true);

		$headers = [
			'Folders', 'Files', 'Elapsed time'
		];

		$this->showSummary($headers, [], $output);
	}

	/**
	 * Shows a summary of operations
	 *
	 * @param string[] $headers
	 * @param string[] $rows
	 * @param OutputInterface $output
	 */
	protected function showSummary(array $headers, array $rows, OutputInterface $output): void {
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
	protected function formatExecTime(): string {
		$secs = round($this->execTime);
		# convert seconds into HH:MM:SS form
		return sprintf('%02d:%02d:%02d', ($secs / 3600), ($secs / 60 % 60), $secs % 60);
	}
}
