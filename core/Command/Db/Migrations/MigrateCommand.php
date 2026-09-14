<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2017 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Core\Command\Db\Migrations;

use OC\DB\Connection;
use OC\DB\MigrationService;
use OC\Migration\ConsoleOutput;
use OCP\App\IAppManager;
use Stecman\Component\Symfony\Console\BashCompletion\Completion\CompletionAwareInterface;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends Command implements CompletionAwareInterface {
	public function __construct(
		private Connection $connection,
		private IAppManager $appManager,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		$this
			->setName('migrations:migrate')
			->setDescription('Run pending database migrations for an app up to a specified migration ID or the latest available migration.')
			->addArgument(
				'app',
				InputArgument::REQUIRED,
				'The app whose database migrations should be run'
			)
			->addArgument(
				'version',
				InputArgument::OPTIONAL,
				'Target migration ID (e.g. 2404Date20220903071748) or latest.',
				'latest'
			);

		parent::configure();
	}

	#[\Override]
	public function execute(InputInterface $input, OutputInterface $output): int {
		$appName = $input->getArgument('app');
		$ms = new MigrationService($appName, $this->connection, new ConsoleOutput($output));
		$version = $input->getArgument('version');

		$ms->migrate($version);
		return 0;
	}

	/**
	 * @param string $optionName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	#[\Override]
	public function completeOptionValues($optionName, CompletionContext $context): array {
		return [];
	}

	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	#[\Override]
	public function completeArgumentValues($argumentName, CompletionContext $context): array {
		if ($argumentName === 'app') {
			$allApps = $this->appManager->getAllAppsInAppsFolders();
			return array_diff($allApps, $this->appManager->getEnabledApps());
		}

		if ($argumentName === 'version') {
			$appName = $context->getWordAtIndex($context->getWordIndex() - 1);

			$ms = new MigrationService($appName, $this->connection);
			$migrations = $ms->getAvailableVersions();

			array_unshift($migrations, 'latest');
			return $migrations;
		}

		return [];
	}
}
