<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Activity\Filter;

use OCA\Files\Activity\Helper;
use OCP\Activity\IFilter;
use OCP\Activity\IManager;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IURLGenerator;

class Favorites implements IFilter {

	/**
	 * @param IL10N $l
	 * @param IURLGenerator $url
	 * @param IManager $activityManager
	 * @param Helper $helper
	 * @param IDBConnection $db
	 */
	public function __construct(
		protected IL10N $l,
		protected IURLGenerator $url,
		protected IManager $activityManager,
		protected Helper $helper,
		protected IDBConnection $db,
	) {
	}

	/**
	 * @return string Lowercase a-z only identifier
	 * @since 11.0.0
	 */
	public function getIdentifier() {
		return 'files_favorites';
	}

	/**
	 * @return string A translated string
	 * @since 11.0.0
	 */
	public function getName() {
		return $this->l->t('Favorites');
	}

	/**
	 * @return int
	 * @since 11.0.0
	 */
	public function getPriority() {
		return 10;
	}

	/**
	 * @return string Full URL to an icon, empty string when none is given
	 * @since 11.0.0
	 */
	public function getIcon() {
		return $this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/star-dark.svg'));
	}

	/**
	 * @param string[] $types
	 * @return string[] An array of allowed apps from which activities should be displayed
	 * @since 11.0.0
	 */
	public function filterTypes(array $types) {
		return array_intersect([
			'file_created',
			'file_changed',
			'file_deleted',
			'file_restored',
		], $types);
	}

	/**
	 * @return string[] An array of allowed apps from which activities should be displayed
	 * @since 11.0.0
	 */
	public function allowedApps() {
		return ['files'];
	}

	/**
	 * @param IQueryBuilder $query
	 */
	public function filterFavorites(IQueryBuilder $query) {
		try {
			$user = $this->activityManager->getCurrentUserId();
		} catch (\UnexpectedValueException $e) {
			return;
		}

		try {
			$favorites = $this->helper->getFavoriteFilePaths($user);
		} catch (\RuntimeException $e) {
			return;
		}

		$limitations = [];
		if (!empty($favorites['items'])) {
			$limitations[] = $query->expr()->in('file', $query->createNamedParameter($favorites['items'], IQueryBuilder::PARAM_STR_ARRAY));
		}
		foreach ($favorites['folders'] as $favorite) {
			$limitations[] = $query->expr()->like('file', $query->createNamedParameter(
				$this->db->escapeLikeParameter($favorite . '/') . '%'
			));
		}

		if (empty($limitations)) {
			return;
		}

		$function = $query->createFunction('
			CASE 
				WHEN ' . $query->getColumnName('app') . ' <> ' . $query->createNamedParameter('files') . ' THEN 1
				WHEN ' . $query->getColumnName('app') . ' = ' . $query->createNamedParameter('files') . '
					AND (' . implode(' OR ', $limitations) . ')
					THEN 1 
			END = 1'
		);

		$query->andWhere($function);
	}
}
