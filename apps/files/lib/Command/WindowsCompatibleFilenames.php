<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command;

use OCA\Files\Service\SettingsService;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\Attribute\Option;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;
use OCP\Console\Verbosity;

#[AsCommand(
	name: 'files:windows-compatible-filenames',
	description: 'Enforce naming constraints for windows compatible filenames',
)]
class WindowsCompatibleFilenames {
	public function __construct(
		private readonly SettingsService $service,
	) {
	}

	public function __invoke(
		IOutput $output,
		#[Option(description: 'Enable windows naming constraints')]
		bool $enable = false,
		#[Option(description: 'Disable windows naming constraints')]
		bool $disable = false,
	): ExitCode {
		if ($enable) {
			if ($this->service->hasFilesWindowsSupport()) {
				$output->writeln('<error>Windows compatible filenames already enforced.</error>', Verbosity::Verbose);
			}
			$this->service->setFilesWindowsSupport(true);
			$output->writeln('Windows compatible filenames enforced.');
		} elseif ($disable) {
			if (!$this->service->hasFilesWindowsSupport()) {
				$output->writeln('<error>Windows compatible filenames already disabled.</error>', Verbosity::Verbose);
			}
			$this->service->setFilesWindowsSupport(false);
			$output->writeln('Windows compatible filename constraints removed.');
		} else {
			$output->writeln('Windows compatible filenames are ' . ($this->service->hasFilesWindowsSupport() ? 'enforced' : 'disabled'));
		}
		return ExitCode::Success;
	}
}
