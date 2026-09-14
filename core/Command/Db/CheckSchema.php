<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Db;

use OC\Core\Command\Base;
use OC\DB\SchemaChecker;
use Override;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckSchema extends Base {
	public function __construct(
		private readonly SchemaChecker $schemaChecker,
	) {
		parent::__construct();
	}

	#[Override]
	protected function configure(): void {
		$this
			->setName('db:schema:check')
			->setDescription('Compare the live database schema against the schema expected for the currently installed version')
			->setHelp("Note that the expected schema might not exactly match the live schema as the expected schema doesn't take into account any database wide settings or defaults.")
			->addArgument('table', InputArgument::OPTIONAL, 'Only check the schema for the specified table');
		parent::configure();
	}

	#[Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$onlyTable = $input->getArgument('table');
		$findings = $this->schemaChecker->getFindings($onlyTable);
		['blocking' => $blocking, 'byDisabledApp' => $byDisabledApp] = $this->schemaChecker->partitionFindings($findings);

		if ($input->getOption('output') === self::OUTPUT_FORMAT_PLAIN) {
			if ($findings === []) {
				$output->writeln('<info>The live database schema matches the expected schema.</info>');
			} else {
				foreach ($blocking as $finding) {
					$output->writeln('<comment>' . $this->schemaChecker->formatFinding($finding) . '</comment>');
				}
				if ($output->isVerbose()) {
					$this->printDisabledAppFindings($byDisabledApp, $output);
				}
			}
		} else {
			$this->writeArrayInOutputFormat($input, $output, $findings);
		}

		return $blocking === [] ? 0 : 1;
	}

	/**
	 * @param array<string, list<array{table: string, type: string, name?: string, changes?: list<string>, app: ?string, enabled: bool}>> $byDisabledApp
	 */
	private function printDisabledAppFindings(array $byDisabledApp, OutputInterface $output): void {
		if ($byDisabledApp === []) {
			return;
		}

		$output->writeln('Disabled apps (not affecting exit code):');
		$output->writeln('If the schema for a disabled app differs from what is expected, this might indicate the app was updated since it was disabled. Missing migrations will be applied once the app is enabled again.');
		foreach ($byDisabledApp as $app => $appFindings) {
			$output->writeln("  {$app}:");
			foreach ($appFindings as $finding) {
				$output->writeln('    - <comment>' . $this->schemaChecker->formatFinding($finding) . '</comment>');
			}
		}
	}
}
