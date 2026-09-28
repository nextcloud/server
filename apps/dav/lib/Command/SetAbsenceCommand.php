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

class SetAbsenceCommand extends Command {

	public function __construct(
		private IUserManager $userManager,
		private AbsenceService $absenceService,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('dav:absence:set');
		$this->addArgument(
			'user-id',
			InputArgument::REQUIRED,
			'User ID of the affected account'
		);
		$this->addArgument(
			'first-day',
			InputArgument::REQUIRED,
			'Inclusive start day formatted as YYYY-MM-DD'
		);
		$this->addArgument(
			'last-day',
			InputArgument::REQUIRED,
			'Inclusive end day formatted as YYYY-MM-DD'
		);
		$this->addArgument(
			'short-message',
			InputArgument::REQUIRED,
			'Short message'
		);
		$this->addArgument(
			'message',
			InputArgument::REQUIRED,
			'Message'
		);
		$this->addArgument(
			'replacement-user-id',
			InputArgument::OPTIONAL,
			'Replacement user id'
		);
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$userId = $input->getArgument('user-id');

		$user = $this->userManager->get($userId);
		if ($user === null) {
			$output->writeln('<error>User not found</error>');
			return 1;
		}

		$replacementUserId = $input->getArgument('replacement-user-id');
		if ($replacementUserId === null) {
			$replacementUser = null;
		} else {
			$replacementUser = $this->userManager->get($replacementUserId);
			if ($replacementUser === null) {
				$output->writeln('<error>Replacement user not found</error>');
				return 2;
			}
		}

		$this->absenceService->createOrUpdateAbsence(
			$user,
			$input->getArgument('first-day'),
			$input->getArgument('last-day'),
			$input->getArgument('short-message'),
			$input->getArgument('message'),
			$replacementUser?->getUID(),
			$replacementUser?->getDisplayName(),
		);

		return 0;
	}

}
