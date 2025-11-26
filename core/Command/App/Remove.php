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
			->setDescription('Remove an app from this Nextcloud instance')
			->setHelp(
				"Removes the specified app and, if present, runs the app's uninstall steps.\n" .
				"\n" .
				"By default, this command runs the app's uninstall steps (which may delete data) and then removes the app files.\n" .
				"Use `--keep-data` to skip uninstall steps and preserve app data (database tables, configuration, and stored files).\n" .
				"Note: Some apps may still preserve data either way, depending on their uninstall implementation.\n"
			)
			->addArgument(
				'app-id',
				InputArgument::REQUIRED,
				'remove the specified app'
			)
			->addOption(
				'keep-data',
				null,
				InputOption::VALUE_NONE,
				'Do not run uninstall tasks; preserve app data and configuration'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$appId = (string) $input->getArgument('app-id');
		$keepData  = (bool) $input->getOption('keep-data');

		// Prevent removal of shipped/core apps
		if ($this->manager->isShipped($appId)) {
			$output->writeln("App '$appId' is a shipped/core app and cannot be removed.");
			return self::FAILURE;
		}

		// Prevent removal of apps that aren't even installed (note: don't use isInstalled(); it's a misnomer)
		try {
			$this->manager->getAppPath($appId);
		} catch (AppPathNotFoundException $e) {
			$output->writeln("App '$appId' is not installed. Nothing to remove.");
			return self::FAILURE; // one could argue this a no-op and should be considered a success (?)
		}

		$appVersion = $this->manager->getAppVersion($appId);

		// Do not run the specified app's uninstall tasks -- preserving app data/config -- if requested
		if ($keepData) {
			$message = "Removing app '$appId' but keeping app data (uninstall hooks skipped).";
			$output->writeln($message);
			$this->logger->info($message, [ 'app' => 'CLI', ]);
		} else {
			// Disable the app before removing to trigger uninstall steps
			try {
				$this->manager->disableApp($appId);
				$message = "Disabled app '$appId' (uninstall steps executed).";
				$output->writeln($message);
				$this->logger->info($message, [ 'app' => 'CLI', ]);
			} catch (Throwable $e) {
				$message = "Failed to disable app '$appId' (version $appVersion) - app removal skipped.";
				$output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
				$output->writeln("\n" . $message);
				$this->logger->error($message, [ 'app' => 'CLI', 'exception' => $e, ]);
				return self::FAILURE;
			}
		}

		// Remove the specified app
		try {
			$removeSuccess = $this->installer->removeApp($appId);
		} catch (Throwable $e) {
			$removeSuccess = false;
			$output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
			$this->logger->error("Failed to remove app '$appId': " . $e->getMessage(), [ 'app' => 'CLI', 'exception' => $e, ]);
		}

		// Something went wrong during removeApp(); probably no removal took place or incomplete
		if (!$removeSuccess) {
			$message = "\nFailed to remove app '$appId' (version $appVersion) - app files/registration were not removed.";
			$output->writeln($message);
			$this->logger->error($message, [ 'app' => 'CLI', ]);
			return self::FAILURE;
		}
		
		$message = "Removed app '$appId' (version $appVersion).";
		$output->writeln($message);
		$this->logger->info($message, [ 'app' => 'CLI', ]);

		return self::SUCCESS;
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
			// TODO: Include disabled apps too
			return $this->manager->getEnabledApps();
		}
		return [];
	}
}
