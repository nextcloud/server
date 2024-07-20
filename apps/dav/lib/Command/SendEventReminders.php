<?php
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Command;

use OCA\DAV\CalDAV\Reminder\ReminderService;
use OCP\IConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SendEventReminders
 *
 * @package OCA\DAV\Command
 */
class SendEventReminders extends Command {
	public function __construct(
		protected ReminderService $reminderService,
		protected IConfig $config,
	) {
		parent::__construct();
	}

	/**
	 * @inheritDoc
	 */
	protected function configure():void {
		$this
			->setName('dav:send-event-reminders')
			->setDescription('Sends event reminders');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		if ($this->config->getAppValue('dav', 'sendEventReminders', 'yes') !== 'yes') {
			$output->writeln('<error>Sending event reminders disabled!</error>');
			$output->writeln('<info>Please run "php occ config:app:set dav sendEventReminders --value yes"');
			return self::FAILURE;
		}

		if ($this->config->getAppValue('dav', 'sendEventRemindersMode', 'backgroundjob') !== 'occ') {
			$output->writeln('<error>Sending event reminders mode set to background-job!</error>');
			$output->writeln('<info>Please run "php occ config:app:set dav sendEventRemindersMode --value occ"');
			return self::FAILURE;
		}

		$this->reminderService->processReminders();
		return self::SUCCESS;
	}
}
