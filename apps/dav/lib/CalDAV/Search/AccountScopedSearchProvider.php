<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Search;

use NCU\Search\AccountScopedSearchResult;
use NCU\Search\IAccountScopedSearchProvider;
use NCU\Search\MetadataField;
use OCA\DAV\Search\SearchOperatorEvaluator;
use OCP\Calendar\ICalendar;
use OCP\Calendar\ICalendarExport;
use OCP\Calendar\ICalendarIsShared;
use OCP\Calendar\IManager;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;
use OCP\Files\Search\ISearchQuery;
use OCP\Files\SimpleFS\InMemoryFile;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Property;
use Sabre\VObject\Reader;

/**
 * @psalm-import-type DeclarativeSettingsFormField from \OCP\Settings\IDeclarativeSettingsForm
 */
class AccountScopedSearchProvider implements IAccountScopedSearchProvider {
	private const ID = 'calendar';

	/** The properties a `content` condition searches. Anything outside this list is not indexed. */
	private const CONTENT_PROPERTIES = ['SUMMARY', 'DESCRIPTION', 'LOCATION', 'CATEGORIES', 'COMMENT'];

	private const FIELD_PROPERTIES = [
		'content' => self::CONTENT_PROPERTIES,
		'name' => ['SUMMARY'],
		'attendee' => ['ATTENDEE'],
		'organizer' => ['ORGANIZER'],
	];

	private const COMPONENT_TYPES = ['VEVENT', 'VTODO', 'VJOURNAL'];

	/**
	 * Generated from contacts rather than entered by anyone, so collecting it would duplicate the
	 * Contacts provider and present derived data as a calendar record.
	 */
	private const GENERATED_CALENDAR_URIS = ['contact_birthdays'];

	public function __construct(
		private readonly IL10N $l10n,
		private readonly IManager $calendarManager,
		private readonly LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function getId(): string {
		return self::ID;
	}

	#[\Override]
	public function getName(): string {
		return $this->l10n->t('Calendar');
	}

	#[\Override]
	public function getSearchCriterion(): array {
		return [
			['id' => 'content', 'title' => $this->l10n->t('Content'), 'type' => 'text', 'default' => ''],
			['id' => 'name', 'title' => $this->l10n->t('Title'), 'type' => 'text', 'default' => ''],
			['id' => 'attendee', 'title' => $this->l10n->t('Attendee'), 'type' => 'text', 'default' => ''],
			['id' => 'organizer', 'title' => $this->l10n->t('Organizer'), 'type' => 'text', 'default' => ''],
			// When the meeting happens, not when the record was written — CalDAV exposes no filter
			// on the latter at all.
			['id' => 'event_start', 'title' => $this->l10n->t('Starts on or after'), 'type' => 'number', 'default' => 0],
			['id' => 'event_end', 'title' => $this->l10n->t('Ends on or before'), 'type' => 'number', 'default' => 0],
		];
	}

	#[\Override]
	public function getSearchResultMetadataKeys(): array {
		return [
			'owner' => $this->l10n->t('Owner'),
			'calendarName' => $this->l10n->t('Calendar'),
			'uid' => $this->l10n->t('UID'),
			'shared' => $this->l10n->t('Shared'),
			'checksum' => $this->l10n->t('Checksum'),
			'eventStart' => $this->l10n->t('Starts'),
			'eventEnd' => $this->l10n->t('Ends'),
			'organizer' => $this->l10n->t('Organizer'),
			'attendees' => $this->l10n->t('Attendees'),
			'location' => $this->l10n->t('Location'),
		];
	}

	#[\Override]
	public function search(string $userId, ISearchQuery $query): \Generator {
		$operation = $query->getSearchOperation();
		$options = $this->timerangeOptions($operation);

		$seen = [];
		$skipped = 0;
		$yielded = 0;

		foreach ($this->calendarsFor($userId) as $calendar) {
			foreach ($this->candidates($calendar, $options) as $object) {
				$uid = (string)($object['uid'] ?? '');
				if ($uid === '') {
					continue;
				}

				// The same UID can recur within one account — scheduling copies an invitation into
				// every attendee's own calendar, and recurrence expansion repeats it per occurrence.
				// Either way it is one meeting, not several.
				if (isset($seen[$uid])) {
					continue;
				}
				$seen[$uid] = true;

				if (!SearchOperatorEvaluator::matches($operation, fn (string $field): array => $this->valuesFor($object, $field))) {
					continue;
				}

				// Skipped after matching, because the offset counts matches, not candidates. Exact
				// because candidate order is sorted in candidates() rather than left to the backend.
				if ($skipped < $query->getOffset()) {
					$skipped++;

					continue;
				}
				if ($yielded >= $query->getLimit()) {
					return;
				}
				$yielded++;

				$summary = $this->firstString($object, 'SUMMARY');
				$entry = new AccountScopedSearchResult(
					$this->encodeId($calendar->getUri(), $uid),
					$summary !== '' ? $summary : ('Untitled ' . strtolower((string)($object['type'] ?? 'event'))),
				);

				$this->addMetaData($entry, 'owner', static fn (): string => $userId);
				$this->addMetaData($entry, 'calendarName', static fn (): string => (string)$calendar->getDisplayName());
				$this->addMetaData($entry, 'uid', static fn (): string => $uid);
				$this->addMetaData($entry, 'shared', static fn (): bool
					=> $calendar instanceof ICalendarIsShared ? $calendar->isShared() : false);

				$start = $this->firstTimestamp($object, 'DTSTART');
				$this->addMetaData($entry, 'eventStart', static function () use ($start): int {
					if ($start === null) {
						throw new \RuntimeException('this object carries no DTSTART');
					}

					return $start;
				});

				$end = $this->firstTimestamp($object, 'DTEND') ?? $start;
				$this->addMetaData($entry, 'eventEnd', static function () use ($end): int {
					if ($end === null) {
						throw new \RuntimeException('this object carries no DTEND');
					}

					return $end;
				});

				$ics = $this->exportObject($calendar, $uid);
				$this->addMetaData($entry, 'checksum', static function () use ($ics): string {
					if ($ics === null) {
						throw new \RuntimeException('calendar object not found while computing a checksum');
					}

					// Hashed here since there is nothing stored to read; re-serialising the same blob
					// is deterministic, so this is reproducible at export time.
					return 'SHA256:' . hash('sha256', $ics);
				});

				$attributes = $ics === null ? null : $this->attributesOf($ics);
				$this->addMetaData($entry, 'organizer', static function () use ($attributes): string {
					if (!isset($attributes['organizer'])) {
						throw new \RuntimeException('no organizer recorded on this object');
					}

					return $attributes['organizer'];
				});
				$this->addMetaData($entry, 'attendees', static function () use ($attributes): array {
					if (!isset($attributes['attendees'])) {
						throw new \RuntimeException('no attendees recorded on this object');
					}

					return $attributes['attendees'];
				});
				$this->addMetaData($entry, 'location', static function () use ($attributes): string {
					if (!isset($attributes['location'])) {
						throw new \RuntimeException('no location recorded on this object');
					}

					return $attributes['location'];
				});

				yield $entry;
			}
		}
	}

	private function addMetaData(AccountScopedSearchResult $entry, string $name, callable $read): void {
		try {
			$value = $read();
			$entry->addMetaData($value === null
				? MetadataField::notCaptured($name, 'not reported by the storage')
				: MetadataField::captured($name, $value));
		} catch (\Throwable $e) {
			$entry->addMetaData(MetadataField::notCaptured($name, $e->getMessage()));
		}
	}

	#[\Override]
	public function readContent(string $userId, AccountScopedSearchResult $entry): ?ISimpleFile {
		$decoded = $this->decodeId($entry->getId());
		if ($decoded === null) {
			return null;
		}
		[$calendarUri, $uid] = $decoded;

		$calendar = $this->findCalendar($userId, $calendarUri);
		$ics = $calendar === null ? null : $this->exportObject($calendar, $uid);
		if ($ics === null) {
			return null;
		}

		return new InMemoryFile($this->safeName($uid) . '.ics', $ics);
	}

	/**
	 * The facts about a meeting that are not its text: who called it, who was invited, where.
	 *
	 * @return array<string, mixed>
	 */
	private function attributesOf(string $ics): array {
		$attributes = [];

		try {
			$parsed = Reader::read($ics);
		} catch (\Throwable $e) {
			$this->logger->debug('Could not parse a calendar object for its attributes', ['exception' => $e]);

			return $attributes;
		}

		$component = null;
		foreach ($parsed->getComponents() as $candidate) {
			if (in_array($candidate->name, ['VEVENT', 'VTODO'], true)) {
				$component = $candidate;
				break;
			}
		}
		if ($component === null) {
			return $attributes;
		}

		// "CN <address>", because a name is what someone types into a search box and an address is
		// what identifies the person. Both, or whichever is there.
		$person = static function (Property $property): string {
			$name = trim((string)($property['CN'] ?? ''));
			$address = trim(str_ireplace('mailto:', '', (string)$property));

			if ($name === '' || $address === '') {
				return $name . $address;
			}

			return $name . ' <' . $address . '>';
		};

		foreach ($component->select('ORGANIZER') as $organizer) {
			$value = $person($organizer);
			if ($value !== '') {
				$attributes['organizer'] = $value;
			}
			break;
		}

		$attendees = [];
		foreach ($component->select('ATTENDEE') as $attendee) {
			$value = $person($attendee);
			if ($value !== '') {
				$attendees[] = $value;
			}
		}
		if ($attendees !== []) {
			$attributes['attendees'] = $attendees;
		}

		foreach ($component->select('LOCATION') as $location) {
			$value = trim((string)$location);
			if ($value !== '') {
				$attributes['location'] = $value;
			}
			break;
		}

		return $attributes;
	}

	/**
	 * The object as a standalone iCalendar document — attendees, alarms and recurrence rules
	 * intact, not a reconstruction from search results.
	 *
	 * `export()` has no uid filter, so this is a linear scan of the calendar per item. Fine for a
	 * personal calendar, worth caching per calendar per call if it ever shows up as a hot path.
	 */
	private function exportObject(ICalendar $calendar, string $uid): ?string {
		if (!$calendar instanceof ICalendarExport) {
			return null;
		}

		try {
			foreach ($calendar->export(null) as $vCalendar) {
				if ($this->uidOf($vCalendar) === $uid) {
					return $vCalendar->serialize();
				}
			}
		} catch (\Throwable $e) {
			$this->logger->warning('Calendar export failed', ['exception' => $e, 'uid' => $uid]);
		}

		return null;
	}

	/**
	 * Identity is the UID, not the DAV URI: it is the object's own identity, a client may PUT an
	 * object at any URI it likes, and every expanded occurrence of a recurrence carries it.
	 */
	private function uidOf(VCalendar $vCalendar): string {
		foreach ($vCalendar->getComponents() as $component) {
			$uid = (string)($component->UID ?? '');
			if ($uid !== '') {
				return $uid;
			}
		}

		return '';
	}

	/**
	 * Every object in the calendar, within the query's time range if it has one — otherwise
	 * deliberately not narrowed by the property index, see `SearchOperatorEvaluator`. The time
	 * range narrows soundly because it comes from the `DTSTART`/`DTEND` columns, not that index.
	 *
	 * @param array<string, mixed> $options
	 * @return list<array<string, mixed>>
	 */
	private function candidates(ICalendar $calendar, array $options): array {
		try {
			$page = $calendar->search('', [], $options, null);
		} catch (\Throwable $e) {
			// An unreadable calendar is not a reason to abandon the rest of the account.
			$this->logger->warning('Calendar search failed', ['exception' => $e, 'calendar' => $calendar->getUri()]);

			return [];
		}

		$found = [];
		foreach ($page as $object) {
			$found[(string)($object['uid'] ?? '')] = $object;
		}

		// Ordered by UID, so resuming a search skips the same matches an interrupted call already
		// yielded. CalDAV promises no order of its own.
		ksort($found);

		return array_values($found);
	}

	/**
	 * @return list<ICalendar>
	 */
	private function calendarsFor(string $userId): array {
		try {
			$calendars = $this->calendarManager->getCalendarsForPrincipal('principals/users/' . $userId);
		} catch (\Throwable $e) {
			$this->logger->warning('Could not list calendars for an account', ['exception' => $e, 'userId' => $userId]);

			return [];
		}

		return array_values(array_filter($calendars, static fn (ICalendar $calendar): bool
			// ICalendarExport excludes federated calendars, which are read-only mirrors of records
			// held elsewhere and cannot be exported as evidence anyway.
			=> $calendar instanceof ICalendarExport
			&& !in_array($calendar->getUri(), self::GENERATED_CALENDAR_URIS, true)));
	}

	private function findCalendar(string $userId, string $calendarUri): ?ICalendar {
		foreach ($this->calendarsFor($userId) as $calendar) {
			if ($calendar->getUri() === $calendarUri) {
				return $calendar;
			}
		}

		return null;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function timerangeOptions(?ISearchOperator $operation): array {
		$start = SearchOperatorEvaluator::mandatoryBound($operation, 'event_start', ISearchComparison::COMPARE_GREATER_THAN_EQUAL);
		$end = SearchOperatorEvaluator::mandatoryBound($operation, 'event_end', ISearchComparison::COMPARE_LESS_THAN_EQUAL);

		$options = ['types' => self::COMPONENT_TYPES];
		if ($start !== null) {
			$options['timerange']['start'] = (new \DateTimeImmutable())->setTimestamp($start);
		}
		if ($end !== null) {
			$options['timerange']['end'] = (new \DateTimeImmutable())->setTimestamp($end);
		}

		return $options;
	}

	/**
	 * @param array<string, mixed> $object
	 * @return list<mixed>
	 */
	private function valuesFor(array $object, string $field): array {
		if ($field === 'event_start') {
			$value = $this->firstTimestamp($object, 'DTSTART');

			return $value === null ? [] : [$value];
		}
		if ($field === 'event_end') {
			$value = $this->firstTimestamp($object, 'DTEND') ?? $this->firstTimestamp($object, 'DTSTART');

			return $value === null ? [] : [$value];
		}

		$values = [];
		foreach (self::FIELD_PROPERTIES[$field] ?? [] as $property) {
			foreach ($this->properties($object, $property) as $value) {
				$values[] = is_scalar($value) ? $value : null;
			}
		}

		return array_values(array_filter($values, static fn (mixed $v): bool => $v !== null));
	}

	/**
	 * Property values across every component of the object, flattened.
	 *
	 * CalDAV hands back `[value, parameters]` for a property that may occur once and a list of
	 * those for one that may repeat; `is_array($raw[0])` tells them apart, because a single value
	 * is always a string or a date.
	 *
	 * @param array<string, mixed> $object
	 * @return list<mixed>
	 */
	private function properties(array $object, string $name): array {
		$values = [];
		foreach ($object['objects'] ?? [] as $component) {
			$raw = $component[$name] ?? null;
			if (!is_array($raw) || $raw === []) {
				continue;
			}

			$entries = is_array($raw[0] ?? null) ? $raw : [$raw];
			foreach ($entries as $entry) {
				$values[] = is_array($entry) ? ($entry[0] ?? null) : $entry;
			}
		}

		return $values;
	}

	/**
	 * @param array<string, mixed> $object
	 */
	private function firstString(array $object, string $name): string {
		foreach ($this->properties($object, $name) as $value) {
			if (is_scalar($value) && (string)$value !== '') {
				return (string)$value;
			}
		}

		return '';
	}

	/**
	 * @param array<string, mixed> $object
	 */
	private function firstTimestamp(array $object, string $name): ?int {
		foreach ($this->properties($object, $name) as $value) {
			if ($value instanceof \DateTimeInterface) {
				return $value->getTimestamp();
			}
		}

		return null;
	}

	private function safeName(string $uid): string {
		return trim(preg_replace('/[^\p{L}\p{N}._-]+/u', '_', $uid) ?? '', '_') ?: 'event';
	}

	private function encodeId(string $calendarUri, string $uid): string {
		return rawurlencode($calendarUri) . ':' . $uid;
	}

	/**
	 * @return array{0: string, 1: string}|null
	 */
	private function decodeId(string $id): ?array {
		$parts = explode(':', $id, 2);
		if (count($parts) !== 2) {
			return null;
		}

		return [rawurldecode($parts[0]), $parts[1]];
	}
}
