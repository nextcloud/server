<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\App;

use OC\Installer;
use OCP\App\AppPathNotFoundException;
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

	#[\Override]
	protected function configure(): void {
		$this
			->setName('app:update')
			->setDescription('Update an app, or all apps, from the app store')
			->addArgument(
				'app-id',
				InputArgument::OPTIONAL,
				'the ID of the app to update'
			)
			->addOption(
				'all',
				null,
				InputOption::VALUE_NONE,
				'update all apps that have an available update'
			)
			->addOption(
				'showonly',
				null,
				InputOption::VALUE_NONE,
				'only list available updates, without installing them'
			)
			->addOption(
				'showcurrent',
				null,
				InputOption::VALUE_NONE,
				'also show the currently installed version alongside the available update (implies --showonly)'
			)
			->addOption(
				'allow-unstable',
				null,
				InputOption::VALUE_NONE,
				'allow updating to unstable (e.g. beta) releases'
			)
		;
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$appStoreEnabled = $this->config->getSystemValueBool('appstoreenabled', true);
		if ($appStoreEnabled === false) {
			$output->writeln('App store access is disabled by the administrator; cannot check for updates');
			return 1;
		}

		$internetAvailable = $this->config->getSystemValueBool('has_internet_connection', true);
		$isDefaultAppStore = $this->config->getSystemValueString('appstoreurl', self::APP_STORE_URL) === self::APP_STORE_URL;
		if ($internetAvailable === false && $isDefaultAppStore === true) {
			$output->writeln('The default app store is configured, but Internet access is disabled, so the app store cannot be reached');
			return 1;
		}

		$singleAppId = $input->getArgument('app-id');
		$updateFound = false;
		$checkFailed = false;
		$showOnly = $input->getOption('showonly') || $input->getOption('showcurrent');

		if ($singleAppId) {
			$apps = [$singleAppId];
			try {
				$this->manager->getAppPath($singleAppId);
			} catch (AppPathNotFoundException) {
				$output->writeln('App "' . $singleAppId . '" is not installed');
				return 1;
			}
		} elseif ($input->getOption('all') || $showOnly) {
			$apps = $this->manager->getAllAppsInAppsFolders();
		} else {
			$output->writeln('<error>Please specify an app ID to update, or use "--all" to update all apps</error>');
			return 1;
		}

		$return = 0;
		foreach ($apps as $appId) {
			try {
				$newVersion = $this->installer->isUpdateAvailable(
					$appId,
					$input->getOption('allow-unstable'),
				);
			} catch (\Exception $e) {
				// Handles installer/app-manager failures that escape the app-store fetcher.
				$this->logger->error('Failure while checking for an update of app "' . $appId . '"', [
					'app' => 'app:update',
					'exception' => $e,
				]);
				$output->writeln('App "' . $appId . '" could not be checked for updates: ' . $e->getMessage());
				$checkFailed = true;
				$return = 1;
				continue;
			}

			if ($newVersion !== false) {
				$updateFound = true;
				if ($input->getOption('showcurrent')) {
					$message = 'App "' . $appId . '": ' . $this->manager->getAppVersion($appId) . ' → ' . $newVersion . ' available';
				} else {
					$message = 'App "' . $appId . '": update available (' . $newVersion . ')';
				}
				$output->writeln($message);

				if (!$showOnly) {
					try {
						$result = $this->installer->updateAppstoreApp(
							$appId,
							$input->getOption('allow-unstable'),
						);
					} catch (\Exception $e) {
						$this->logger->error('Failure during update of app "' . $appId . '"', [
							'app' => 'app:update',
							'exception' => $e,
						]);
						$output->writeln('App "' . $appId . '" could not be updated: ' . $e->getMessage());
						$return = 1;
						continue;
					}

					if ($result === false) {
						$output->writeln('App "' . $appId . '" could not be updated');
						$return = 1;
					} else {
						$output->writeln('App "' . $appId . '" updated successfully');
					}
				}
			}
		}

		if (!$updateFound) {
			if ($checkFailed) {
				if (!$singleAppId) {
					$output->writeln('Some apps could not be checked for updates; the rest are up to date');
				}
			} elseif ($singleAppId) {
				$output->writeln('App "' . $singleAppId . '" is already up to date');
			} else {
				$output->writeln('All apps are up to date');
			}
		}

		return $return;
	}
}
