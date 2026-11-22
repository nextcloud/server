<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command;

use DateTimeImmutable;
use OC\Core\Command\Info\FileUtils;
use OCP\Console\Attribute\Argument;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\Attribute\Option;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;
use OCP\Files\IRootFolder;
use Psr\Clock\ClockInterface;

#[AsCommand(
	name: 'files:touch',
	description: 'Update the last modified date of a file or folder, or create an empty file',
)]
class Touch {
	public function __construct(
		private readonly FileUtils $fileUtils,
		private readonly IRootFolder $rootFolder,
		private readonly ClockInterface $clock,
	) {
	}

	public function __invoke(
		IOutput $output,
		#[Argument(description: 'Nextcloud path or fileid for the file or folder to change the modified date of')]
		string $file,
		#[Option(name: 'no-create', description: 'Don\'t create an empty file if the target path doesn\'t exist', shortcut: 'c')]
		bool $noCreate = false,
		#[Option(description: 'Time to use as modified date instead of the current time. Acceptable formats are: ISO8601, "YYYY-MM-DD" and Unix time in seconds.', shortcut: 'd')]
		?string $date = null,
	): ExitCode {
		$node = $this->fileUtils->getNode($file);

		if (!$node) {
			if ($noCreate || is_numeric($file)) {
				$output->writeln("<error>$file doesn't exist</error>");
				return ExitCode::Failure;
			}
			$node = $this->rootFolder->newFile($file);
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

		return ExitCode::Success;
	}

	/**
	 * @return \DateTimeImmutable|false
	 */
	protected function parseDateOption(string $input) {
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
