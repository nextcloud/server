<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Search;

use DateTimeImmutable;
use OCA\DAV\CalDAV\CalDavBackend;
use OCP\IUser;
use OCP\Search\IFilteringProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;
use Sabre\VObject\Component;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\InvalidDataException;
use Sabre\VObject\Property;
use Sabre\VObject\Property\ICalendar\DateTime;
use Sabre\VObject\Reader;
use function array_push;
use function array_values;

/**
 * Class EventsSearchProvider
 *
 * @package OCA\DAV\Search
 */
class EventsSearchProvider extends ACalendarSearchProvider implements IFilteringProvider {
	/**
	 * @var string[]
	 */
	private const SEARCH_PROPERTIES = [
		'SUMMARY',
		'LOCATION',
		'DESCRIPTION',
		'ATTENDEE',
		'ORGANIZER',
		'CATEGORIES',
	];

	/**
	 * @var array<string, string[]>
	 */
	private const SEARCH_PARAMETERS = [
		'ATTENDEE' => ['CN'],
		'ORGANIZER' => ['CN'],
	];

	/**
	 * @var string
	 */
	private const COMPONENT_TYPE = 'VEVENT';

	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function getId(): string {
		return 'calendar';
	}

	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function getName(): string {
		return $this->l10n->t('Events');
	}

	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function getOrder(string $route, array $routeParameters): ?int {
		if ($this->appManager->isEnabledForUser('calendar')) {
			return $route === 'calendar.View.index' ? -1 : 30;
		}

		return null;
	}

	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function search(
		IUser $user,
		ISearchQuery $query,
	): SearchResult {
		if (!$this->appManager->isEnabledForUser('calendar', $user)) {
			return SearchResult::complete($this->getName(), []);
		}

		$principalUri = 'principals/users/' . $user->getUID();
		$calendarsById = $this->getSortedCalendars($principalUri);
		$subscriptionsById = $this->getSortedSubscriptions($principalUri);

		/** @var string|null $term */
		$term = $query->getFilter('term')?->get();

		$since = $query->getFilter('since')?->get();
		$until = $query->getFilter('until')?->get();

		if ($since !== null && $until === null) {
			$until = new DateTimeImmutable('now', new \DateTimeZone('Z'));
		}

		/** @var array{start: DateTimeImmutable|null, end: DateTimeImmutable|null} $timeRange */
		$timeRange = [
			'start' => $since,
			'end' => $until,
		];

		if ($term === null) {
			$searchResults = [];
		} else {
			$searchResults = $this->backend->searchPrincipalUri(
				$principalUri,
				$term,
				[self::COMPONENT_TYPE],
				self::SEARCH_PROPERTIES,
				self::SEARCH_PARAMETERS,
				[
					'limit' => $query->getLimit(),
					'offset' => $query->getCursor(),
					'timerange' => $timeRange,
				]
			);
		}
		/** @var IUser|null $person */
		$person = $query->getFilter('person')?->get();
		$personDisplayName = $person?->getDisplayName();
		if ($personDisplayName !== null) {
			array_push($searchResults, ...$this->backend->searchPrincipalUri(
				$principalUri,
				$personDisplayName,
				[self::COMPONENT_TYPE],
				['ATTENDEE'],
				self::SEARCH_PARAMETERS,
				[
					'limit' => $query->getLimit(),
					'offset' => $query->getCursor(),
					'timerange' => $timeRange,
				],
			));
		}

		// Resolve each row to its in-range component (deduplicating events that
		// matched both the term and attendee searches, keyed by calendarid-uri, and
		// dropping anything that does not resolve to a usable in-range component) and
		// format it.
		$formattedResults = [];
		foreach ($searchResults as $searchResult) {
			$key = $searchResult['calendarid'] . '-' . $searchResult['uri'];
			if (isset($formattedResults[$key])) {
				continue;
			}
			$component = $this->resolveComponent($searchResult['calendardata'], $since, $until);
			if ($component === null) {
				continue;
			}

			$title = (string)($component->SUMMARY ?? $this->l10n->t('Untitled event'));
			if ($searchResult['calendartype'] === CalDavBackend::CALENDAR_TYPE_CALENDAR) {
				$calendar = $calendarsById[$searchResult['calendarid']];
			} else {
				$calendar = $subscriptionsById[$searchResult['calendarid']];
			}
			$subline = $this->generateSubline($component, $calendar);
			$resourceUrl = $this->getDeepLinkToCalendarApp($calendar['principaluri'], $calendar['uri'], $searchResult['uri']);
			$result = new SearchResultEntry('', $title, $subline, $resourceUrl, 'icon-calendar-dark', false);

			$dtStart = $component->DTSTART;
			if ($dtStart instanceof DateTime) {
				$result->addAttribute('createdAt', $dtStart->getDateTime()->format('U'));
			}

			$formattedResults[$key] = $result;
		}

		return SearchResult::paginated(
			$this->getName(),
			array_values($formattedResults),
			$query->getCursor() + count($formattedResults)
		);
	}

	/**
	 * Resolve the component to display for a result row.
	 *
	 * Parses the calendar data and, when a time range is requested,
	 * expands it to the in-range occurrence. Returns null to drop the row when the
	 * data is not a calendar or has no occurrence within since and until.
	 */
	private function resolveComponent(string $calendarData, ?\DateTimeInterface $since, ?\DateTimeInterface $until): ?Component {
		$document = Reader::read($calendarData, Reader::OPTION_FORGIVING);
		if (!$document instanceof VCalendar) {
			return null;
		}

		if ($since !== null && $until !== null) {
			$document = $this->expandInRange($document, $since, $until);
			if ($document === null) {
				return null;
			}
		}

		return $this->getPrimaryComponent($document, self::COMPONENT_TYPE);
	}

	/**
	 * Expand a recurring event into its occurrences within the requested
	 * [$since, $until] window, converted back from the UTC that expand() forces
	 * into the event's original timezone.
	 *
	 * Returns null when the event has no occurrence in range (recurrence gap) or
	 * cannot be expanded.
	 */
	private function expandInRange(VCalendar $vCalendar, \DateTimeInterface $since, \DateTimeInterface $until): ?VCalendar {
		// expand() rewrites every occurrence's DTSTART/DTEND to UTC, so remember
		// the event's original timezone to display the occurrence in local time.
		$originalTimeZone = null;
		$baseComponent = $vCalendar->getBaseComponent(self::COMPONENT_TYPE);
		if ($baseComponent !== null && isset($baseComponent->DTSTART) && $baseComponent->DTSTART->hasTime()) {
			$originalTimeZone = $baseComponent->DTSTART->getDateTime()->getTimezone();
		}

		try {
			$expanded = $vCalendar->expand($since, $until);
		} catch (InvalidDataException $e) {
			return null;
		}

		$occurrences = $expanded->select(self::COMPONENT_TYPE);
		if ($occurrences === []) {
			return null;
		}

		if ($originalTimeZone !== null) {
			foreach ($occurrences as $occurrence) {
				$this->applyTimeZone($occurrence, $originalTimeZone);
			}
		}

		return $expanded;
	}

	/**
	 * Move the occurrence back into the event's original timezone after expand()
	 * has rewritten it to UTC, so the rendered time matches the user's local time.
	 */
	private function applyTimeZone(Component $component, \DateTimeZone $timeZone): void {
		foreach (['DTSTART', 'DTEND'] as $name) {
			if (isset($component->$name) && $component->$name->hasTime()) {
				$component->$name->setDateTime(
					$component->$name->getDateTime()->setTimezone($timeZone),
				);
			}
		}
	}

	protected function getDeepLinkToCalendarApp(
		string $principalUri,
		string $calendarUri,
		string $calendarObjectUri,
	): string {
		$davUrl = $this->getDavUrlForCalendarObject($principalUri, $calendarUri, $calendarObjectUri);
		// This route will automatically figure out what recurrence-id to open
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->linkToRoute('calendar.view.index')
			. 'edit/'
			. base64_encode($davUrl)
		);
	}

	protected function getDavUrlForCalendarObject(
		string $principalUri,
		string $calendarUri,
		string $calendarObjectUri,
	): string {
		[,, $principalId] = explode('/', $principalUri, 3);

		return $this->urlGenerator->linkTo('', 'remote.php') . '/dav/calendars/'
			. str_replace(' ', '%20', $principalId) . '/'
			. $calendarUri . '/'
			. $calendarObjectUri;
	}

	protected function generateSubline(Component $eventComponent, array $calendarInfo): string {
		$dtStart = $eventComponent->DTSTART;
		$dtEnd = $this->getDTEndForEvent($eventComponent);
		$isAllDayEvent = $dtStart instanceof Property\ICalendar\Date;
		$startDateTime = new \DateTime($dtStart->getDateTime()->format(\DateTimeInterface::ATOM));
		$endDateTime = new \DateTime($dtEnd->getDateTime()->format(\DateTimeInterface::ATOM));

		if ($isAllDayEvent) {
			$endDateTime->modify('-1 day');
			if ($this->isDayEqual($startDateTime, $endDateTime)) {
				$formattedSubline = $this->l10n->l('date', $startDateTime, ['width' => 'medium']);
			} else {
				$formattedStart = $this->l10n->l('date', $startDateTime, ['width' => 'medium']);
				$formattedEnd = $this->l10n->l('date', $endDateTime, ['width' => 'medium']);
				$formattedSubline = "$formattedStart - $formattedEnd";
			}
		} else {
			$formattedStartDate = $this->l10n->l('date', $startDateTime, ['width' => 'medium']);
			$formattedEndDate = $this->l10n->l('date', $endDateTime, ['width' => 'medium']);
			$formattedStartTime = $this->l10n->l('time', $startDateTime, ['width' => 'short']);
			$formattedEndTime = $this->l10n->l('time', $endDateTime, ['width' => 'short']);

			if ($this->isDayEqual($startDateTime, $endDateTime)) {
				$formattedSubline = "$formattedStartDate $formattedStartTime - $formattedEndTime";
			} else {
				$formattedSubline = "$formattedStartDate $formattedStartTime - $formattedEndDate $formattedEndTime";
			}
		}

		if (isset($calendarInfo['{DAV:}displayname']) && !empty($calendarInfo['{DAV:}displayname'])) {
			$formattedSubline = $formattedSubline . " ({$calendarInfo['{DAV:}displayname']})";
		}

		// string cast is just to make psalm happy
		return (string)$formattedSubline;
	}

	protected function getDTEndForEvent(Component $eventComponent):Property {
		if (isset($eventComponent->DTEND)) {
			$end = $eventComponent->DTEND;
		} elseif (isset($eventComponent->DURATION)) {
			$isFloating = $eventComponent->DTSTART->isFloating();
			$end = clone $eventComponent->DTSTART;
			$endDateTime = $end->getDateTime();
			$endDateTime = $endDateTime->add(DateTimeParser::parse($eventComponent->DURATION->getValue()));
			$end->setDateTime($endDateTime, $isFloating);
		} elseif (!$eventComponent->DTSTART->hasTime()) {
			$isFloating = $eventComponent->DTSTART->isFloating();
			$end = clone $eventComponent->DTSTART;
			$endDateTime = $end->getDateTime();
			$endDateTime = $endDateTime->modify('+1 day');
			$end->setDateTime($endDateTime, $isFloating);
		} else {
			$end = clone $eventComponent->DTSTART;
		}

		return $end;
	}

	protected function isDayEqual(
		\DateTime $dtStart,
		\DateTime $dtEnd,
	): bool {
		return $dtStart->format('Y-m-d') === $dtEnd->format('Y-m-d');
	}

	#[\Override]
	public function getSupportedFilters(): array {
		return [
			'term',
			'person',
			'since',
			'until',
		];
	}

	#[\Override]
	public function getAlternateIds(): array {
		return [];
	}

	#[\Override]
	public function getCustomFilters(): array {
		return [];
	}
}
