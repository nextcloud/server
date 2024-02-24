<?php

declare(strict_types=1);

/**
 * @copyright 2023 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
