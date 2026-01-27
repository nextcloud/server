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
use OCP\App\IAppManager;
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
use Sabre\DAV\Xml\Property\Href;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class CalendarMigrator implements IMigrator, ISizeEstimationMigrator {

	use TMigratorBasicVersionHandling;

	private const PATH_ROOT = Application::APP_ID . '/calendars/';
	private const PATH_VERSION = self::PATH_ROOT . 'version.json';
	private const PATH_CALENDARS = self::PATH_ROOT . 'calendars.json';
	private const PATH_SUBSCRIPTIONS = self::PATH_ROOT . 'subscriptions.json';
	private const USERS_URI_ROOT = 'principals/users/';
	private const MIGRATED_URI_PREFIX = 'migrated-';

	private const DAV_PROPERTY_URI = 'uri';
	private const DAV_PROPERTY_DISPLAYNAME = '{DAV:}displayname';
	private const DAV_PROPERTY_CALENDAR_COLOR = '{http://apple.com/ns/ical/}calendar-color';
	private const DAV_PROPERTY_CALENDAR_TIMEZONE = '{urn:ietf:params:xml:ns:caldav}calendar-timezone';
	private const DAV_PROPERTY_SUBSCRIBED_SOURCE = 'source';
	private const DAV_PROPERTY_SUBSCRIBED_STRIP_TODOS = '{http://calendarserver.org/ns/}subscribed-strip-todos';
	private const DAV_PROPERTY_SUBSCRIBED_STRIP_ALARMS = '{http://calendarserver.org/ns/}subscribed-strip-alarms';
	private const DAV_PROPERTY_SUBSCRIBED_STRIP_ATTACHMENTS = '{http://calendarserver.org/ns/}subscribed-strip-attachments';

	public function __construct(
		private readonly IAppManager $appManager,
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
	#[\Override]
	public function export(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$output->writeln('Exporting calendaring data…');
		$this->exportVersion($exportDestination, $output);
		$this->exportCalendars($user, $exportDestination, $output);
		$this->exportSubscriptions($user, $exportDestination, $output);
	}

	/**
	 * @throws CalendarMigratorException
	 */
	private function exportVersion(IExportDestination $exportDestination, OutputInterface $output): void {
		try {
			$versionData = [
				'appVersion' => $this->appManager->getAppVersion(Application::APP_ID),
			];
			$exportDestination->addFileContents(self::PATH_VERSION, json_encode($versionData, JSON_THROW_ON_ERROR));
		} catch (Throwable $e) {
			throw new CalendarMigratorException('Could not export version information', 0, $e);
		}
	}

	/**
	 * @throws CalendarMigratorException
	 */
	public function exportCalendars(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$output->writeln('Exporting calendars to ' . self::PATH_CALENDARS . '…');

		try {
			$calendarExports = $this->calendarManager->getCalendarsForPrincipal(self::USERS_URI_ROOT . $user->getUID());

			$exportData = [];
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

				// construct archive path for calendar data
				$filename = preg_replace('/[^a-z0-9-_]/iu', '', $calendar->getUri());
				$exportDataPath = self::PATH_ROOT . $filename . '.data';

				// add calendar metadata to the collection
				$exportData[] = [
					'format' => 'ical',
					'uri' => $calendar->getUri(),
					'label' => $calendar->getDisplayName(),
					'color' => $calendar->getDisplayColor(),
					'timezone' => $calendar->getSchedulingTimezone(),
				];

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

			// write all calendar metadata
			$exportDestination->addFileContents(self::PATH_CALENDARS, json_encode($exportData, JSON_THROW_ON_ERROR));

			$output->writeln('Exported ' . count($exportData) . ' calendar(s)…');
		} catch (Throwable $e) {
			throw new CalendarMigratorException('Could not export calendars', 0, $e);
		}
	}

	/**
	 * @throws CalendarMigratorException
	 */
	private function exportSubscriptions(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$output->writeln('Exporting calendar subscriptions to ' . self::PATH_SUBSCRIPTIONS . '…');

		try {
			$subscriptions = $this->calDavBackend->getSubscriptionsForUser(self::USERS_URI_ROOT . $user->getUID());

			$exportData = [];
			foreach ($subscriptions as $subscription) {
				$exportData[] = [
					'uri' => $subscription[self::DAV_PROPERTY_URI],
					'displayname' => $subscription[self::DAV_PROPERTY_DISPLAYNAME] ?? null,
					'color' => $subscription[self::DAV_PROPERTY_CALENDAR_COLOR] ?? null,
					'source' => $subscription[self::DAV_PROPERTY_SUBSCRIBED_SOURCE] ?? null,
					'striptodos' => $subscription[self::DAV_PROPERTY_SUBSCRIBED_STRIP_TODOS] ?? null,
					'stripalarms' => $subscription[self::DAV_PROPERTY_SUBSCRIBED_STRIP_ALARMS] ?? null,
					'stripattachments' => $subscription[self::DAV_PROPERTY_SUBSCRIBED_STRIP_ATTACHMENTS] ?? null,
				];
			}

			$exportDestination->addFileContents(self::PATH_SUBSCRIPTIONS, json_encode($exportData, JSON_THROW_ON_ERROR));

			$output->writeln('Exported ' . count($exportData) . ' calendar subscription(s)…');
		} catch (Throwable $e) {
			throw new CalendarMigratorException('Could not export calendar subscriptions', 0, $e);
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws CalendarMigratorException
	 */
	public function import(IUser $user, IImportSource $importSource, OutputInterface $output): void {
		$output->writeln('Importing calendaring data…');
		if ($importSource->getMigratorVersion($this->getId()) === null) {
			$output->writeln('No version for ' . static::class . ', skipping import…');
			return;
		}

		$this->importCalendars($user, $importSource, $output);
		$this->importSubscriptions($user, $importSource, $output);
	}

	/**
	 * @throws CalendarMigratorException
	 */
	public function importCalendars(IUser $user, IImportSource $importSource, OutputInterface $output): void {
		$output->writeln('Importing calendars from ' . self::PATH_ROOT . '…');

		$migratorVersion = $importSource->getMigratorVersion($this->getId());
		match ($migratorVersion) {
			1 => $this->importCalendarsV1($user, $importSource, $output),
			2 => $this->importCalendarsV2($user, $importSource, $output),
			default => throw new CalendarMigratorException('Unsupported migrator version ' . $migratorVersion . ' for ' . static::class),
		};
	}

	/**
	 * @throws CalendarMigratorException
	 */
	public function importCalendarsV2(IUser $user, IImportSource $importSource, OutputInterface $output): void {
		$output->writeln('Importing calendars from ' . self::PATH_CALENDARS . '…');

		if ($importSource->pathExists(self::PATH_CALENDARS) === false) {
			$output->writeln('No calendars to import…');
			return;
		}

		$importData = $importSource->getFileContents(self::PATH_CALENDARS);
		if (empty($importData)) {
			$output->writeln('No calendars to import…');
			return;
		}

		try {
			/** @var array<int, array<string, mixed>> $calendarsData */
			$calendarsData = json_decode($importData, true, 512, JSON_THROW_ON_ERROR);

			if (empty($calendarsData)) {
				$output->writeln('No calendars to import…');
				return;
			}

			$principalUri = self::USERS_URI_ROOT . $user->getUID();

			$importCount = 0;
			foreach ($calendarsData as $calendarMeta) {
				$migratedCalendarUri = self::MIGRATED_URI_PREFIX . $calendarMeta['uri'];
				$filename = preg_replace('/[^a-z0-9-_]/iu', '', $calendarMeta['uri']);
				$importDataPath = self::PATH_ROOT . $filename . '.data';

				try {
					// check if a calendar with this URI already exists
					$calendars = $this->calendarManager->getCalendarsForPrincipal($principalUri, [$migratedCalendarUri]);
					if (empty($calendars)) {
						$output->writeln("Creating calendar \"$migratedCalendarUri\"");
						// create the calendar
						$this->calDavBackend->createCalendar($principalUri, $migratedCalendarUri, [
							self::DAV_PROPERTY_DISPLAYNAME => $calendarMeta['label'] ?? $this->l10n->t('Migrated calendar (%1$s)', [$calendarMeta['uri']]),
							self::DAV_PROPERTY_CALENDAR_COLOR => $calendarMeta['color'] ?? $this->defaults->getColorPrimary(),
							self::DAV_PROPERTY_CALENDAR_TIMEZONE => $calendarMeta['timezone'] ?? null,
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

					$importCount++;
				} catch (Throwable $e) {
					$output->writeln('Failed to import calendar "' . ($calendarMeta['uri'] ?? 'unknown') . '", skipping…');
					continue;
				}
			}

			$output->writeln('Imported ' . $importCount . ' calendar(s)…');
		} catch (Throwable $e) {
			throw new CalendarMigratorException('Could not import calendars', 0, $e);
		}
	}

	/**
	 * @throws CalendarMigratorException
	 */
	public function importCalendarsV1(IUser $user, IImportSource $importSource, OutputInterface $output): void {
		$files = $importSource->getFolderListing(self::PATH_ROOT);
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
			$importDataPath = self::PATH_ROOT . $filename;

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
						self::DAV_PROPERTY_DISPLAYNAME => $calendarName ?? $this->l10n->t('Migrated calendar (%1$s)', [$calendarUri]),
						self::DAV_PROPERTY_CALENDAR_COLOR => $calendarColor ?? $this->defaults->getColorPrimary(),
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

	/**
	 * @throws CalendarMigratorException
	 */
	public function importSubscriptions(IUser $user, IImportSource $importSource, OutputInterface $output): void {
		$output->writeln('Importing calendar subscriptions from ' . self::PATH_SUBSCRIPTIONS . '…');

		if ($importSource->pathExists(self::PATH_SUBSCRIPTIONS) === false) {
			$output->writeln('No calendar subscriptions to import…');
			return;
		}

		$importData = $importSource->getFileContents(self::PATH_SUBSCRIPTIONS);
		if (empty($importData)) {
			$output->writeln('No calendar subscriptions to import…');
			return;
		}

		try {
			$subscriptions = json_decode($importData, true, 512, JSON_THROW_ON_ERROR);

			if (empty($subscriptions)) {
				$output->writeln('No calendar subscriptions to import…');
				return;
			}

			$principalUri = self::USERS_URI_ROOT . $user->getUID();
			$importCount = 0;
			foreach ($subscriptions as $subscription) {
				$output->writeln('Importing calendar subscription "' . ($subscription['displayname'] ?? $subscription['source'] ?? 'unknown') . '"');

				if (empty($subscription['source'])) {
					$output->writeln('Skipping subscription without source URL');
					continue;
				}

				$this->calDavBackend->createSubscription(
					$principalUri,
					$subscription['uri'] ? self::MIGRATED_URI_PREFIX . $subscription['uri'] : self::MIGRATED_URI_PREFIX . bin2hex(random_bytes(16)),
					[
						'{http://calendarserver.org/ns/}source' => new Href($subscription['source']),
						self::DAV_PROPERTY_DISPLAYNAME => $subscription['displayname'] ?? null,
						self::DAV_PROPERTY_CALENDAR_COLOR => $subscription['color'] ?? null,
						self::DAV_PROPERTY_SUBSCRIBED_STRIP_TODOS => $subscription['striptodos'] ?? null,
						self::DAV_PROPERTY_SUBSCRIBED_STRIP_ALARMS => $subscription['stripalarms'] ?? null,
						self::DAV_PROPERTY_SUBSCRIBED_STRIP_ATTACHMENTS => $subscription['stripattachments'] ?? null,
					]
				);
				$importCount++;
			}

			$output->writeln('Imported ' . $importCount . ' subscription(s)…');
		} catch (Throwable $e) {
			throw new CalendarMigratorException('Could not import calendar subscriptions', 0, $e);
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
