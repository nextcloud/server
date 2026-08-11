<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\UserStatus\Command;

use OCA\UserStatus\Db\UserStatusMapper;
use OCA\UserStatus\Service\StatusService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Repair extends Command {

	public function __construct(
		private UserStatusMapper $mapper,
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

	/**
	 * Rows written before is_backup had a default are invisible to every query
	 * comparing it against false, so other users see them as offline and the
	 * cleanup job skips them.
	 */
	private function repairMissingBackupFlags(OutputInterface $output, bool $dryRun): void {
		$ids = $this->mapper->findStatusesWithoutBackupFlagIds();
		if ($ids === []) {
			$output->writeln('No statuses with a missing backup flag.');
			return;
		}

		$count = count($ids);
		if ($dryRun) {
			$output->writeln("Would set the backup flag on <info>$count</info> status(es).");
			$this->listIds($output, $ids);
			return;
		}

		$fixed = $this->mapper->normalizeBackupFlagByIds($ids);
		$output->writeln("Set the backup flag on <info>$fixed</info> status(es).");
	}

	/**
	 * A live status on an automated message id with no backup row can never be
	 * reverted by the automation that set it, and the heartbeat refuses to
	 * overwrite it, so the user is stuck. Removing the row lets the next
	 * heartbeat recreate a normal status.
	 */
	private function repairOrphanedStatuses(OutputInterface $output, bool $dryRun): void {
		$ids = $this->mapper->findOrphanedAutomatedStatusIds(StatusService::AUTOMATED_MESSAGE_IDS);
		if ($ids === []) {
			$output->writeln('No users stuck on an automated status.');
			return;
		}

		if ($dryRun) {
			$output->writeln('Would clear <info>' . count($ids) . '</info> status(es) stuck on an automated status.');
			$this->listIds($output, $ids);
			return;
		}

		$deleted = $this->mapper->deleteByIds($ids);
		$output->writeln("Cleared <info>$deleted</info> status(es) stuck on an automated status.");
	}

	/**
	 * A backup that can no longer be matched blocks every future automated
	 * status change for that user, because createBackupStatus() keeps hitting
	 * the unique constraint on user_id.
	 */
	private function repairStrandedBackups(OutputInterface $output, bool $dryRun): void {
		$ids = $this->mapper->findStrandedBackupIds(StatusService::AUTOMATED_MESSAGE_IDS);
		if ($ids === []) {
			$output->writeln('No stranded backup statuses.');
			return;
		}

		if ($dryRun) {
			$output->writeln('Would remove <info>' . count($ids) . '</info> stranded backup status(es).');
			$this->listIds($output, $ids);
			return;
		}

		$deleted = $this->mapper->deleteByIds($ids);
		$output->writeln("Removed <info>$deleted</info> stranded backup status(es).");
	}

	/**
	 * The ids are what an administrator needs to look the rows up themselves,
	 * but there can be a lot of them, so only spell them out when asked.
	 *
	 * @param list<int> $ids
	 */
	private function listIds(OutputInterface $output, array $ids): void {
		if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
			$output->writeln('  ids: ' . implode(', ', $ids));
		}
	}
}
