<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Command;

use InvalidArgumentException;
use OCA\DAV\CalDAV\CalendarImpl;
use OCA\DAV\CalDAV\Import\ImportCountEvent;
use OCA\DAV\CalDAV\Import\ImportDisposition;
use OCA\DAV\CalDAV\Import\ImportObjectEvent;
use OCA\DAV\CalDAV\Import\ImportService;
use OCP\Calendar\CalendarImportOptions;
use OCP\Calendar\IManager;
use OCP\ITempManager;
use OCP\IUserManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Calendar Import Command
 *
 * Used to import data to supported calendars from disk or stdin
 */
#[AsCommand(
	name: 'calendar:import',
	description: 'Import calendar data to supported calendars from disk or stdin',
	hidden: false
)]
class ImportCalendar extends Command {
	public function __construct(
		private IUserManager $userManager,
		private IManager $calendarManager,
		private ITempManager $tempManager,
		private ImportService $importService,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		$this->setName('calendar:import')
			->setDescription('Import calendar data to supported calendars from disk or stdin')
			->addArgument('uid', InputArgument::REQUIRED, 'Id of system user')
			->addArgument('uri', InputArgument::REQUIRED, 'URI of calendar')
			->addArgument('location', InputArgument::OPTIONAL, 'Location to read the input from, defaults to stdin.')
			->addOption('format', null, InputOption::VALUE_REQUIRED, 'Format of input (ical, jcal, xcal) defaults to ical', 'ical')
			->addOption('errors', null, InputOption::VALUE_REQUIRED, 'how to handle item errors (0 - continue, 1 - fail)')
			->addOption('validation', null, InputOption::VALUE_REQUIRED, 'how to handle item validation (0 - no validation, 1 - validate and skip on issue, 2 - validate and fail on issue)')
			->addOption('supersede', null, InputOption::VALUE_NONE, 'override/replace existing items')
			->addOption('show-created', null, InputOption::VALUE_NONE, 'show all created items after processing')
			->addOption('show-updated', null, InputOption::VALUE_NONE, 'show all updated items after processing')
			->addOption('show-skipped', null, InputOption::VALUE_NONE, 'show all skipped items after processing')
			->addOption('show-errors', null, InputOption::VALUE_NONE, 'show all errored items after processing');
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$userId = $input->getArgument('uid');
		$calendarId = $input->getArgument('uri');
		$location = $input->getArgument('location');
		$format = $input->getOption('format');
		$errors = is_numeric($input->getOption('errors')) ? (int)$input->getOption('errors') : null;
		$validation = is_numeric($input->getOption('validation')) ? (int)$input->getOption('validation') : null;
		$supersede = $input->getOption('supersede');
		$showCreated = $input->getOption('show-created');
		$showUpdated = $input->getOption('show-updated');
		$showSkipped = $input->getOption('show-skipped');
		$showErrors = $input->getOption('show-errors');

		if (!$this->userManager->userExists($userId)) {
			throw new InvalidArgumentException("User <$userId> not found.");
		}
		// retrieve calendar and evaluate if import is supported and writeable
		$calendars = $this->calendarManager->getCalendarsForPrincipal('principals/users/' . $userId, [$calendarId]);
		if ($calendars === []) {
			throw new InvalidArgumentException("Calendar <$calendarId> not found");
		}
		$calendar = $calendars[0];
		if (!$calendar instanceof CalendarImpl) {
			throw new InvalidArgumentException("Calendar <$calendarId> doesn't support this function");
		}
		if (!$calendar->isWritable()) {
			throw new InvalidArgumentException("Calendar <$calendarId> is not writeable");
		}
		if ($calendar->isDeleted()) {
			throw new InvalidArgumentException("Calendar <$calendarId> is deleted");
		}
		// construct options object
		$options = new CalendarImportOptions();
		$options->setSupersede($supersede);
		if ($errors !== null) {
			$options->setErrors($errors);
		}
		if ($validation !== null) {
			$options->setValidate($validation);
		}
		$options->setFormat($format);
		$options->setCounts(true);
		// evaluate if a valid location was given and is usable otherwise default to stdin
		if ($location !== null) {
			$stream = fopen($location, 'r');
			if ($stream === false) {
				throw new InvalidArgumentException("Location <$location> is not valid. Cannot open location for read operation.");
			}
		} else {
			$stdin = fopen('php://stdin', 'r');
			if ($stdin === false) {
				throw new InvalidArgumentException('Cannot open stdin for read operation.');
			}
			$tempPath = $this->tempManager->getTemporaryFile();
			$stream = fopen($tempPath, 'w+');
			while (!feof($stdin)) {
				fwrite($stream, fread($stdin, 8192));
			}
			fclose($stdin);
			fseek($stream, 0);
		}
		$timeStarted = microtime(true);
		$totalCreated = 0;
		$totalUpdated = 0;
		$totalSkipped = 0;
		$totalErrors = 0;
		try {
			foreach ($this->importService->import($stream, $calendar, $options) as $event) {
				if ($event instanceof ImportCountEvent) {
					$output->writeln('Total objects to import: ' . $event->total());
					if ($showCreated || $showUpdated || $showSkipped || $showErrors) {
						$output->writeln('');
					}
					continue;
				}
				if (!$event instanceof ImportObjectEvent) {
					continue;
				}

				switch ($event->disposition) {
					case ImportDisposition::Created:
						$totalCreated++;
						if ($showCreated) {
							$output->writeln(['created: ' . ($event->identifier ?? 'unknown')]);
						}
						break;
					case ImportDisposition::Updated:
						$totalUpdated++;
						if ($showUpdated) {
							$output->writeln(['updated: ' . ($event->identifier ?? 'unknown')]);
						}
						break;
					case ImportDisposition::Exists:
						$totalSkipped++;
						if ($showSkipped) {
							$output->writeln(['skipped: ' . ($event->identifier ?? 'unknown')]);
						}
						break;
					case ImportDisposition::Error:
						$totalErrors++;
						if ($showErrors) {
							$output->writeln(['errors: ' . ($event->identifier ?? 'unknown')]);
							$output->writeln($event->errors);
						}
						break;
				}
			}
		} finally {
			fclose($stream);
		}
		$timeFinished = microtime(true);
		$output->writeln([
			'',
			'Import Completed',
			'================',
			'Execution Time: ' . ($timeFinished - $timeStarted) . ' sec',
			'Total Created: ' . $totalCreated,
			'Total Updated: ' . $totalUpdated,
			'Total Skipped: ' . $totalSkipped,
			'Total Errors: ' . $totalErrors,
			''
		]);

		return self::SUCCESS;
	}
}
