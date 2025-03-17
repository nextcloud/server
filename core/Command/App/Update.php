<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\App;

use OC\Installer;
use OCP\App\IAppManager;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Update extends Command {
	public const APP_STORE_URL = 'https://apps.nextcloud.com/api/v1';

	public function __construct(
		protected IAppManager $manager,
		protected IConfig $config,
		private Installer $installer,
		private LoggerInterface $logger,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('app:update')
			->setDescription('update an app or all apps')
			->addArgument(
				'app-id',
				InputArgument::OPTIONAL,
				'update the specified app'
			)
			->addOption(
				'all',
				null,
				InputOption::VALUE_NONE,
				'update all updatable apps'
			)
			->addOption(
				'showonly',
				null,
				InputOption::VALUE_NONE,
				'show update(s) without updating'
			)
			->addOption(
				'allow-unstable',
				null,
				InputOption::VALUE_NONE,
				'allow updating to unstable releases'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$appStoreEnabled = $this->config->getSystemValueBool('appstoreenabled', true);
		if ($appStoreEnabled === false) {
			$output->writeln('App store access is disabled');
			return 1;
		}

		$internetAvailable = $this->config->getSystemValueBool('has_internet_connection', true);
		$isDefaultAppStore = $this->config->getSystemValueString('appstoreurl', self::APP_STORE_URL) === self::APP_STORE_URL;
		if ($internetAvailable === false && $isDefaultAppStore === true) {
			$output->writeln('Internet connection is disabled, and therefore the default public App store is not reachable');
			return 1;
		}

		$singleAppId = $input->getArgument('app-id');
		$updateFound = false;

		if ($singleAppId) {
			$apps = [$singleAppId];
			try {
				$this->manager->getAppPath($singleAppId);
			} catch (\OCP\App\AppPathNotFoundException $e) {
				$output->writeln($singleAppId . ' not installed');
				return 1;
			}
		} elseif ($input->getOption('all') || $input->getOption('showonly')) {
			$apps = $this->manager->getAllAppsInAppsFolders();
		} else {
			$output->writeln('<error>Please specify an app to update or "--all" to update all updatable apps"</error>');
			return 1;
		}

		$return = 0;
		foreach ($apps as $appId) {
			$newVersion = $this->installer->isUpdateAvailable($appId, $input->getOption('allow-unstable'));
			if ($newVersion) {
				$updateFound = true;
				$output->writeln($appId . ' new version available: ' . $newVersion);

				if (!$input->getOption('showonly')) {
					try {
						$result = $this->installer->updateAppstoreApp($appId, $input->getOption('allow-unstable'));
					} catch (\Exception $e) {
						$this->logger->error('Failure during update of app "' . $appId . '"', [
							'app' => 'app:update',
							'exception' => $e,
						]);
						$output->writeln('Error: ' . $e->getMessage());
						$result = false;
						$return = 1;
					}

					if ($result === false) {
						$output->writeln($appId . ' couldn\'t be updated');
						$return = 1;
					} else {
						$output->writeln($appId . ' updated');
					}
				}
			}
		}

		if (!$updateFound) {
			if ($singleAppId) {
				$output->writeln($singleAppId . ' is up-to-date or no updates could be found');
			} else {
				$output->writeln('All apps are up-to-date or no updates could be found');
			}
		}

		return $return;
	}
}
