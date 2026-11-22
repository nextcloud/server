<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Search;

use NCU\Search\AccountScopedSearchResult;
use NCU\Search\Exceptions\AccountUnavailableException;
use NCU\Search\IAccountScopedSearchProvider;
use NCU\Search\SearchPropertyDefinition;
use NCU\Search\SearchPropertyType;
use OCA\DAV\CalDAV\CalendarImpl;
use OCA\DAV\Search\SearchOperatorEvaluator;
use OCP\Calendar\ICalendar;
use OCP\Calendar\ICalendarExport;
use OCP\Calendar\ICalendarIsShared;
use OCP\Calendar\IManager;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;
use OCP\Files\SimpleFS\InMemoryFile;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IL10N;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Component;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Property;
use Sabre\VObject\Property\ICalendar\DateTime;

class CalendarAccountScopedSearchProvider implements IAccountScopedSearchProvider {
	private const ID = 'calendar';

	private const CONTENT_PROPERTIES = ['SUMMARY', 'DESCRIPTION', 'LOCATION', 'CATEGORIES', 'COMMENT'];

	private const FIELD_PROPERTIES = [
		'content' => self::CONTENT_PROPERTIES,
		'name' => ['SUMMARY'],
		'attendee' => ['ATTENDEE'],
		'organizer' => ['ORGANIZER'],
	];

	private const COMPONENT_TYPES = ['VEVENT', 'VTODO', 'VJOURNAL'];

	private const GENERATED_CALENDAR_URIS = ['contact_birthdays'];

	public function __construct(
		private readonly IL10N $l10n,
		private readonly IManager $calendarManager,
		private readonly IUserManager $userManager,
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
	public function getProperties(): array {
		return [
			new SearchPropertyDefinition('content', $this->l10n->t('Content'), searchable: true),
			new SearchPropertyDefinition('name', $this->l10n->t('Title'), searchable: true),
			new SearchPropertyDefinition('owner', $this->l10n->t('Owner'), selectable: true),
			new SearchPropertyDefinition('calendar_name', $this->l10n->t('Calendar'), selectable: true),
			new SearchPropertyDefinition('uid', $this->l10n->t('UID'), selectable: true),
			new SearchPropertyDefinition('shared', $this->l10n->t('Shared'), SearchPropertyType::Boolean, selectable: true),
			new SearchPropertyDefinition('checksum', $this->l10n->t('Checksum'), selectable: true, detailOnly: true),
			new SearchPropertyDefinition('event_start', $this->l10n->t('Starts'), SearchPropertyType::DateTime, searchable: true, selectable: true),
			new SearchPropertyDefinition('event_end', $this->l10n->t('Ends'), SearchPropertyType::DateTime, searchable: true, selectable: true),
			new SearchPropertyDefinition('organizer', $this->l10n->t('Organizer'), searchable: true, selectable: true, detailOnly: true),
			new SearchPropertyDefinition('attendee', $this->l10n->t('Attendees'), searchable: true, selectable: true, detailOnly: true),
			new SearchPropertyDefinition('location', $this->l10n->t('Location'), selectable: true, detailOnly: true),
		];
	}

	#[\Override]
	public function search(string $userId, ?ISearchOperator $filter, int $limit, int $offset = 0): \Generator {
		$this->assertAccount($userId);
		$options = $this->timerangeOptions($filter);

		$seen = [];
		$skipped = 0;
		$yielded = 0;

		foreach ($this->calendarsFor($userId) as $calendar) {
			foreach ($this->candidates($calendar, $options) as $object) {
				$uid = (string)($object['uid'] ?? '');
				if ($uid === '') {
					continue;
				}

				// Invitations and recurrences repeat a UID, but it is still one item.
				if (isset($seen[$uid])) {
					continue;
				}
				$seen[$uid] = true;

				if (!SearchOperatorEvaluator::matches($filter, fn (string $field): array => $this->valuesFor($object, $field))) {
					continue;
				}

				// The offset counts matches, not candidates.
				if ($skipped < $offset) {
					$skipped++;

					continue;
				}
				if ($yielded >= $limit) {
					return;
				}
				$yielded++;

				yield $this->entryFor(
					$calendar,
					$userId,
					$uid,
					$this->firstString($object, 'SUMMARY'),
					(string)($object['type'] ?? 'event'),
					$this->firstTimestamp($object, 'DTSTART'),
					$this->firstTimestamp($object, 'DTEND'),
				);
			}
		}
	}

	#[\Override]
	public function get(string $userId, string $id): ?AccountScopedSearchResult {
		$this->assertAccount($userId);
		$found = $this->findObject($userId, $id);
		if ($found === null) {
			return null;
		}
		[$calendar, $uid, $vCalendar] = $found;

		$component = $this->mainComponent($vCalendar);
		$entry = $this->entryFor(
			$calendar,
			$userId,
			$uid,
			(string)($component?->SUMMARY ?? ''),
			$component?->name ?? 'event',
			$component === null ? null : $this->timestampOf($component, 'DTSTART'),
			$component === null ? null : $this->timestampOf($component, 'DTEND'),
		);

		$ics = $vCalendar->serialize();
		$this->addMetaData($entry, 'checksum', static fn (): string => 'SHA256:' . hash('sha256', $ics));

		$attributes = $component === null ? [] : $this->attributesOf($component);
		$this->addMetaData($entry, 'organizer', static function () use ($attributes): string {
			if (!isset($attributes['organizer'])) {
				throw new \RuntimeException('no organizer recorded on this object');
			}

			return $attributes['organizer'];
		});
		$this->addMetaData($entry, 'attendee', static function () use ($attributes): array {
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

		return $entry;
	}

	#[\Override]
	public function readContent(string $userId, string $id): ?ISimpleFile {
		$this->assertAccount($userId);
		$found = $this->findObject($userId, $id);
		if ($found === null) {
			return null;
		}
		[, $uid, $vCalendar] = $found;

		return new InMemoryFile($this->safeName($uid) . '.ics', $vCalendar->serialize());
	}

	private function entryFor(ICalendar $calendar, string $userId, string $uid, string $summary, string $type, ?int $start, ?int $end): AccountScopedSearchResult {
		$entry = new AccountScopedSearchResult(
			$this->encodeId($calendar->getUri(), $uid),
			$summary !== '' ? $summary : ('Untitled ' . strtolower($type)),
		);

		$this->addMetaData($entry, 'owner', fn (): string => $this->ownerOf($calendar, $userId));
		$this->addMetaData($entry, 'calendar_name', static fn (): string => (string)$calendar->getDisplayName());
		$this->addMetaData($entry, 'uid', static fn (): string => $uid);
		$this->addMetaData($entry, 'shared', static fn (): bool
			=> $calendar instanceof ICalendarIsShared ? $calendar->isShared() : false);

		$this->addMetaData($entry, 'event_start', static function () use ($start): int {
			if ($start === null) {
				throw new \RuntimeException('this object carries no DTSTART');
			}

			return $start;
		});

		$end ??= $start;
		$this->addMetaData($entry, 'event_end', static function () use ($end): int {
			if ($end === null) {
				throw new \RuntimeException('this object carries no DTEND');
			}

			return $end;
		});

		return $entry;
	}

	private function ownerOf(ICalendar $calendar, string $userId): string {
		$principal = $calendar instanceof CalendarImpl ? $calendar->getOwnerPrincipalUri() : '';

		return str_starts_with($principal, 'principals/users/')
			? substr($principal, strlen('principals/users/'))
			: $userId;
	}

	/**
	 * @throws AccountUnavailableException
	 */
	private function assertAccount(string $userId): void {
		if (!$this->userManager->userExists($userId)) {
			throw new AccountUnavailableException('No such account: ' . $userId);
		}
	}

	/**
	 * @return array{0: ICalendar, 1: string, 2: VCalendar}|null
	 */
	private function findObject(string $userId, string $id): ?array {
		$decoded = $this->decodeId($id);
		if ($decoded === null) {
			return null;
		}
		[$calendarUri, $uid] = $decoded;

		$calendar = $this->findCalendar($userId, $calendarUri);
		if ($calendar === null) {
			return null;
		}

		$vCalendar = $this->exportObject($calendar, $uid);

		return $vCalendar === null ? null : [$calendar, $uid, $vCalendar];
	}

	private function mainComponent(VCalendar $vCalendar): ?Component {
		foreach ($vCalendar->getComponents() as $component) {
			if (in_array($component->name, self::COMPONENT_TYPES, true)) {
				return $component;
			}
		}

		return null;
	}

	private function timestampOf(Component $component, string $name): ?int {
		$property = $component->select($name)[0] ?? null;

		return $property instanceof DateTime ? $property->getDateTime()->getTimestamp() : null;
	}

	private function addMetaData(AccountScopedSearchResult $entry, string $name, callable $read): void {
		try {
			$value = $read();
			if ($value === null) {
				$entry->setMetadataError($name, 'not reported by the storage');
			} else {
				$entry->setMetadata($name, $value);
			}
		} catch (\Throwable $e) {
			$entry->setMetadataError($name, $e->getMessage());
		}
	}

	/**
	 * Organizer, attendees and location of an object.
	 *
	 * @return array<string, mixed>
	 */
	private function attributesOf(Component $component): array {
		$attributes = [];

		// "CN <address>", or whichever of the two is set.
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
	 * The object as a standalone iCalendar document.
	 *
	 * `export()` has no uid filter, so this scans the whole calendar: never call it per search result.
	 */
	private function exportObject(ICalendar $calendar, string $uid): ?VCalendar {
		if (!$calendar instanceof ICalendarExport) {
			return null;
		}

		try {
			foreach ($calendar->export(null) as $vCalendar) {
				if ($this->uidOf($vCalendar) === $uid) {
					return $vCalendar;
				}
			}
		} catch (\Throwable $e) {
			$this->logger->warning('Calendar export failed', ['exception' => $e, 'uid' => $uid]);
		}

		return null;
	}

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
	 * Every object in the calendar, within the query's time range if it has one.
	 *
	 * @param array<string, mixed> $options
	 * @return list<array<string, mixed>>
	 */
	private function candidates(ICalendar $calendar, array $options): array {
		try {
			$page = $calendar->search('', [], $options, null);
		} catch (\Throwable $e) {
			$this->logger->warning('Calendar search failed', ['exception' => $e, 'calendar' => $calendar->getUri()]);

			return [];
		}

		$found = [];
		foreach ($page as $object) {
			$found[(string)($object['uid'] ?? '')] = $object;
		}

		// CalDAV has no stable order of its own, and paging needs one.
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
			// Excludes federated calendars, which cannot be exported.
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
	 * CalDAV returns `[value, parameters]` for a single property and a list of those for a
	 * repeated one.
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
