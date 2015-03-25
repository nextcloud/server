<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

use OC\L10N\Factory;
use OCP\Activity\IExtension;
use OCP\IURLGenerator;

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

	const SUBJECT_SHARED_GROUP_SELF = 'shared_group_self';
	const SUBJECT_SHARED_LINK_SELF = 'shared_link_self';
	const SUBJECT_SHARED_USER_SELF = 'shared_user_self';
	const SUBJECT_SHARED_WITH_BY = 'shared_with_by';

	/** @var Factory */
	protected $languageFactory;

	/** @var IURLGenerator */
	protected $URLGenerator;

	/**
	 * @param Factory $languageFactory
	 * @param IURLGenerator $URLGenerator
	 */
	public function __construct(Factory $languageFactory, IURLGenerator $URLGenerator) {
		$this->languageFactory = $languageFactory;
		$this->URLGenerator = $URLGenerator;
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

		if ($method === 'stream') {
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
		$l = $this->getL10N($languageCode);

		if ($app === self::FILES_SHARING_APP) {
			switch ($text) {
				case self::SUBJECT_REMOTE_SHARE_RECEIVED:
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
				case self::SUBJECT_SHARED_GROUP_SELF:
					return (string) $l->t('You shared %1$s with group %2$s', $params);
				case self::SUBJECT_SHARED_WITH_BY:
					return (string) $l->t('%2$s shared %1$s with you', $params);
				case self::SUBJECT_SHARED_LINK_SELF:
					return (string) $l->t('You shared %1$s via link', $params);
			}
		}

		return false;
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
					return array(
						0 => '',// We can not use 'username' since the user is in a different ownCloud
					);
				case self::SUBJECT_REMOTE_SHARE_ACCEPTED:
				case self::SUBJECT_REMOTE_SHARE_DECLINED:
				case self::SUBJECT_REMOTE_SHARE_UNSHARED:
					return array(
						0 => '',// We can not use 'username' since the user is in a different ownCloud
						1 => 'file',
					);
				case self::SUBJECT_PUBLIC_SHARED_FOLDER_DOWNLOADED:
				case self::SUBJECT_PUBLIC_SHARED_FILE_DOWNLOADED:
					return array(
						0 => 'file',
					);
				case self::SUBJECT_SHARED_LINK_SELF:
					return [0 => 'file'];
				case self::SUBJECT_SHARED_USER_SELF:
				case self::SUBJECT_SHARED_WITH_BY:
					return [0 => 'file', 1 => 'username'];

				case self::SUBJECT_SHARED_GROUP_SELF:
					return [
						0 => 'file',
						//1 => 'group', Group does not exist yet
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
		if ($activity['app'] === 'files') {
			switch ($activity['subject']) {
				case self::SUBJECT_SHARED_LINK_SELF:
				case self::SUBJECT_SHARED_WITH_BY:
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
