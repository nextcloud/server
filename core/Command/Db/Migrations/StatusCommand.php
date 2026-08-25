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

class StatusCommand extends Command implements CompletionAwareInterface {
	public function __construct(
		private Connection $connection,
		private IAppManager $appManager,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure() {
		$this
			->setName('migrations:status')
			->setDescription('View the status of a set of migrations.')
			->addArgument('app', InputArgument::REQUIRED, 'Name of the app this migration command shall work on');
	}

	#[\Override]
	public function execute(InputInterface $input, OutputInterface $output): int {
		$appName = $input->getArgument('app');
		$ms = new MigrationService($appName, $this->connection, new ConsoleOutput($output));

		$infos = $this->getMigrationsInfos($ms);
		$title = sprintf('Database migration status for "%s"', $infos['App']);
		$output->writeln($title);
		$output->writeln(str_repeat('=', strlen($title)));
		$output->writeln('');

		if ($infos['Missing from Installed Code'] > 0) {
			$output->writeln(
				'<error>Status: Warning — migration history references migrations missing from installed code</error>',
			);
		} elseif ($infos['Unapplied'] > 0) {
			$output->writeln(sprintf(
				'<comment>Status: %d unapplied migration%s</comment>',
				$infos['Unapplied'],
				$infos['Unapplied'] === 1 ? '' : 's',
			));
		} else {
			$output->writeln('<info>Status: Up to date</info>');
		}
		$output->writeln('');

		$sections = [
			'Migration configuration' => [
				'App',
				'History Table',
				'Migration Namespace',
				'Migration Directory',
			],
			'Version status' => [
				'Previous Available',
				'Last Recorded as Executed',
				'Next Available',
				'Latest Available',
			],
			'Migration counts' => [
				'Recorded as Executed',
				'Missing from Installed Code',
				'Available in Installed Code',
				'Unapplied',
			],
		];

		foreach ($sections as $section => $keys) {
			$output->writeln($section);
			$output->writeln(str_repeat('-', strlen($section)));

			foreach ($keys as $key) {
				$output->writeln("$key: " . str_repeat(' ', 34 - strlen($key)) . $infos[$key]);
			}

			$output->writeln('');
		}

		$output->writeln('Unapplied migrations');
		$output->writeln('--------------------');

		$pending = $infos['Unapplied Migration Descriptions'];
		if (is_array($pending)) {
			foreach ($pending as $name => $description) {
				$output->writeln("$name: $description");
			}
		} else {
			$output->writeln($pending);
		}
	
		return 0;
	}

	/**
	 * @param string $optionName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	#[\Override]
	public function completeOptionValues($optionName, CompletionContext $context) {
		return [];
	}

	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	#[\Override]
	public function completeArgumentValues($argumentName, CompletionContext $context) {
		if ($argumentName === 'app') {
			$allApps = $this->appManager->getAllAppsInAppsFolders();
			return array_diff($allApps, $this->appManager->getEnabledApps());
		}
		return [];
	}

	/**
	 * @param MigrationService $ms
	 * @return array associative array of human readable info name as key and the actual information as value
	 */
	public function getMigrationsInfos(MigrationService $ms) {
		$executedMigrations = $ms->getMigratedVersions();
		$availableMigrations = $ms->getAvailableVersions();
		$executedUnavailableMigrations = array_diff($executedMigrations, array_keys($availableMigrations));

		$numExecutedUnavailableMigrations = count($executedUnavailableMigrations);
		$numNewMigrations = count(array_diff(array_keys($availableMigrations), $executedMigrations));
		$currentMigration = $executedMigrations === []
			? null
			: end($executedMigrations);

		$pending = $ms->describeMigrationStep();

		$infos = [
			'App' => $ms->getApp(),
			'History Table' => $ms->getMigrationsTableName(),
			'Migration Namespace' => $ms->getMigrationsNamespace(),
			'Migration Directory' => $ms->getMigrationsDirectory(),
			'Previous Available' => $this->getFormattedRelativeVersion(
				$availableMigrations,
				$currentMigration,
				-1,
			),
			'Last Recorded as Executed' => $currentMigration
				?? 'None (no migrations recorded as executed)',
			'Next Available' => $this->getFormattedRelativeVersion(
				$availableMigrations,
				$currentMigration,
				1,
			),
			'Latest Available' => $availableMigrations === []
				? 'None (no migration files found)'
				: end($availableMigrations),
			'Recorded as Executed' => count($executedMigrations),
			'Missing from Installed Code' => $numExecutedUnavailableMigrations,
			'Available in Installed Code' => count($availableMigrations),
			'Unapplied' => $numNewMigrations,
			'Unapplied Migration Descriptions' => count($pending) ? $pending : 'None'
		];

		return $infos;
	}

	/**
	 * @param list<string> $availableMigrations
	 */
	private function getFormattedRelativeVersion(
		array $availableMigrations,
		?string $currentMigration,
		int $offset,
	): string {
		if ($currentMigration === null) {
			if ($offset < 0) {
				return 'None (no migrations recorded as executed)';
			}

			return $availableMigrations === []
				? 'None (no migration files found)'
				: $availableMigrations[0];
		}

		$currentIndex = array_search(
			$currentMigration,
			$availableMigrations,
			true,
		);

		if ($currentIndex === false) {
			return 'Unknown (last executed migration is missing from code)';
		}

		$relativeIndex = $currentIndex + $offset;
		if (!isset($availableMigrations[$relativeIndex])) {
			return $offset < 0
				? 'None (at first available migration)'
				: 'None (at latest available migration)';
		}

		return $availableMigrations[$relativeIndex];
	}
}
