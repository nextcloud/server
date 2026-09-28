<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\Command;

use OCA\DAV\Service\AbsenceService;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetAbsenceCommand extends Command {

	public function __construct(
		private IUserManager $userManager,
		private AbsenceService $absenceService,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('dav:absence:get');
		$this->addArgument(
			'user-id',
			InputArgument::REQUIRED,
			'User ID of the affected account'
		);
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$userId = $input->getArgument('user-id');

		$user = $this->userManager->get($userId);
		if ($user === null) {
			$output->writeln('<error>User not found</error>');
			return 1;
		}

		$absence = $this->absenceService->getAbsence($userId);
		if ($absence === null) {
			$output->writeln('<info>No absence set</info>');
			return 0;
		}

		$output->writeln('<info>Start day:</info> ' . $absence->getFirstDay());
		$output->writeln('<info>End day:</info> ' . $absence->getLastDay());
		$output->writeln('<info>Short message:</info> ' . $absence->getStatus());
		$output->writeln('<info>Message:</info> ' . $absence->getMessage());
		$output->writeln('<info>Replacement user:</info> ' . ($absence->getReplacementUserId() ?? 'none'));
		$output->writeln('<info>Replacement display name:</info> ' . ($absence->getReplacementUserDisplayName() ?? 'none'));

		return 0;
	}

}
