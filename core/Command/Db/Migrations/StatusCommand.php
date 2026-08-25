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
			$output->writeln('<error>Status: Warning — migration history requires attention</error>');
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

			$values = [];
			foreach ($keys as $key) {
				$values[$key] = $infos[$key];
			}
			$this->writeKeyValueRows($output, $values);

			$output->writeln('');
		}

		$missingMigrationVersions = $infos['Missing Migration Versions'];
		if ($missingMigrationVersions !== []) {
			$output->writeln('<error>Warnings</error>');
			$output->writeln('--------');
			$output->writeln(sprintf(
				'%d migration%s recorded as executed %s not present in the installed code:',
				count($missingMigrationVersions),
				count($missingMigrationVersions) === 1 ? '' : 's',
				count($missingMigrationVersions) === 1 ? 'is' : 'are',
			));

			foreach ($missingMigrationVersions as $version) {
				$output->writeln('  - ' . $version);
			}

			$output->writeln('');
			$output->writeln(
				'If this is unexpected, verify that the installed app and server code match the intended version.',
			);
			$output->writeln(
				'Do not remove records from the migration history table manually.',
			);
			$output->writeln('');
		}

		$output->writeln('Unapplied migrations');
		$output->writeln('--------------------');

		$unappliedMigrations = $infos['Unapplied Migrations'];
		if ($unappliedMigrations === []) {
			$output->writeln('None');
		} else {
			$first = true;
			foreach ($unappliedMigrations as $version => $migration) {
				if (!$first) {
					$output->writeln('');
				}

				$output->writeln($version);
				$this->writeKeyValueRows($output, $migration, 2);
				$first = false;
			}
		}
	
		return 0;
	}

	/**
	 * @param array<string, scalar|null> $values
	 */
	private function writeKeyValueRows(
		OutputInterface $output,
		array $values,
		int $indent = 0,
	): void {
		$labelWidth = max(array_map(
			static fn (string $label): int => strlen($label) + 1,
			array_keys($values),
		));
		$prefix = str_repeat(' ', $indent);

		foreach ($values as $label => $value) {
			$output->writeln(sprintf(
				'%s%-' . $labelWidth . 's  %s',
				$prefix,
				$label . ':',
				$value,
			));
		}
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
		$executedUnavailableMigrations = array_diff($executedMigrations, $availableMigrations);
		$unappliedMigrationVersions = array_diff($availableMigrations, $executedMigrations);

		$numExecutedUnavailableMigrations = count($executedUnavailableMigrations);
		$numNewMigrations = count($unappliedMigrationVersions);
		$currentMigration = $executedMigrations === []
			? null
			: end($executedMigrations);

		$pending = $ms->describeMigrationStep();
		$unappliedMigrations = [];
		foreach ($unappliedMigrationVersions as $version) {
			$migration = $ms->createInstance($version);
			$unappliedMigrations[$version] = [
				'Name' => $migration->name() ?: 'Not provided',
				'Description' => $migration->description() ?: 'Not provided',
			];
		}

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
			'Missing Migration Versions' => array_values($executedUnavailableMigrations),
			'Available in Installed Code' => count($availableMigrations),
			'Unapplied' => $numNewMigrations,
			'Unapplied Migrations' => $unappliedMigrations,
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
