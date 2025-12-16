<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\UserMigration;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\CalendarImpl;
use OCA\DAV\CalDAV\Export\ExportService;
use OCA\DAV\CalDAV\Import\ImportService;
use OCP\Calendar\CalendarExportOptions;
use OCP\Calendar\CalendarImportOptions;
use OCP\Calendar\IManager as ICalendarManager;
use OCP\Defaults;
use OCP\IL10N;
use OCP\ITempManager;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\IMigrator;
use OCP\UserMigration\ISizeEstimationMigrator;
use OCP\UserMigration\TMigratorBasicVersionHandling;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class CalendarMigrator implements IMigrator, ISizeEstimationMigrator {

	use TMigratorBasicVersionHandling;

	private const USERS_URI_ROOT = 'principals/users/';
	private const MIGRATED_URI_PREFIX = 'migrated-';
	private const EXPORT_ROOT = Application::APP_ID . '/calendars/';

	public function __construct(
		private readonly CalDavBackend $calDavBackend,
		private readonly ICalendarManager $calendarManager,
		private readonly Defaults $defaults,
		private readonly IL10N $l10n,
		private readonly ExportService $exportService,
		private readonly ImportService $importService,
		private readonly ITempManager $tempManager,
	) {
		$this->version = 2;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getId(): string {
		return 'calendar';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDisplayName(): string {
		return $this->l10n->t('Calendar');
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDescription(): string {
		return $this->l10n->t('Calendars including events, details and attendees');
	}

	/**
	 * {@inheritDoc}
	 */
	public function getEstimatedExportSize(IUser $user): int|float {
		$principalUri = self::USERS_URI_ROOT . $user->getUID();
		$calendars = $this->calendarManager->getCalendarsForPrincipal($principalUri);

		$calendarCount = 0;
		$totalSize = 0;

		foreach ($calendars as $calendar) {
			if (!$calendar instanceof CalendarImpl) {
				continue;
			}
			if ($calendar->isShared()) {
				continue;
			}
			$calendarCount++;
			// Note: 'uid' is required because getLimitedCalendarObjects uses it as the array key
			$objects = $this->calDavBackend->getLimitedCalendarObjects((int)$calendar->getKey(), CalDavBackend::CALENDAR_TYPE_CALENDAR, ['uid', 'size']);
			foreach ($objects as $object) {
				$totalSize += (int)($object['size'] ?? 0);
			}
		}

		// 150B for meta file per calendar + total calendar data size
		$size = ($calendarCount * 150 + $totalSize) / 1024;

		return ceil($size);
	}

	/**
	 * {@inheritDoc}
	 */
	public function export(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$output->writeln('Exporting calendars into ' . CalendarMigrator::EXPORT_ROOT . '…');

		$calendarExports = $this->calendarManager->getCalendarsForPrincipal(self::USERS_URI_ROOT . $user->getUID());

		if (empty($calendarExports)) {
			$output->writeln('No calendars to export…');
		}

		try {
			/** @var CalendarImpl $calendar */
			foreach ($calendarExports as $calendar) {
				$output->writeln('Exporting calendar "' . $calendar->getUri() . '"');

				if (!$calendar instanceof CalendarImpl) {
					$output->writeln('Skipping unsupported calendar type for "' . $calendar->getUri() . '"');
					continue;
				}

				if ($calendar->isShared()) {
					$output->writeln('Skipping shared calendar "' . $calendar->getUri() . '"');
					continue;
				}

				// construct archive paths
				$filename = preg_replace('/[^a-z0-9-_]/iu', '', $calendar->getUri());
				$exportMetaPath = CalendarMigrator::EXPORT_ROOT . $filename . '.meta';
				$exportDataPath = CalendarMigrator::EXPORT_ROOT . $filename . '.data';

				// add calendar meta to the export archive
				$exportDestination->addFileContents($exportMetaPath, json_encode([
					'format' => 'ical',
					'uri' => $calendar->getUri(),
					'label' => $calendar->getDisplayName(),
					'color' => $calendar->getDisplayColor(),
					'timezone' => $calendar->getSchedulingTimezone(),
				], JSON_THROW_ON_ERROR));

				// export calendar data to a temporary file
				$options = new CalendarExportOptions();
				$options->setFormat('ical');
				$tempPath = $this->tempManager->getTemporaryFile();
				$tempFile = fopen($tempPath, 'w+');
				foreach ($this->exportService->export($calendar, $options) as $chunk) {
					fwrite($tempFile, $chunk);
				}

				// add the temporary file to the export archive
				rewind($tempFile);
				$exportDestination->addFileAsStream($exportDataPath, $tempFile);
				fclose($tempFile);
			}
		} catch (Throwable $e) {
			throw new CalendarMigratorException('Could not export calendars', 0, $e);
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws CalendarMigratorException
	 */
	public function import(IUser $user, IImportSource $importSource, OutputInterface $output): void {
		$migratorVersion = $importSource->getMigratorVersion($this->getId());

		if ($migratorVersion === null) {
			$output->writeln('No version for ' . static::class . ', skipping import…');
			return;
		}

		$output->writeln('Importing calendars from ' . CalendarMigrator::EXPORT_ROOT . '…');

		match ($migratorVersion) {
			1 => $this->importV1($user, $importSource, $output),
			2 => $this->importV2($user, $importSource, $output),
			default => throw new CalendarMigratorException('Unsupported migrator version ' . $migratorVersion . ' for ' . static::class),
		};
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws CalendarMigratorException
	 */
	public function importV2(IUser $user, IImportSource $importSource, OutputInterface $output): void {
		$files = $importSource->getFolderListing(CalendarMigrator::EXPORT_ROOT);
		if (empty($files)) {
			$output->writeln('No calendars to import…');
		}

		$principalUri = self::USERS_URI_ROOT . $user->getUID();

		foreach ($files as $filename) {
			// Only process .meta files
			if (!str_ends_with($filename, '.meta')) {
				continue;
			}

			// construct archive paths
			$importMetaPath = CalendarMigrator::EXPORT_ROOT . $filename;
			$importDataPath = CalendarMigrator::EXPORT_ROOT . substr($filename, 0, -5) . '.data';

			try {
				// read calendar meta from the import archive
				$calendarMeta = json_decode($importSource->getFileContents($importMetaPath), true, 512, JSON_THROW_ON_ERROR);
				$migratedCalendarUri = self::MIGRATED_URI_PREFIX . $calendarMeta['uri'];
				// check if a calendar with this URI already exists
				$calendars = $this->calendarManager->getCalendarsForPrincipal($principalUri, [$migratedCalendarUri]);
				if (empty($calendars)) {
					$output->writeln("Creating calendar \"$migratedCalendarUri\"");
					// create the calendar
					$this->calDavBackend->createCalendar($principalUri, $migratedCalendarUri, [
						'{DAV:}displayname' => $calendarMeta['label'] ?? $this->l10n->t('Migrated calendar (%1$s)', [$calendarMeta['uri']]),
						'{http://apple.com/ns/ical/}calendar-color' => $calendarMeta['color'] ?? $this->defaults->getColorPrimary(),
						'{urn:ietf:params:xml:ns:caldav}calendar-timezone' => $calendarMeta['timezone'] ?? null,
					]);
					// retrieve the created calendar
					$calendars = $this->calendarManager->getCalendarsForPrincipal($principalUri, [$migratedCalendarUri]);
					if (empty($calendars) || !($calendars[0] instanceof CalendarImpl)) {
						$output->writeln("Failed to retrieve created calendar \"$migratedCalendarUri\", skipping import…");
						continue;
					}
				} else {
					$output->writeln("Using existing calendar \"$migratedCalendarUri\"");
				}
				$calendar = $calendars[0];

				// copy import stream to temporary file as the source stream is not rewindable
				$importStream = $importSource->getFileAsStream($importDataPath);
				$tempPath = $this->tempManager->getTemporaryFile();
				$tempFile = fopen($tempPath, 'w+');
				stream_copy_to_stream($importStream, $tempFile);
				rewind($tempFile);

				// import calendar data
				try {
					$options = new CalendarImportOptions();
					$options->setFormat($calendarMeta['format'] ?? 'ical');
					$options->setErrors(0);
					$options->setValidate(1);
					$options->setSupersede(true);

					$outcome = $this->importService->import(
						$tempFile,
						$calendar,
						$options
					);
				} finally {
					fclose($tempFile);
				}

				$this->importSummary($calendarMeta['label'] ?? $calendarMeta['uri'], $outcome, $output);
			} catch (Throwable $e) {
				$output->writeln("Failed to import calendar \"$filename\", skipping…");
				continue;
			}
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws CalendarMigratorException
	 */
	public function importV1(IUser $user, IImportSource $importSource, OutputInterface $output): void {
		$files = $importSource->getFolderListing(CalendarMigrator::EXPORT_ROOT);
		if (empty($files)) {
			$output->writeln('No calendars to import…');
		}

		$principalUri = self::USERS_URI_ROOT . $user->getUID();

		foreach ($files as $filename) {
			// Only process .ics files
			if (!str_ends_with($filename, '.ics')) {
				continue;
			}

			// construct archive path
			$importDataPath = CalendarMigrator::EXPORT_ROOT . $filename;

			try {
				$calendarUri = substr($filename, 0, -4);
				$migratedCalendarUri = self::MIGRATED_URI_PREFIX . $calendarUri;

				// copy import stream to temporary file as the source stream is not rewindable
				$importStream = $importSource->getFileAsStream($importDataPath);
				$tempPath = $this->tempManager->getTemporaryFile();
				$tempFile = fopen($tempPath, 'w+');
				stream_copy_to_stream($importStream, $tempFile);
				rewind($tempFile);

				// check if a calendar with this URI already exists
				$calendars = $this->calendarManager->getCalendarsForPrincipal($principalUri, [$migratedCalendarUri]);
				if (empty($calendars)) {
					$output->writeln("Creating calendar \"$migratedCalendarUri\"");
					// extract calendar properties from the ICS header without full parsing
					$calendarName = null;
					$calendarColor = null;
					$headerLines = 0;
					while (($line = fgets($tempFile)) !== false && $headerLines < 50) {
						$headerLines++;
						$line = trim($line);
						if (str_starts_with($line, 'X-WR-CALNAME:')) {
							$calendarName = substr($line, 13);
						} elseif (str_starts_with($line, 'X-APPLE-CALENDAR-COLOR:')) {
							$calendarColor = substr($line, 23);
						}
						// stop parsing header once we hit the first component
						if (str_starts_with($line, 'BEGIN:VEVENT')
							|| str_starts_with($line, 'BEGIN:VTODO')
							|| str_starts_with($line, 'BEGIN:VJOURNAL')) {
							break;
						}
					}
					rewind($tempFile);
					// create the calendar
					$this->calDavBackend->createCalendar($principalUri, $migratedCalendarUri, [
						'{DAV:}displayname' => $calendarName ?? $this->l10n->t('Migrated calendar (%1$s)', [$calendarUri]),
						'{http://apple.com/ns/ical/}calendar-color' => $calendarColor ?? $this->defaults->getColorPrimary(),
					]);
					// retrieve the created calendar
					$calendars = $this->calendarManager->getCalendarsForPrincipal($principalUri, [$migratedCalendarUri]);
					if (empty($calendars) || !($calendars[0] instanceof CalendarImpl)) {
						$output->writeln("Failed to retrieve created calendar \"$migratedCalendarUri\", skipping import…");
						fclose($tempFile);
						continue;
					}
				} else {
					$output->writeln("Using existing calendar \"$migratedCalendarUri\"");
				}
				$calendar = $calendars[0];

				// import calendar data
				$options = new CalendarImportOptions();
				$options->setFormat('ical');
				$options->setErrors(0);
				$options->setValidate(1);
				$options->setSupersede(true);

				try {
					$outcome = $this->importService->import(
						$tempFile,
						$calendar,
						$options
					);
				} finally {
					fclose($tempFile);
				}

				$this->importSummary($calendarName ?? $calendarUri, $outcome, $output);
			} catch (Throwable $e) {
				$output->writeln("Failed to import calendar \"$filename\", skipping…");
				continue;
			}
		}
	}


	private function importSummary(string $label, array $outcome, OutputInterface $output): void {
		$created = 0;
		$updated = 0;
		$skipped = 0;
		$errors = 0;

		foreach ($outcome as $result) {
			match ($result['outcome'] ?? null) {
				'created' => $created++,
				'updated' => $updated++,
				'exists' => $skipped++,
				'error' => $errors++,
				default => null,
			};
		}

		$output->writeln("  \"$label\": $created created, $updated updated, $skipped skipped, $errors errors");
	}
}
