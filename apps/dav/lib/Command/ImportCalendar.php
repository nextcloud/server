<?php
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Command;

use InvalidArgumentException;
use OCA\DAV\CalDAV\Import\ImportService;
use OCP\Calendar\CalendarImportOptions;
use OCP\Calendar\ICalendarImport;
use OCP\Calendar\ICalendarIsWritable;
use OCP\Calendar\IManager;
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
 *
 * @since 32.0.0
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
		private ImportService $importService,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('calendar:import')
			->setDescription('Import calendar data to supported calendars from disk or stdin')
			->addArgument('uid', InputArgument::REQUIRED, 'Id of system user')
			->addArgument('cid', InputArgument::REQUIRED, 'Id of calendar')
			->addArgument('format', InputArgument::OPTIONAL, 'Format of output (iCal, jCal, xCal) default to iCal')
			->addArgument('location', InputArgument::OPTIONAL, 'location of where to write the output. defaults to stdin')
			->addOption('errors', null, InputOption::VALUE_REQUIRED, 'how to handel item errors (0 - continue, 1 - fail)')
			->addOption('validation', null, InputOption::VALUE_REQUIRED, 'how to handel item validation (0 - no validation, 1 - validate and skip on issue, 2 - validate and fail on issue)')
			->addOption('supersede', null, InputOption::VALUE_NONE, 'override/replace existing items')
			->addOption('show-created', null, InputOption::VALUE_NONE, 'show all created items after processing')
			->addOption('show-updated', null, InputOption::VALUE_NONE, 'show all updated items after processing')
			->addOption('show-skipped', null, InputOption::VALUE_NONE, 'show all skipped items after processing')
			->addOption('show-errors', null, InputOption::VALUE_NONE, 'show all errored items after processing')
		;
		
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		
		$userId = $input->getArgument('uid');
		$calendarId = $input->getArgument('cid');
		$format = $input->getArgument('format');
		$location = $input->getArgument('location');
		$errors = $input->getOption('errors');
		$validation = $input->getOption('validation');
		$supersede = $input->getOption('supersede') ? true : false;
		$showCreated = $input->getOption('show-created') ? true : false;
		$showUpdated = $input->getOption('show-updated') ? true : false;
		$showSkipped = $input->getOption('show-skipped') ? true : false;
		$showErrors = $input->getOption('show-errors') ? true : false;

		if (!$this->userManager->userExists($userId)) {
			throw new InvalidArgumentException("User <$userId> not found.");
		}
		// retrieve calendar and evaluate if import is supported and writeable
		$calendars = $this->calendarManager->getCalendarsForPrincipal('principals/users/' . $userId, [$calendarId]);
		if ($calendars === []) {
			throw new InvalidArgumentException("Calendar <$calendarId> not found");
		}
		$calendar = $calendars[0];
		if (!$calendar instanceof ICalendarImport || !$calendar instanceof ICalendarIsWritable) {
			throw new InvalidArgumentException("Calendar <$calendarId> dose support this function");
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
			if ($errors < 0 || $errors > 1) {
				throw new InvalidArgumentException('Invalid errors option specified');
			}
			$options->setErrors($errors);
		}
		if ($validation !== null) {
			if ($validation < 0 || $validation > 2) {
				throw new InvalidArgumentException('Invalid validation option specified');
			}
			$options->setValidate($validation);
		}
		// evaluate if provided format is supported
		if ($format !== null && !in_array($format, $this->importService::FORMATS, true)) {
			throw new \InvalidArgumentException("Format <$format> is not valid.");
		} else {
			$options->setFormat($format ?? 'ical');
		}
		// evaluate if a valid location was given and is usable otherwise default to stdin
		$timeStarted = microtime(true);
		if ($location !== null) {
			$input = fopen($location, 'r');
			if ($input === false) {
				throw new \InvalidArgumentException("Location <$location> is not valid. Can not open location for read operation.");
			} else {
				try {
					$outcome = $this->importService->import($input, $calendar, $options);
				} finally {
					fclose($input);
				}
			}
		} else {
			$input = fopen('php://stdin', 'r');
			if ($input === false) {
				throw new \InvalidArgumentException('Can not open stdin for read operation.');
			} else {
				try {
					$temp = tmpfile();
					while (!feof($input)) {
						fwrite($temp, fread($input, 8192));
					}
					fseek($temp, 0);
					$outcome = $this->importService->import($temp, $calendar, $options);
				} finally {
					fclose($input);
					fclose($temp);
				}
			}
		}
		$timeFinished = microtime(true);

		$totalCreated = 0;
		$totalUpdated = 0;
		$totalSkipped = 0;
		$totalErrors = 0;

		if ($outcome !== []) {

			if ($showCreated || $showUpdated || $showSkipped || $showErrors) {
				$output->writeln('');
			}

			foreach ($outcome as $id => $result) {
				if (isset($result['outcome'])) {
					switch ($result['outcome']) {
						case 'created':
							$totalCreated++;
							if ($showCreated) {
								$output->writeln(['created: ' . $id]);
							}
							break;
						case 'updated':
							$totalUpdated++;
							if ($showUpdated) {
								$output->writeln(['updated: ' . $id]);
							}
							break;
						case 'exists':
							$totalSkipped++;
							if ($showSkipped) {
								$output->writeln(['skipped: ' . $id]);
							}
							break;
						case 'error':
							$totalErrors++;
							if ($showErrors) {
								$output->writeln(['errors: ' . $id]);
								$output->writeln($result['errors']);
							}
							break;
					}
				}
				
			}
		}

		$output->writeln([
			'',
			'Import Completed',
			'================',
			'Execution Time: ' . (string)($timeFinished - $timeStarted) . ' sec',
			'Total Created: ' . (string)$totalCreated,
			'Total Updated: ' . (string)$totalUpdated,
			'Total Skipped: ' . (string)$totalSkipped,
			'Total Errors: ' . (string)$totalErrors,
			''
		]);

		return self::SUCCESS;
	}
}
