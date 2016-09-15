<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files;

use OCP\IDBConnection;
use OCP\L10N\IFactory;
use OCP\Activity\IExtension;
use OCP\Activity\IManager;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;

class Activity implements IExtension {
	const APP_FILES = 'files';
	const FILTER_FILES = 'files';
	const FILTER_FAVORITES = 'files_favorites';

	const TYPE_SHARE_CREATED = 'file_created';
	const TYPE_SHARE_CHANGED = 'file_changed';
	const TYPE_SHARE_DELETED = 'file_deleted';
	const TYPE_SHARE_RESTORED = 'file_restored';
	const TYPE_FAVORITES = 'files_favorites';

	/** @var IL10N */
	protected $l;

	/** @var IFactory */
	protected $languageFactory;

	/** @var IURLGenerator */
	protected $URLGenerator;

	/** @var \OCP\Activity\IManager */
	protected $activityManager;

	/** @var \OCP\IDBConnection */
	protected $connection;

	/** @var \OCP\IConfig */
	protected $config;

	/** @var \OCA\Files\ActivityHelper */
	protected $helper;

	/**
	 * @param IFactory $languageFactory
	 * @param IURLGenerator $URLGenerator
	 * @param IManager $activityManager
	 * @param ActivityHelper $helper
	 * @param IDBConnection $connection
	 * @param IConfig $config
	 */
	public function __construct(IFactory $languageFactory, IURLGenerator $URLGenerator, IManager $activityManager, ActivityHelper $helper, IDBConnection $connection, IConfig $config) {
		$this->languageFactory = $languageFactory;
		$this->URLGenerator = $URLGenerator;
		$this->l = $this->getL10N();
		$this->activityManager = $activityManager;
		$this->helper = $helper;
		$this->connection = $connection;
		$this->config = $config;
	}

	/**
	 * @param string|null $languageCode
	 * @return IL10N
	 */
	protected function getL10N($languageCode = null) {
		return $this->languageFactory->get(self::APP_FILES, $languageCode);
	}

	/**
	 * The extension can return an array of additional notification types.
	 * If no additional types are to be added false is to be returned
	 *
	 * @param string $languageCode
	 * @return array|false Array "stringID of the type" => "translated string description for the setting"
	 * 				or Array "stringID of the type" => [
	 * 					'desc' => "translated string description for the setting"
	 * 					'methods' => [self::METHOD_*],
	 * 				]
	 */
	public function getNotificationTypes($languageCode) {
		$l = $this->getL10N($languageCode);
		return [
			self::TYPE_SHARE_CREATED => (string) $l->t('A new file or folder has been <strong>created</strong>'),
			self::TYPE_SHARE_CHANGED => (string) $l->t('A file or folder has been <strong>changed</strong> or <strong>renamed</strong>'),
			self::TYPE_FAVORITES => [
				'desc' => (string) $l->t('Limit notifications about creation and changes to your <strong>favorite files</strong> <em>(Stream only)</em>'),
				'methods' => [self::METHOD_STREAM],
			],
			self::TYPE_SHARE_DELETED => (string) $l->t('A file or folder has been <strong>deleted</strong>'),
			self::TYPE_SHARE_RESTORED => (string) $l->t('A file or folder has been <strong>restored</strong>'),
		];
	}

	/**
	 * For a given method additional types to be displayed in the settings can be returned.
	 * In case no additional types are to be added false is to be returned.
	 *
	 * @param string $method
	 * @return array|false
	 */
	public function getDefaultTypes($method) {
		if ($method === self::METHOD_STREAM) {
			$settings = array();
			$settings[] = self::TYPE_SHARE_CREATED;
			$settings[] = self::TYPE_SHARE_CHANGED;
			$settings[] = self::TYPE_SHARE_DELETED;
			$settings[] = self::TYPE_SHARE_RESTORED;
			return $settings;
		}

		return false;
	}

	/**
	 * The extension can translate a given message to the requested languages.
	 * If no translation is available false is to be returned.
	 *
	 * @param string $app
	 * @param string $text
	 * @param array $params
	 * @param boolean $stripPath
	 * @param boolean $highlightParams
	 * @param string $languageCode
	 * @return string|false
	 */
	public function translate($app, $text, $params, $stripPath, $highlightParams, $languageCode) {
		if ($app !== self::APP_FILES) {
			return false;
		}

		$l = $this->getL10N($languageCode);

		if ($this->activityManager->isFormattingFilteredObject()) {
			$translation = $this->translateShort($text, $l, $params);
			if ($translation !== false) {
				return $translation;
			}
		}

		return $this->translateLong($text, $l, $params);
	}

	/**
	 * @param string $text
	 * @param IL10N $l
	 * @param array $params
	 * @return string|false
	 */
	protected function translateLong($text, IL10N $l, array $params) {
		switch ($text) {
			case 'created_self':
				return (string) $l->t('You created %1$s', $params);
			case 'created_by':
				return (string) $l->t('%2$s created %1$s', $params);
			case 'created_public':
				return (string) $l->t('%1$s was created in a public folder', $params);
			case 'changed_self':
				return (string) $l->t('You changed %1$s', $params);
			case 'changed_by':
				return (string) $l->t('%2$s changed %1$s', $params);
			case 'deleted_self':
				return (string) $l->t('You deleted %1$s', $params);
			case 'deleted_by':
				return (string) $l->t('%2$s deleted %1$s', $params);
			case 'restored_self':
				return (string) $l->t('You restored %1$s', $params);
			case 'restored_by':
				return (string) $l->t('%2$s restored %1$s', $params);
			case 'renamed_self':
				return (string) $l->t('You renamed %2$s to %1$s', $params);
			case 'renamed_by':
				return (string) $l->t('%2$s renamed %3$s to %1$s', $params);
			case 'moved_self':
				return (string) $l->t('You moved %2$s to %1$s', $params);
			case 'moved_by':
				return (string) $l->t('%2$s moved %3$s to %1$s', $params);

			default:
				return false;
		}
	}

	/**
	 * @param string $text
	 * @param IL10N $l
	 * @param array $params
	 * @return string|false
	 */
	protected function translateShort($text, IL10N $l, array $params) {
		switch ($text) {
			case 'changed_by':
				return (string) $l->t('Changed by %2$s', $params);
			case 'deleted_by':
				return (string) $l->t('Deleted by %2$s', $params);
			case 'restored_by':
				return (string) $l->t('Restored by %2$s', $params);
			case 'renamed_by':
				return (string) $l->t('Renamed by %2$s', $params);
			case 'moved_by':
				return (string) $l->t('Moved by %2$s', $params);

			default:
				return false;
		}
	}

	/**
	 * The extension can define the type of parameters for translation
	 *
	 * Currently known types are:
	 * * file		=> will strip away the path of the file and add a tooltip with it
	 * * username	=> will add the avatar of the user
	 *
	 * @param string $app
	 * @param string $text
	 * @return array|false
	 */
	function getSpecialParameterList($app, $text) {
		if ($app === self::APP_FILES) {
			switch ($text) {
				case 'created_self':
				case 'created_by':
				case 'created_public':
				case 'changed_self':
				case 'changed_by':
				case 'deleted_self':
				case 'deleted_by':
				case 'restored_self':
				case 'restored_by':
					return [
						0 => 'file',
						1 => 'username',
					];
				case 'renamed_self':
				case 'moved_self':
					return [
						0 => 'file',
						1 => 'file',
					];
				case 'renamed_by':
				case 'moved_by':
					return [
						0 => 'file',
						1 => 'username',
						2 => 'file',
					];
			}
		}

		return false;
	}

	/**
	 * A string naming the css class for the icon to be used can be returned.
	 * If no icon is known for the given type false is to be returned.
	 *
	 * @param string $type
	 * @return string|false
	 */
	public function getTypeIcon($type) {
		switch ($type) {
			case self::TYPE_SHARE_CHANGED:
				return 'icon-change';
			case self::TYPE_SHARE_CREATED:
				return 'icon-add-color';
			case self::TYPE_SHARE_DELETED:
				return 'icon-delete-color';

			default:
				return false;
		}
	}

	/**
	 * The extension can define the parameter grouping by returning the index as integer.
	 * In case no grouping is required false is to be returned.
	 *
	 * @param array $activity
	 * @return integer|false
	 */
	public function getGroupParameter($activity) {
		if ($activity['app'] === self::APP_FILES) {
			switch ($activity['subject']) {
				case 'created_self':
				case 'created_by':
				case 'changed_self':
				case 'changed_by':
				case 'deleted_self':
				case 'deleted_by':
				case 'restored_self':
				case 'restored_by':
					return 0;
			}
		}

		return false;
	}

	/**
	 * The extension can define additional navigation entries. The array returned has to contain two keys 'top'
	 * and 'apps' which hold arrays with the relevant entries.
	 * If no further entries are to be added false is no be returned.
	 *
	 * @return array|false
	 */
	public function getNavigation() {
		return [
			'top' => [
				self::FILTER_FAVORITES => [
					'id' => self::FILTER_FAVORITES,
					'name' => (string) $this->l->t('Favorites'),
					'url' => $this->URLGenerator->linkToRoute('activity.Activities.showList', ['filter' => self::FILTER_FAVORITES]),
				],
			],
			'apps' => [
				self::FILTER_FILES => [
					'id' => self::FILTER_FILES,
					'name' => (string) $this->l->t('Files'),
					'url' => $this->URLGenerator->linkToRoute('activity.Activities.showList', ['filter' => self::FILTER_FILES]),
				],
			],
		];
	}

	/**
	 * The extension can check if a customer filter (given by a query string like filter=abc) is valid or not.
	 *
	 * @param string $filterValue
	 * @return boolean
	 */
	public function isFilterValid($filterValue) {
		return $filterValue === self::FILTER_FILES || $filterValue === self::FILTER_FAVORITES;
	}

	/**
	 * The extension can filter the types based on the filter if required.
	 * In case no filter is to be applied false is to be returned unchanged.
	 *
	 * @param array $types
	 * @param string $filter
	 * @return array|false
	 */
	public function filterNotificationTypes($types, $filter) {
		if ($filter === self::FILTER_FILES || $filter === self::FILTER_FAVORITES) {
			return array_intersect([
				self::TYPE_SHARE_CREATED,
				self::TYPE_SHARE_CHANGED,
				self::TYPE_SHARE_DELETED,
				self::TYPE_SHARE_RESTORED,
			], $types);
		}
		return false;
	}

	/**
	 * For a given filter the extension can specify the sql query conditions including parameters for that query.
	 * In case the extension does not know the filter false is to be returned.
	 * The query condition and the parameters are to be returned as array with two elements.
	 * E.g. return array('`app` = ? and `message` like ?', array('mail', 'ownCloud%'));
	 *
	 * @param string $filter
	 * @return array|false
	 */
	public function getQueryForFilter($filter) {
		$user = $this->activityManager->getCurrentUserId();
		// Display actions from all files
		if ($filter === self::FILTER_FILES) {
			return ['`app` = ?', [self::APP_FILES]];
		}

		if (!$user) {
			// Remaining filters only work with a user/token
			return false;
		}

		// Display actions from favorites only
		if ($filter === self::FILTER_FAVORITES || in_array($filter, ['all', 'by', 'self']) && $this->userSettingFavoritesOnly($user)) {
			try {
				$favorites = $this->helper->getFavoriteFilePaths($user);
			} catch (\RuntimeException $e) {
				// Too many favorites, can not put them into one query anymore...
				return ['`app` = ?', [self::APP_FILES]];
			}

			/*
			 * Display activities only, when they are not `type` create/change
			 * or `file` is a favorite or in a favorite folder
			 */
			$parameters = $fileQueryList = [];
			$parameters[] = self::APP_FILES;
			$parameters[] = self::APP_FILES;

			$fileQueryList[] = '(`type` <> ? AND `type` <> ?)';
			$parameters[] = self::TYPE_SHARE_CREATED;
			$parameters[] = self::TYPE_SHARE_CHANGED;

			foreach ($favorites['items'] as $favorite) {
				$fileQueryList[] = '`file` = ?';
				$parameters[] = $favorite;
			}
			foreach ($favorites['folders'] as $favorite) {
				$fileQueryList[] = '`file` LIKE ?';
				$parameters[] = $this->connection->escapeLikeParameter($favorite) . '/%';
			}

			return [
				' CASE '
					. 'WHEN `app` <> ? THEN 1 '
					. 'WHEN `app` = ? AND (' . implode(' OR ', $fileQueryList) . ') THEN 1 '
					. 'ELSE 0 '
				. 'END = 1 ',
				$parameters,
			];
		}
		return false;
	}

	/**
	 * Is the file actions favorite limitation enabled?
	 *
	 * @param string $user
	 * @return bool
	 */
	protected function userSettingFavoritesOnly($user) {
		return (bool) $this->config->getUserValue($user, 'activity', 'notify_' . self::METHOD_STREAM . '_' . self::TYPE_FAVORITES, false);
	}
}
