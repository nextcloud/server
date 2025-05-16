<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Command;

use OC\Core\Command\Base;
use OCA\Files\Service\SettingsService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WindowsCompatibleFilenames extends Base {

	public function __construct(
		private SettingsService $service,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();

		$this
			->setName('files:windows-compatible-filenames')
			->setDescription('Enforce naming constraints for windows compatible filenames')
			->addOption('enable', description: 'Enable windows naming constraints')
			->addOption('disable', description: 'Disable windows naming constraints');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		if ($input->getOption('enable')) {
			if ($this->service->hasFilesWindowsSupport()) {
				$output->writeln('<error>Windows compatible filenames already enforced.</error>', OutputInterface::VERBOSITY_VERBOSE);
			}
			$this->service->setFilesWindowsSupport(true);
			$output->writeln('Windows compatible filenames enforced.');
		} elseif ($input->getOption('disable')) {
			if (!$this->service->hasFilesWindowsSupport()) {
				$output->writeln('<error>Windows compatible filenames already disabled.</error>', OutputInterface::VERBOSITY_VERBOSE);
			}
			$this->service->setFilesWindowsSupport(false);
			$output->writeln('Windows compatible filename constraints removed.');
		} else {
			$output->writeln('Windows compatible filenames are ' . ($this->service->hasFilesWindowsSupport() ? 'enforced' : 'disabled'));
		}
		return self::SUCCESS;
	}
}
