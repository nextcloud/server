<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UpdateNotification\Command;

use OC\App\AppManager;
use OC\Installer;
use OCA\UpdateNotification\UpdateChecker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Check extends Command {

	public function __construct(
		private AppManager $appManager,
		private UpdateChecker $updateChecker,
		private Installer $installer,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('update:check')
			->setDescription('Check for server and app updates')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$updatesAvailableCount = 0;

		// Server
		$r = $this->updateChecker->getUpdateState();
		if (isset($r['updateAvailable']) && $r['updateAvailable']) {
			$output->writeln($r['updateVersionString'] . ' is available. Get more information on how to update at ' . $r['updateLink'] . '.');
			$updatesAvailableCount += 1;
		}


		// Apps
		$apps = $this->appManager->getEnabledApps();
		foreach ($apps as $app) {
			$update = $this->installer->isUpdateAvailable($app);
			if ($update !== false) {
				$output->writeln('Update for ' . $app . ' to version ' . $update . ' is available.');
				$updatesAvailableCount += 1;
			}
		}

		// Report summary
		if ($updatesAvailableCount === 0) {
			$output->writeln('<info>Everything up to date</info>');
		} elseif ($updatesAvailableCount === 1) {
			$output->writeln('<comment>1 update available</comment>');
		} else {
			$output->writeln('<comment>' . $updatesAvailableCount . ' updates available</comment>');
		}

		return 0;
	}
}
