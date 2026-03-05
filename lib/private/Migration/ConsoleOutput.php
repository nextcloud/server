<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Migration;

use OCP\Migration\IOutput;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SimpleOutput
 *
 * Just a simple IOutput implementation with writes messages to the log file.
 * Alternative implementations will write to the console or to the web ui (web update case)
 *
 * @package OC\Migration
 */
class ConsoleOutput implements IOutput {
	private ?ProgressBar $progressBar = null;

	public function __construct(
		private OutputInterface $output,
	) {
	}

	public function debug(string $message): void {
		$this->output->writeln($message, OutputInterface::VERBOSITY_VERBOSE);
	}

	/**
	 * @param string $message
	 */
	public function info($message): void {
		$this->output->writeln("<info>$message</info>");
	}

	/**
	 * @param string $message
	 */
	public function warning($message): void {
		$this->output->writeln("<comment>$message</comment>");
	}

	/**
	 * @param int $max
	 */
	public function startProgress($max = 0): void {
		if (!is_null($this->progressBar)) {
			$this->progressBar->finish();
		}
		$this->progressBar = new ProgressBar($this->output);
		$this->progressBar->start($max);
	}

	/**
	 * @param int $step
	 * @param string $description
	 */
	public function advance($step = 1, $description = ''): void {
		if (is_null($this->progressBar)) {
			$this->progressBar = new ProgressBar($this->output);
			$this->progressBar->start();
		}
		$this->progressBar->advance($step);
		if (!is_null($description)) {
			$this->output->write(" $description");
		}
	}

	public function finishProgress(): void {
		if (is_null($this->progressBar)) {
			return;
		}
		$this->progressBar->finish();
	}
}
