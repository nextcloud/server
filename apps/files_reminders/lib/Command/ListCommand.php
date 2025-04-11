<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesReminders\Command;

use DateTimeInterface;
use OC\Core\Command\Base;
use OCA\FilesReminders\Model\RichReminder;
use OCA\FilesReminders\Service\ReminderService;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ListCommand extends Base {
	public function __construct(
		private ReminderService $reminderService,
		private IUserManager $userManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('files:reminders')
			->setDescription('List file reminders')
			->addArgument(
				'user',
				InputArgument::OPTIONAL,
				'list reminders for user',
			)
			->addOption(
				'output',
				null,
				InputOption::VALUE_OPTIONAL,
				'Output format (plain, json or json_pretty, default is plain)',
				$this->defaultOutputFormat,
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$io = new SymfonyStyle($input, $output);

		$uid = $input->getArgument('user');
		if ($uid !== null) {
			/** @var string $uid */
			$user = $this->userManager->get($uid);
			if ($user === null) {
				$io->error("Unknown user <$uid>");
				return 1;
			}
		}

		$reminders = $this->reminderService->getAll($user ?? null);

		$outputOption = $input->getOption('output');
		switch ($outputOption) {
			case static::OUTPUT_FORMAT_JSON:
			case static::OUTPUT_FORMAT_JSON_PRETTY:
				$this->writeArrayInOutputFormat(
					$input,
					$io,
					array_map(
						fn (RichReminder $reminder) => $reminder->jsonSerialize(),
						$reminders,
					),
					'',
				);
				return 0;
			default:
				if (empty($reminders)) {
					$io->text('No reminders');
					return 0;
				}

				$io->table(
					['User Id', 'File Id', 'Path', 'Due Date', 'Updated At', 'Created At', 'Notified'],
					array_map(
						fn (RichReminder $reminder) => [
							$reminder->getUserId(),
							$reminder->getFileId(),
							$reminder->getNode()->getPath(),
							$reminder->getDueDate()->format(DateTimeInterface::ATOM), // ISO 8601
							$reminder->getUpdatedAt()->format(DateTimeInterface::ATOM), // ISO 8601
							$reminder->getCreatedAt()->format(DateTimeInterface::ATOM), // ISO 8601
							$reminder->getNotified() ? 'true' : 'false',
						],
						$reminders,
					),
				);
				return 0;
		}
	}
}
