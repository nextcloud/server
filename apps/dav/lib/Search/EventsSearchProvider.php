<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
namespace OCA\DAV\Search;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\IUser;
use OCP\Search\IFilteringProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;
use Sabre\VObject\Component;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\Property;
use function array_combine;
use function array_fill;
use function array_key_exists;
use function array_map;

/**
 * Class EventsSearchProvider
 *
 * @package OCA\DAV\Search
 */
class EventsSearchProvider extends ACalendarSearchProvider implements IFilteringProvider {
	/**
	 * @var string[]
	 */
	private static $searchProperties = [
		'SUMMARY',
		'LOCATION',
		'DESCRIPTION',
		'ATTENDEE',
		'ORGANIZER',
		'CATEGORIES',
	];

	/**
	 * @var string[]
	 */
	private static $searchParameters = [
		'ATTENDEE' => ['CN'],
		'ORGANIZER' => ['CN'],
	];

	/**
	 * @var string
	 */
	private static $componentType = 'VEVENT';

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'calendar';
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->l10n->t('Events');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(string $route, array $routeParameters): ?int {
		if ($this->appManager->isEnabledForUser('calendar')) {
			return $route === 'calendar.View.index' ? -1 : 30;
		}

		return null;
	}

	/**
	 * @inheritDoc
	 */
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
		if ($term === null) {
			$searchResults = [];
		} else {
			$searchResults = $this->backend->searchPrincipalUri(
				$principalUri,
				$term,
				[self::$componentType],
				self::$searchProperties,
				self::$searchParameters,
				[
					'limit' => $query->getLimit(),
					'offset' => $query->getCursor(),
					'timerange' => [
						'start' => $query->getFilter('since')?->get(),
						'end' => $query->getFilter('until')?->get(),
					],
				]
			);
		}
		/** @var IUser|null $person */
		$person = $query->getFilter('person')?->get();
		$personDisplayName = $person?->getDisplayName();
		if ($personDisplayName !== null) {
			$attendeeSearchResults = $this->backend->searchPrincipalUri(
				$principalUri,
				$personDisplayName,
				[self::$componentType],
				['ATTENDEE'],
				self::$searchParameters,
				[
					'limit' => $query->getLimit(),
					'offset' => $query->getCursor(),
					'timerange' => [
						'start' => $query->getFilter('since')?->get(),
						'end' => $query->getFilter('until')?->get(),
					],
				],
			);

			$searchResultIndex = array_combine(
				array_map(fn ($event) => $event['calendarid'] . '-' . $event['uri'], $searchResults),
				array_fill(0, count($searchResults), null),
			);
			foreach ($attendeeSearchResults as $attendeeResult) {
				if (array_key_exists($attendeeResult['calendarid'] . '-' . $attendeeResult['uri'], $searchResultIndex)) {
					// Duplicate
					continue;
				}
				$searchResults[] = $attendeeResult;
			}
		}
		$formattedResults = \array_map(function (array $eventRow) use ($calendarsById, $subscriptionsById): SearchResultEntry {
			$component = $this->getPrimaryComponent($eventRow['calendardata'], self::$componentType);
			$title = (string)($component->SUMMARY ?? $this->l10n->t('Untitled event'));
			$subline = $this->generateSubline($component);

			if ($eventRow['calendartype'] === CalDavBackend::CALENDAR_TYPE_CALENDAR) {
				$calendar = $calendarsById[$eventRow['calendarid']];
			} else {
				$calendar = $subscriptionsById[$eventRow['calendarid']];
			}
			$resourceUrl = $this->getDeepLinkToCalendarApp($calendar['principaluri'], $calendar['uri'], $eventRow['uri']);

			return new SearchResultEntry('', $title, $subline, $resourceUrl, 'icon-calendar-dark', false);
		}, $searchResults);

		return SearchResult::paginated(
			$this->getName(),
			$formattedResults,
			$query->getCursor() + count($formattedResults)
		);
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
		string $calendarObjectUri
	): string {
		[,, $principalId] = explode('/', $principalUri, 3);

		return $this->urlGenerator->linkTo('', 'remote.php') . '/dav/calendars/'
			. $principalId . '/'
			. $calendarUri . '/'
			. $calendarObjectUri;
	}

	protected function generateSubline(Component $eventComponent): string {
		$dtStart = $eventComponent->DTSTART;
		$dtEnd = $this->getDTEndForEvent($eventComponent);
		$isAllDayEvent = $dtStart instanceof Property\ICalendar\Date;
		$startDateTime = new \DateTime($dtStart->getDateTime()->format(\DateTimeInterface::ATOM));
		$endDateTime = new \DateTime($dtEnd->getDateTime()->format(\DateTimeInterface::ATOM));

		if ($isAllDayEvent) {
			$endDateTime->modify('-1 day');
			if ($this->isDayEqual($startDateTime, $endDateTime)) {
				return $this->l10n->l('date', $startDateTime, ['width' => 'medium']);
			}

			$formattedStart = $this->l10n->l('date', $startDateTime, ['width' => 'medium']);
			$formattedEnd = $this->l10n->l('date', $endDateTime, ['width' => 'medium']);
			return "$formattedStart - $formattedEnd";
		}

		$formattedStartDate = $this->l10n->l('date', $startDateTime, ['width' => 'medium']);
		$formattedEndDate = $this->l10n->l('date', $endDateTime, ['width' => 'medium']);
		$formattedStartTime = $this->l10n->l('time', $startDateTime, ['width' => 'short']);
		$formattedEndTime = $this->l10n->l('time', $endDateTime, ['width' => 'short']);

		if ($this->isDayEqual($startDateTime, $endDateTime)) {
			return "$formattedStartDate $formattedStartTime - $formattedEndTime";
		}

		return "$formattedStartDate $formattedStartTime - $formattedEndDate $formattedEndTime";
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

	public function getSupportedFilters(): array {
		return [
			'term',
			'person',
			'since',
			'until',
		];
	}

	public function getAlternateIds(): array {
		return [];
	}

	public function getCustomFilters(): array {
		return [];
	}
}
