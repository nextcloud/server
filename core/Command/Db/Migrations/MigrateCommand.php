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
use Stecman\Component\Symfony\Console\BashCompletion\Completion\CompletionAwareInterface;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends Command implements CompletionAwareInterface {
	public function __construct(
		private Connection $connection,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('migrations:migrate')
			->setDescription('Execute a migration to a specified version or the latest available version.')
			->addArgument('app', InputArgument::REQUIRED, 'Name of the app this migration command shall work on')
			->addArgument('version', InputArgument::OPTIONAL, 'The version number (YYYYMMDDHHMMSS) or alias (first, prev, next, latest) to migrate to.', 'latest');

		parent::configure();
	}

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
	public function completeOptionValues($optionName, CompletionContext $context) {
		return [];
	}

	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeArgumentValues($argumentName, CompletionContext $context) {
		if ($argumentName === 'app') {
			$allApps = \OC_App::getAllApps();
			return array_diff($allApps, \OC_App::getEnabledApps(true, true));
		}

		if ($argumentName === 'version') {
			$appName = $context->getWordAtIndex($context->getWordIndex() - 1);

			$ms = new MigrationService($appName, $this->connection);
			$migrations = $ms->getAvailableVersions();

			array_unshift($migrations, 'next', 'latest');
			return $migrations;
		}

		return [];
	}
}
