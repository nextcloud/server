<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command;

use DateTimeImmutable;
use OC\Core\Command\Info\FileUtils;
use OCP\Files\IRootFolder;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Touch extends Command {
	public function __construct(
		private readonly FileUtils $fileUtils,
		private readonly IRootFolder $rootFolder,
		private readonly ClockInterface $clock,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		$this
			->setName('files:touch')
			->setDescription('Update the last modified date of a file or folder, or create an empty file')
			->addArgument('file', InputArgument::REQUIRED, 'Nextcloud path or fileid for the file or folder to change the modified date of')
			->addOption('date', 'd', InputOption::VALUE_REQUIRED, 'Time to use as modified date instead of the current time. Acceptable formats are: ISO8601, "YYYY-MM-DD" and Unix time in seconds.')
			->addOption('no-create', 'c', InputOption::VALUE_NONE, 'Don\'t create an empty file if the target path doesn\'t exist');
	}

	#[\Override]
	public function execute(InputInterface $input, OutputInterface $output): int {
		$fileInput = $input->getArgument('file');
		$node = $this->fileUtils->getNode($fileInput);
		$date = $input->getOption('date');
		$noCreate = $input->getOption('no-create');

		if (!$node) {
			if ($noCreate || is_numeric($fileInput)) {
				$output->writeln("<error>$fileInput doesn't exist</error>");
				return self::FAILURE;
			}
			$node = $this->rootFolder->newFile($fileInput);
		}

		if ($date) {
			$mtime = $this->parseDateOption($date);
			if (!$mtime) {
				$output->writeln("<error>Invalid date format '$date'. Acceptable formats are: ISO8601, \"YYYY-MM-DD\" and Unix time in seconds.</error>");
			}
		} else {
			$mtime = $this->clock->now();
		}
		$node->touch($mtime->getTimestamp());

		return self::SUCCESS;
	}

	/**
	 * @return \DateTimeImmutable|false
	 */
	protected function parseDateOption(string $input) {
		$date = false;

		// Handle Unix timestamp
		if (filter_var($input, FILTER_VALIDATE_INT)) {
			return new DateTimeImmutable('@' . $input);
		}

		// ISO8601
		$date = DateTimeImmutable::createFromFormat(DateTimeImmutable::ATOM, $input);
		if ($date) {
			return $date;
		}
		// With fractions
		$date = DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.uP', $input);
		if ($date) {
			return $date;
		}

		// YYYY-MM-DD
		return DateTimeImmutable::createFromFormat('!Y-m-d', $input);
	}
}
