<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\App;

use OC\Installer;
use OCP\App\IAppManager;
use Psr\Log\LoggerInterface;
use Stecman\Component\Symfony\Console\BashCompletion\Completion\CompletionAwareInterface;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class Remove extends Command implements CompletionAwareInterface {
	public function __construct(
		protected IAppManager $manager,
		private Installer $installer,
		private LoggerInterface $logger,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('app:remove')
			->setDescription('remove an app')
			->addArgument(
				'app-id',
				InputArgument::REQUIRED,
				'remove the specified app'
			)
			->addOption(
				'keep-data',
				null,
				InputOption::VALUE_NONE,
				'keep app data and do not remove them'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$appId = $input->getArgument('app-id');

		// Check if the app is enabled
		if (!$this->manager->isEnabledForAnyone($appId)) {
			$output->writeln($appId . ' is not enabled');
			return 1;
		}

		// Removing shipped apps is not possible, therefore we pre-check that
		// before trying to remove it
		if ($this->manager->isShipped($appId)) {
			$output->writeln($appId . ' could not be removed as it is a shipped app');
			return 1;
		}

		// If we want to keep the data of the app, we simply don't disable it here.
		// App uninstall tasks are being executed when disabled. More info: PR #11627.
		if (!$input->getOption('keep-data')) {
			try {
				$this->manager->disableApp($appId);
				$output->writeln($appId . ' disabled');
			} catch (Throwable $e) {
				$output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
				$this->logger->error($e->getMessage(), [
					'app' => 'CLI',
					'exception' => $e,
				]);
				return 1;
			}
		}

		// Let's try to remove the app...
		try {
			$result = $this->installer->removeApp($appId);
		} catch (Throwable $e) {
			$output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
			$this->logger->error($e->getMessage(), [
				'app' => 'CLI',
				'exception' => $e,
			]);
			return 1;
		}

		if ($result === false) {
			$output->writeln($appId . ' could not be removed');
			return 1;
		}

		$appVersion = $this->manager->getAppVersion($appId);
		$output->writeln($appId . ' ' . $appVersion . ' removed');

		return 0;
	}

	/**
	 * @param string $optionName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeOptionValues($optionName, CompletionContext $context): array {
		return [];
	}

	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeArgumentValues($argumentName, CompletionContext $context): array {
		if ($argumentName === 'app-id') {
			return $this->manager->getEnabledApps();
		}
		return [];
	}
}
