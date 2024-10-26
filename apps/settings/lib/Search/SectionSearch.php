<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Search;

use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;
use OCP\Settings\IIconSection;
use OCP\Settings\IManager;

class SectionSearch implements IProvider {

	public function __construct(
		protected IManager $settingsManager,
		protected IGroupManager $groupManager,
		protected IURLGenerator $urlGenerator,
		protected IL10N $l,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'settings';
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->l->t('Settings');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(string $route, array $routeParameters): int {
		if ($route === 'settings.PersonalSettings.index' || $route === 'settings.AdminSettings.index') {
			return -1;
		}
		// At the very bottom
		return 500;
	}

	/**
	 * @inheritDoc
	 */
	public function search(IUser $user, ISearchQuery $query): SearchResult {
		$isAdmin = $this->groupManager->isAdmin($user->getUID());

		$personalSections = $this->settingsManager->getPersonalSections();
		foreach ($personalSections as $priority => $sections) {
			$personalSections[$priority] = array_values(array_filter(
				$sections,
				fn (IIconSection $section) => !empty($this->settingsManager->getPersonalSettings($section->getID())),
			));
		}

		$adminSections = $this->settingsManager->getAdminSections();
		foreach ($adminSections as $priority => $sections) {
			$adminSections[$priority] = array_values(array_filter(
				$sections,
				fn (IIconSection $section) => !empty($this->settingsManager->getAllowedAdminSettings($section->getID(), $user)),
			));
		}

		$result = $this->searchSections(
			$query,
			$personalSections,
			$isAdmin ? $this->l->t('Personal') : '',
			'settings.PersonalSettings.index'
		);

		if ($this->groupManager->isAdmin($user->getUID())) {
			$result = array_merge($result, $this->searchSections(
				$query,
				$adminSections,
				$this->l->t('Administration'),
				'settings.AdminSettings.index'
			));
		}

		return SearchResult::complete(
			$this->l->t('Settings'),
			$result
		);
	}

	/**
	 * @param ISearchQuery $query
	 * @param IIconSection[][] $sections
	 * @param string $subline
	 * @param string $routeName
	 * @return array
	 */
	public function searchSections(ISearchQuery $query, array $sections, string $subline, string $routeName): array {
		$result = [];
		foreach ($sections as $priority => $sectionsByPriority) {
			foreach ($sectionsByPriority as $section) {
				if (
					stripos($section->getName(), $query->getTerm()) === false &&
					stripos($section->getID(), $query->getTerm()) === false
				) {
					continue;
				}

				/**
				 * We can't use the icon URL at the moment as they don't invert correctly for dark theme
				 * $iconUrl = $section->getIcon();
				 */

				$result[] = new SearchResultEntry(
					'',
					$section->getName(),
					$subline,
					$this->urlGenerator->linkToRouteAbsolute($routeName, ['section' => $section->getID()]),
					'icon-settings-dark'
				);
			}
		}

		return $result;
	}
}
