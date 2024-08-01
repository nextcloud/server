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

	/** @var IManager */
	protected $settingsManager;

	/** @var IGroupManager */
	protected $groupManager;

	/** @var IURLGenerator */
	protected $urlGenerator;

	/** @var IL10N */
	protected $l;

	public function __construct(IManager $settingsManager,
		IGroupManager $groupManager,
		IURLGenerator $urlGenerator,
		IL10N $l) {
		$this->settingsManager = $settingsManager;
		$this->groupManager = $groupManager;
		$this->urlGenerator = $urlGenerator;
		$this->l = $l;
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

		$result = $this->searchSections(
			$query,
			$this->settingsManager->getPersonalSections(),
			$isAdmin ? $this->l->t('Personal') : '',
			'settings.PersonalSettings.index'
		);

		if ($this->groupManager->isAdmin($user->getUID())) {
			$result = array_merge($result, $this->searchSections(
				$query,
				$this->settingsManager->getAdminSections(),
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
