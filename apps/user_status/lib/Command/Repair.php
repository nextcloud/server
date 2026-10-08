<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\UserStatus\Command;

use OCA\UserStatus\Service\StatusRepairService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Repair extends Command {

	public function __construct(
		private StatusRepairService $repairService,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		$this
			->setName('user-status:repair')
			->setDescription('Repair user statuses left behind by an interrupted automated status')
			->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only report what would be repaired');
	}

	#[\Override]
	public function execute(InputInterface $input, OutputInterface $output): int {
		$dryRun = (bool)$input->getOption('dry-run');
		if ($dryRun) {
			$output->writeln('<comment>Dry run, no changes will be written.</comment>');
			$output->writeln('');
		}

		$this->repairMissingBackupFlags($output, $dryRun);
		$this->repairOrphanedStatuses($output, $dryRun);
		$this->repairStrandedBackups($output, $dryRun);

		return self::SUCCESS;
	}

	/** The flag comes from the user id, so a pre-default backup stays a backup. */
	private function repairMissingBackupFlags(OutputInterface $output, bool $dryRun): void {
		$ids = $this->repairService->findStatusesWithoutBackupFlagIds();
		if ($ids === []) {
			$output->writeln('No statuses with a missing backup flag.');
			return;
		}

		$count = count($ids);
		if ($dryRun) {
			$output->writeln("Would give <info>$count</info> status(es) an explicit backup flag.");
			$this->listIds($output, $ids);
			return;
		}

		$fixed = $this->repairService->normalizeBackupFlagByIds($ids);
		$output->writeln("Gave <info>$fixed</info> status(es) an explicit backup flag.");
	}

	private function repairOrphanedStatuses(OutputInterface $output, bool $dryRun): void {
		$ids = $this->repairService->findOrphanedAutomatedStatusIds();
		if ($ids === []) {
			$output->writeln('No users stuck on an automated status.');
			return;
		}

		if ($dryRun) {
			$output->writeln('Would clear <info>' . count($ids) . '</info> status(es) stuck on an automated status.');
			$this->listIds($output, $ids);
			return;
		}

		$deleted = $this->repairService->deleteByIds($ids);
		$output->writeln("Cleared <info>$deleted</info> status(es) stuck on an automated status.");
	}

	private function repairStrandedBackups(OutputInterface $output, bool $dryRun): void {
		$ids = $this->repairService->findStrandedBackupIds();
		if ($ids === []) {
			$output->writeln('No stranded backup statuses.');
			return;
		}

		if ($dryRun) {
			$output->writeln('Would remove <info>' . count($ids) . '</info> stranded backup status(es).');
			$this->listIds($output, $ids);
			return;
		}

		$deleted = $this->repairService->deleteByIds($ids);
		$output->writeln("Removed <info>$deleted</info> stranded backup status(es).");
	}

	/**
	 * @param list<int> $ids
	 */
	private function listIds(OutputInterface $output, array $ids): void {
		if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
			$output->writeln('  ids: ' . implode(', ', $ids));
		}
	}
}
