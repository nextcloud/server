<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OCA\Files_Sharing;

use OCP\Activity\IExtension;
use OCP\Activity\IManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;

class Activity implements IExtension {
	const FILES_SHARING_APP = 'files_sharing';
	/**
	 * Filter with all sharing related activities
	 */
	const FILTER_SHARES = 'shares';

	/**
	 * Activity types known to this extension
	 */
	const TYPE_PUBLIC_LINKS = 'public_links';
	const TYPE_REMOTE_SHARE = 'remote_share';
	const TYPE_SHARED = 'shared';

	/**
	 * Subject keys for translation of the subjections
	 */
	const SUBJECT_PUBLIC_SHARED_FILE_DOWNLOADED = 'public_shared_file_downloaded';
	const SUBJECT_PUBLIC_SHARED_FOLDER_DOWNLOADED = 'public_shared_folder_downloaded';

	const SUBJECT_REMOTE_SHARE_ACCEPTED = 'remote_share_accepted';
	const SUBJECT_REMOTE_SHARE_DECLINED = 'remote_share_declined';
	const SUBJECT_REMOTE_SHARE_RECEIVED = 'remote_share_received';
	const SUBJECT_REMOTE_SHARE_UNSHARED = 'remote_share_unshared';

	const SUBJECT_SHARED_USER_SELF = 'shared_user_self';
	const SUBJECT_RESHARED_USER_BY = 'reshared_user_by';
	const SUBJECT_UNSHARED_USER_SELF = 'unshared_user_self';
	const SUBJECT_UNSHARED_USER_BY = 'unshared_user_by';

	const SUBJECT_SHARED_GROUP_SELF = 'shared_group_self';
	const SUBJECT_RESHARED_GROUP_BY = 'reshared_group_by';
	const SUBJECT_UNSHARED_GROUP_SELF = 'unshared_group_self';
	const SUBJECT_UNSHARED_GROUP_BY = 'unshared_group_by';

	const SUBJECT_SHARED_LINK_SELF = 'shared_link_self';
	const SUBJECT_RESHARED_LINK_BY = 'reshared_link_by';
	const SUBJECT_UNSHARED_LINK_SELF = 'unshared_link_self';
	const SUBJECT_UNSHARED_LINK_BY = 'unshared_link_by';
	const SUBJECT_LINK_EXPIRED = 'link_expired';
	const SUBJECT_LINK_BY_EXPIRED = 'link_by_expired';

	const SUBJECT_SHARED_EMAIL = 'shared_with_email';
	const SUBJECT_SHARED_WITH_BY = 'shared_with_by';
	const SUBJECT_UNSHARED_BY = 'unshared_by';

	/** @var IFactory */
	protected $languageFactory;

	/** @var IURLGenerator */
	protected $URLGenerator;

	/** @var IManager */
	protected $activityManager;

	/**
	 * @param IFactory $languageFactory
	 * @param IURLGenerator $URLGenerator
	 * @param IManager $activityManager
	 */
	public function __construct(IFactory $languageFactory, IURLGenerator $URLGenerator, IManager $activityManager) {
		$this->languageFactory = $languageFactory;
		$this->URLGenerator = $URLGenerator;
		$this->activityManager = $activityManager;
	}

	protected function getL10N($languageCode = null) {
		return $this->languageFactory->get(self::FILES_SHARING_APP, $languageCode);
	}

	/**
	 * The extension can return an array of additional notification types.
	 * If no additional types are to be added false is to be returned
	 *
	 * @param string $languageCode
	 * @return array|false
	 */
	public function getNotificationTypes($languageCode) {
		$l = $this->getL10N($languageCode);

		return array(
			self::TYPE_SHARED => (string) $l->t('A file or folder has been <strong>shared</strong>'),
			self::TYPE_REMOTE_SHARE => (string) $l->t('A file or folder was shared from <strong>another server</strong>'),
			self::TYPE_PUBLIC_LINKS => (string) $l->t('A public shared file or folder was <strong>downloaded</strong>'),
		);
	}

	/**
	 * For a given method additional types to be displayed in the settings can be returned.
	 * In case no additional types are to be added false is to be returned.
	 *
	 * @param string $method
	 * @return array|false
	 */
	public function getDefaultTypes($method) {
		$defaultTypes = [
			self::TYPE_SHARED,
			self::TYPE_REMOTE_SHARE,
		];

		if ($method === self::METHOD_STREAM) {
			$defaultTypes[] = self::TYPE_PUBLIC_LINKS;
		}

		return $defaultTypes;
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
			case self::TYPE_SHARED:
			case self::TYPE_REMOTE_SHARE:
				return 'icon-share';
			case self::TYPE_PUBLIC_LINKS:
				return 'icon-download';
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
		if ($app !== self::FILES_SHARING_APP) {
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
	 * @return bool|string
	 */
	protected function translateLong($text, IL10N $l, array $params) {

		switch ($text) {
			case self::SUBJECT_REMOTE_SHARE_RECEIVED:
				if (sizeof($params) === 2) {
					// New activity ownCloud 8.2+
					return (string) $l->t('You received a new remote share %2$s from %1$s', $params);
				}
				return (string) $l->t('You received a new remote share from %s', $params);
			case self::SUBJECT_REMOTE_SHARE_ACCEPTED:
				return (string) $l->t('%1$s accepted remote share %2$s', $params);
			case self::SUBJECT_REMOTE_SHARE_DECLINED:
				return (string) $l->t('%1$s declined remote share %2$s', $params);
			case self::SUBJECT_REMOTE_SHARE_UNSHARED:
				return (string) $l->t('%1$s unshared %2$s from you', $params);
			case self::SUBJECT_PUBLIC_SHARED_FOLDER_DOWNLOADED:
				return (string) $l->t('Public shared folder %1$s was downloaded', $params);
			case self::SUBJECT_PUBLIC_SHARED_FILE_DOWNLOADED:
				return (string) $l->t('Public shared file %1$s was downloaded', $params);

			case self::SUBJECT_SHARED_USER_SELF:
				return (string) $l->t('You shared %1$s with %2$s', $params);
			case self::SUBJECT_RESHARED_USER_BY:
				return (string) $l->t('%2$s shared %1$s with %3$s', $params);
			case self::SUBJECT_UNSHARED_USER_SELF:
				return (string) $l->t('You removed the share of %2$s for %1$s', $params);
			case self::SUBJECT_UNSHARED_USER_BY:
				return (string) $l->t('%2$s removed the share of %3$s for %1$s', $params);

			case self::SUBJECT_SHARED_GROUP_SELF:
				return (string) $l->t('You shared %1$s with group %2$s', $params);
			case self::SUBJECT_RESHARED_GROUP_BY:
				return (string) $l->t('%2$s shared %1$s with group %3$s', $params);
			case self::SUBJECT_UNSHARED_GROUP_SELF:
				return (string) $l->t('You removed the share of group %2$s for %1$s', $params);
			case self::SUBJECT_UNSHARED_GROUP_BY:
				return (string) $l->t('%2$s removed the share of group %3$s for %1$s', $params);

			case self::SUBJECT_RESHARED_LINK_BY:
				return (string) $l->t('%2$s shared %1$s via link', $params);
			case self::SUBJECT_SHARED_LINK_SELF:
				return (string) $l->t('You shared %1$s via link', $params);
			case self::SUBJECT_UNSHARED_LINK_SELF:
				return (string) $l->t('You removed the public link for %1$s', $params);
			case self::SUBJECT_UNSHARED_LINK_BY:
				return (string) $l->t('%2$s removed the public link for %1$s', $params);
			case self::SUBJECT_LINK_EXPIRED:
				return (string) $l->t('Your public link for %1$s expired', $params);
			case self::SUBJECT_LINK_BY_EXPIRED:
				return (string) $l->t('The public link of %2$s for %1$s expired', $params);

			case self::SUBJECT_SHARED_WITH_BY:
				return (string) $l->t('%2$s shared %1$s with you', $params);
			case self::SUBJECT_UNSHARED_BY:
				return (string) $l->t('%2$s removed the share for %1$s', $params);
			case self::SUBJECT_SHARED_EMAIL:
				return (string) $l->t('You shared %1$s with %2$s', $params);
		}

		return false;
	}

	/**
	 * @param string $text
	 * @param IL10N $l
	 * @param array $params
	 * @return bool|string
	 */
	protected function translateShort($text, IL10N $l, array $params) {
		switch ($text) {
			case self::SUBJECT_PUBLIC_SHARED_FOLDER_DOWNLOADED:
			case self::SUBJECT_PUBLIC_SHARED_FILE_DOWNLOADED:
				return (string) $l->t('Downloaded via public link');

			case self::SUBJECT_SHARED_USER_SELF:
				return (string) $l->t('Shared with %2$s', $params);
			case self::SUBJECT_RESHARED_USER_BY:
				return (string) $l->t('Shared with %3$s by %2$s', $params);
			case self::SUBJECT_UNSHARED_USER_SELF:
				return (string) $l->t('Removed share for %2$s', $params);
			case self::SUBJECT_UNSHARED_USER_BY:
				return (string) $l->t('%2$s removed share for %3$s', $params);

			case self::SUBJECT_SHARED_GROUP_SELF:
				return (string) $l->t('Shared with group %2$s', $params);
			case self::SUBJECT_RESHARED_GROUP_BY:
				return (string) $l->t('Shared with group %3$s by %2$s', $params);
			case self::SUBJECT_UNSHARED_GROUP_SELF:
				return (string) $l->t('Removed share of group %2$s', $params);
			case self::SUBJECT_UNSHARED_GROUP_BY:
				return (string) $l->t('%2$s removed share of group %3$s', $params);

			case self::SUBJECT_RESHARED_LINK_BY:
				return (string) $l->t('Shared via link by %2$s', $params);
			case self::SUBJECT_SHARED_LINK_SELF:
				return (string) $l->t('Shared via public link');
			case self::SUBJECT_UNSHARED_LINK_SELF:
				return (string) $l->t('Removed public link');
			case self::SUBJECT_UNSHARED_LINK_BY:
				return (string) $l->t('%2$s removed public link');
			case self::SUBJECT_LINK_EXPIRED:
				return (string) $l->t('Public link expired', $params);
			case self::SUBJECT_LINK_BY_EXPIRED:
				return (string) $l->t('Public link of %2$s expired', $params);

			case self::SUBJECT_SHARED_WITH_BY:
				return (string) $l->t('Shared by %2$s', $params);
			case self::SUBJECT_SHARED_EMAIL:
				return (string) $l->t('Shared with %2$s', $params);

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
	public function getSpecialParameterList($app, $text) {
		if ($app === self::FILES_SHARING_APP) {
			switch ($text) {
				case self::SUBJECT_REMOTE_SHARE_RECEIVED:
				case self::SUBJECT_REMOTE_SHARE_UNSHARED:
					return array(
						0 => 'federated_cloud_id',
						//1 => 'file', in theory its a file, but it does not exist yet/anymore
					);
				case self::SUBJECT_REMOTE_SHARE_ACCEPTED:
				case self::SUBJECT_REMOTE_SHARE_DECLINED:
					return array(
						0 => 'federated_cloud_id',
						1 => 'file',
					);
				case self::SUBJECT_PUBLIC_SHARED_FOLDER_DOWNLOADED:
				case self::SUBJECT_PUBLIC_SHARED_FILE_DOWNLOADED:
					return array(
						0 => 'file',
					);
				case self::SUBJECT_SHARED_LINK_SELF:
				case self::SUBJECT_UNSHARED_LINK_SELF:
				case self::SUBJECT_LINK_EXPIRED:
					return [0 => 'file'];
				case self::SUBJECT_RESHARED_LINK_BY:
					return [
						0 => 'file',
						1 => 'username',
						2 => '',
					];
				case self::SUBJECT_SHARED_EMAIL:
					return array(
						0 => 'file',
						1 => '',// 'email' is neither supported nor planned for now
					);

				case self::SUBJECT_SHARED_USER_SELF:
				case self::SUBJECT_SHARED_WITH_BY:
				case self::SUBJECT_UNSHARED_BY:
				case self::SUBJECT_UNSHARED_LINK_BY:
				case self::SUBJECT_LINK_BY_EXPIRED:
				case self::SUBJECT_UNSHARED_USER_SELF:
					return [0 => 'file', 1 => 'username'];
				case self::SUBJECT_RESHARED_USER_BY:
				case self::SUBJECT_UNSHARED_USER_BY:
					return [
						0 => 'file',
						1 => 'username',
						2 => 'username',
					];

				case self::SUBJECT_SHARED_GROUP_SELF:
				case self::SUBJECT_UNSHARED_GROUP_SELF:
					return [
						0 => 'file',
						1 => 'group',
					];

				case self::SUBJECT_RESHARED_GROUP_BY:
				case self::SUBJECT_UNSHARED_GROUP_BY:
					return [
						0 => 'file',
						1 => 'username',
						2 => 'group',
					];
			}
		}

		return false;
	}

	/**
	 * The extension can define the parameter grouping by returning the index as integer.
	 * In case no grouping is required false is to be returned.
	 *
	 * @param array $activity
	 * @return integer|false
	 */
	public function getGroupParameter($activity) {
		if ($activity['app'] === self::FILES_SHARING_APP) {
			switch ($activity['subject']) {
				case self::SUBJECT_SHARED_LINK_SELF:
				case self::SUBJECT_UNSHARED_LINK_SELF:
				case self::SUBJECT_LINK_EXPIRED:
				case self::SUBJECT_SHARED_WITH_BY:
				case self::SUBJECT_UNSHARED_BY:
					// Group by file name
					return 0;
				case self::SUBJECT_SHARED_USER_SELF:
				case self::SUBJECT_SHARED_GROUP_SELF:
					// Group by user/group
					return 1;
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
		$l = $this->getL10N();
		return [
			'apps' => [],
			'top' => [
				self::FILTER_SHARES => [
					'id' => self::FILTER_SHARES,
					'name' => (string) $l->t('Shares'),
					'url' => $this->URLGenerator->linkToRoute('activity.Activities.showList', ['filter' => self::FILTER_SHARES]),
				],
			],
		];
	}

	/**
	 * The extension can check if a custom filter (given by a query string like filter=abc) is valid or not.
	 *
	 * @param string $filterValue
	 * @return boolean
	 */
	public function isFilterValid($filterValue) {
		return $filterValue === self::FILTER_SHARES;
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
		switch ($filter) {
			case self::FILTER_SHARES:
				return array_intersect([self::TYPE_SHARED, self::TYPE_REMOTE_SHARE], $types);
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
		if ($filter === self::FILTER_SHARES) {
			return [
				'`app` = ?',
				[self::FILES_SHARING_APP,],
			];
		}
		return false;
	}

}
