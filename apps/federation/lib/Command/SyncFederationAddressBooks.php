<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Federation\Command;

use OCA\Federation\SyncFederationAddressBooks as SyncService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncFederationAddressBooks extends Command {
	public function __construct(
		private SyncService $syncService,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('federation:sync-addressbooks')
			->setDescription('Synchronizes addressbooks of all federated clouds')
			->addOption('full', null, InputOption::VALUE_NONE, 'Perform a full sync instead of a delta sync');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$progress = new ProgressBar($output);
		$progress->start();
		$full = (bool)$input->getOption('full');
		$this->syncService->syncThemAll(function ($url, $ex) use ($progress, $output): void {
			if ($ex instanceof \Exception) {
				$output->writeln("Error while syncing $url : " . $ex->getMessage());
			} else {
				$progress->advance();
			}
		}, $full);

		$progress->finish();
		$output->writeln('');

		return 0;
	}
}
