<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Command;

use OCA\DAV\CardDAV\SyncService;
use OCP\IConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncSystemAddressBook extends Command {
	public function __construct(
		private SyncService $syncService,
		private IConfig $config,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('dav:sync-system-addressbook')
			->setDescription('Synchronizes users to the system addressbook');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$output->writeln('Syncing users ...');
		$progress = new ProgressBar($output);
		$progress->start();
		$this->syncService->syncInstance(function () use ($progress): void {
			$progress->advance();
		});

		$progress->finish();
		$output->writeln('');
		$this->config->setAppValue('dav', 'needs_system_address_book_sync', 'no');
		return self::SUCCESS;
	}
}
