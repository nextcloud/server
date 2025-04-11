<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Search;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\IUser;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;
use Sabre\VObject\Component;

/**
 * Class TasksSearchProvider
 *
 * @package OCA\DAV\Search
 */
class TasksSearchProvider extends ACalendarSearchProvider {
	/**
	 * @var string[]
	 */
	private static $searchProperties = [
		'SUMMARY',
		'DESCRIPTION',
		'CATEGORIES',
	];

	/**
	 * @var string[]
	 */
	private static $searchParameters = [];

	/**
	 * @var string
	 */
	private static $componentType = 'VTODO';

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'tasks';
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->l10n->t('Tasks');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(string $route, array $routeParameters): ?int {
		if ($this->appManager->isEnabledForUser('tasks')) {
			return $route === 'tasks.Page.index' ? -1 : 35;
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
		if (!$this->appManager->isEnabledForUser('tasks', $user)) {
			return SearchResult::complete($this->getName(), []);
		}

		$principalUri = 'principals/users/' . $user->getUID();
		$calendarsById = $this->getSortedCalendars($principalUri);
		$subscriptionsById = $this->getSortedSubscriptions($principalUri);

		$searchResults = $this->backend->searchPrincipalUri(
			$principalUri,
			$query->getFilter('term')?->get() ?? '',
			[self::$componentType],
			self::$searchProperties,
			self::$searchParameters,
			[
				'limit' => $query->getLimit(),
				'offset' => $query->getCursor(),
				'since' => $query->getFilter('since'),
				'until' => $query->getFilter('until'),
			]
		);
		$formattedResults = \array_map(function (array $taskRow) use ($calendarsById, $subscriptionsById):SearchResultEntry {
			$component = $this->getPrimaryComponent($taskRow['calendardata'], self::$componentType);
			$title = (string)($component->SUMMARY ?? $this->l10n->t('Untitled task'));
			$subline = $this->generateSubline($component);

			if ($taskRow['calendartype'] === CalDavBackend::CALENDAR_TYPE_CALENDAR) {
				$calendar = $calendarsById[$taskRow['calendarid']];
			} else {
				$calendar = $subscriptionsById[$taskRow['calendarid']];
			}
			$resourceUrl = $this->getDeepLinkToTasksApp($calendar['uri'], $taskRow['uri']);

			return new SearchResultEntry('', $title, $subline, $resourceUrl, 'icon-checkmark', false);
		}, $searchResults);

		return SearchResult::paginated(
			$this->getName(),
			$formattedResults,
			$query->getCursor() + count($formattedResults)
		);
	}

	protected function getDeepLinkToTasksApp(
		string $calendarUri,
		string $taskUri,
	): string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->linkToRoute('tasks.page.index')
			. 'calendars/'
			. $calendarUri
			. '/tasks/'
			. $taskUri
		);
	}

	protected function generateSubline(Component $taskComponent): string {
		if ($taskComponent->COMPLETED) {
			$completedDateTime = new \DateTime($taskComponent->COMPLETED->getDateTime()->format(\DateTimeInterface::ATOM));
			$formattedDate = $this->l10n->l('date', $completedDateTime, ['width' => 'medium']);
			return $this->l10n->t('Completed on %s', [$formattedDate]);
		}

		if ($taskComponent->DUE) {
			$dueDateTime = new \DateTime($taskComponent->DUE->getDateTime()->format(\DateTimeInterface::ATOM));
			$formattedDate = $this->l10n->l('date', $dueDateTime, ['width' => 'medium']);

			if ($taskComponent->DUE->hasTime()) {
				$formattedTime = $this->l10n->l('time', $dueDateTime, ['width' => 'short']);
				return $this->l10n->t('Due on %s by %s', [$formattedDate, $formattedTime]);
			}

			return $this->l10n->t('Due on %s', [$formattedDate]);
		}

		return '';
	}
}
