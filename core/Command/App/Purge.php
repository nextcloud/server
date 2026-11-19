<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\App;

use OC\App\AppDataPurger;
use OC\App\AppLeftovers;
use OC\Core\Command\Base;
use OCP\Util;
use Override;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Purge extends Base {
	public function __construct(
		private readonly AppDataPurger $purger,
	) {
		parent::__construct();
	}

	#[Override]
	protected function configure(): void {
		$this
			->setName('app:purge')
			->setDescription('List or delete the data that disabled and removed apps left behind')
			->setHelp(
				"Without an app id, lists the apps that are not enabled but still have data.\n"
				. "With one, shows what that app left behind: its database tables, app config, user preferences,\n"
				. "background jobs, migration records and app data folder. Nothing is deleted without --force.\n"
				. "\n"
				. "An app's tables are found from its migrations, so purge an app before removing its code:\n"
				. "  occ app:disable <app-id>\n"
				. "  occ app:purge <app-id> --force\n"
				. "  occ app:remove --keep-data <app-id>\n"
				. "\n"
				. "Files the app saved in users' own folders are not touched. Back up the database first."
			)
			->addArgument('app-id', InputArgument::OPTIONAL, 'the app whose data to show or delete')
			->addOption('force', 'f', InputOption::VALUE_NONE, 'delete it, rather than only showing what would be deleted');
		parent::configure();
	}

	#[Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$appId = $input->getArgument('app-id');
		$plain = $input->getOption('output') === self::OUTPUT_FORMAT_PLAIN;

		if ($appId === null) {
			$found = $this->purger->findAppsWithLeftovers();
			if (!$plain) {
				$this->writeArrayInOutputFormat($input, $output, array_map(fn (AppLeftovers $l) => $l->toArray(), $found));
			} elseif ($found === []) {
				$output->writeln('<info>No disabled or removed app has data left behind.</info>');
			} else {
				foreach ($found as $leftovers) {
					$code = $leftovers->codePresent ? 'disabled' : 'removed';
					$output->writeln("{$leftovers->appId} ($code): " . $this->summarize($leftovers));
				}
				$output->writeln('');
				$output->writeln('Run occ app:purge <app-id> to see the details for one app.');
			}
			return self::SUCCESS;
		}

		$appId = (string)$appId;
		$refusal = $this->purger->getRefusal($appId);
		if ($refusal !== null) {
			$output->writeln("<error>$refusal</error>");
			return self::FAILURE;
		}

		try {
			$leftovers = $this->purger->getLeftovers($appId);
		} catch (\RuntimeException $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			$output->writeln('Nothing was deleted.');
			return self::FAILURE;
		}
		$force = (bool)$input->getOption('force');

		if (!$plain) {
			if ($force && !$leftovers->isEmpty()) {
				$this->purger->purge($leftovers);
			}
			$this->writeArrayInOutputFormat($input, $output, $leftovers->toArray() + ['deleted' => $force && !$leftovers->isEmpty()]);
			return self::SUCCESS;
		}

		if ($leftovers->isEmpty()) {
			$output->writeln("<info>App '$appId' has no data left behind.</info>");
			return self::SUCCESS;
		}

		$output->writeln(($force ? 'Deleting' : 'Found') . " what app '$appId' left behind:");
		$this->describe($leftovers, $output);

		if (!$leftovers->codePresent) {
			$output->writeln('');
			$output->writeln("<comment>The app's code is gone, so its tables cannot be found. To delete them too, install it again,"
				. ' disable it, and run this before removing it. occ db:schema:check -v lists tables no app accounts for.</comment>');
		}

		if (!$force) {
			$output->writeln('');
			$output->writeln('Nothing was deleted. Run again with --force to delete all of it.');
			return self::SUCCESS;
		}

		$this->purger->purge($leftovers);
		$output->writeln('');
		$output->writeln("<info>Deleted what app '$appId' left behind.</info>");
		return self::SUCCESS;
	}

	private function describe(AppLeftovers $leftovers, OutputInterface $output): void {
		foreach ($leftovers->tables as $table) {
			$output->writeln("  - table $table");
		}
		$lines = [
			[$leftovers->configKeys, 'app config value', 'app config values'],
			[$leftovers->userPreferences, 'user preference', 'user preferences'],
			[$leftovers->backgroundJobs, 'background job', 'background jobs'],
			[$leftovers->migrations, 'migration record', 'migration records'],
		];
		foreach ($lines as [$count, $one, $many]) {
			if ($count > 0) {
				$output->writeln("  - $count " . ($count === 1 ? $one : $many));
			}
		}
		if ($leftovers->appDataSize !== null) {
			$output->writeln('  - app data folder, ' . Util::humanFileSize($leftovers->appDataSize));
		}
	}

	private function summarize(AppLeftovers $leftovers): string {
		$parts = [];
		$tables = count($leftovers->tables);
		foreach ([
			[$tables, 'table', 'tables'],
			[$leftovers->configKeys, 'config value', 'config values'],
			[$leftovers->userPreferences, 'user preference', 'user preferences'],
			[$leftovers->backgroundJobs, 'background job', 'background jobs'],
			[$leftovers->migrations, 'migration record', 'migration records'],
		] as [$count, $one, $many]) {
			if ($count > 0) {
				$parts[] = "$count " . ($count === 1 ? $one : $many);
			}
		}
		if ($leftovers->appDataSize !== null) {
			$parts[] = 'app data ' . Util::humanFileSize($leftovers->appDataSize);
		}
		return implode(', ', $parts);
	}
}
