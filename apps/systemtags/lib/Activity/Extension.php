<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OCA\SystemTags\Activity;

use OCP\Activity\IExtension;
use OCP\Activity\IManager;
use OCP\IL10N;
use OCP\L10N\IFactory;

/**
 * Class Extension
 *
 * @package OCA\SystemTags\Activity
 */
class Extension implements IExtension {
	const APP_NAME = 'systemtags';

	const CREATE_TAG = 'create_tag';
	const UPDATE_TAG = 'update_tag';
	const DELETE_TAG = 'delete_tag';

	const ASSIGN_TAG = 'assign_tag';
	const UNASSIGN_TAG = 'unassign_tag';

	/** @var IFactory */
	protected $languageFactory;

	/** @var IManager */
	protected $activityManager;

	/**
	 * @param IFactory $languageFactory
	 * @param IManager $activityManager
	 */
	public function __construct(IFactory $languageFactory, IManager $activityManager) {
		$this->languageFactory = $languageFactory;
		$this->activityManager = $activityManager;
	}

	protected function getL10N($languageCode = null) {
		return $this->languageFactory->get(self::APP_NAME, $languageCode);
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
			self::APP_NAME => (string) $l->t('<strong>System tags</strong> for a file have been modified'),
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
		return $method === self::METHOD_STREAM ? [self::APP_NAME] : false;
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
			case self::APP_NAME:
				return false;
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
		if ($app !== self::APP_NAME) {
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
	protected function translateShort($text, IL10N $l, array $params) {

		switch ($text) {
			case self::ASSIGN_TAG:
				$params[2] = $this->convertParameterToTag($params[2], $l);
				if ($this->actorIsCurrentUser($params[0])) {
					return (string) $l->t('You assigned system tag %3$s', $params);
				}
				return (string) $l->t('%1$s assigned system tag %3$s', $params);
			case self::UNASSIGN_TAG:
				$params[2] = $this->convertParameterToTag($params[2], $l);
				if ($this->actorIsCurrentUser($params[0])) {
					return (string) $l->t('You unassigned system tag %3$s', $params);
				}
				return (string) $l->t('%1$s unassigned system tag %3$s', $params);
		}

		return false;
	}

	/**
	 * @param string $text
	 * @param IL10N $l
	 * @param array $params
	 * @return bool|string
	 */
	protected function translateLong($text, IL10N $l, array $params) {

		switch ($text) {
			case self::CREATE_TAG:
				$params[1] = $this->convertParameterToTag($params[1], $l);
				if ($this->actorIsCurrentUser($params[0])) {
					return (string) $l->t('You created system tag %2$s', $params);
				}
				return (string) $l->t('%1$s created system tag %2$s', $params);
			case self::DELETE_TAG:
				$params[1] = $this->convertParameterToTag($params[1], $l);
				if ($this->actorIsCurrentUser($params[0])) {
					return (string) $l->t('You deleted system tag %2$s', $params);
				}
				return (string) $l->t('%1$s deleted system tag %2$s', $params);
			case self::UPDATE_TAG:
				$params[1] = $this->convertParameterToTag($params[1], $l);
				$params[2] = $this->convertParameterToTag($params[2], $l);
				if ($this->actorIsCurrentUser($params[0])) {
					return (string) $l->t('You updated system tag %3$s to %2$s', $params);
				}
				return (string) $l->t('%1$s updated system tag %3$s to %2$s', $params);
			case self::ASSIGN_TAG:
				$params[2] = $this->convertParameterToTag($params[2], $l);
				if ($this->actorIsCurrentUser($params[0])) {
					return (string) $l->t('You assigned system tag %3$s to %2$s', $params);
				}
				return (string) $l->t('%1$s assigned system tag %3$s to %2$s', $params);
			case self::UNASSIGN_TAG:
				$params[2] = $this->convertParameterToTag($params[2], $l);
				if ($this->actorIsCurrentUser($params[0])) {
					return (string) $l->t('You unassigned system tag %3$s from %2$s', $params);
				}
				return (string) $l->t('%1$s unassigned system tag %3$s from %2$s', $params);
		}

		return false;
	}

	/**
	 * Check if the author is the current user
	 *
	 * @param string $user Parameter e.g. `<user display-name="admin">admin</user>`
	 * @return bool
	 */
	protected function actorIsCurrentUser($user) {
		try {
			return strip_tags($user) === $this->activityManager->getCurrentUserId();
		} catch (\UnexpectedValueException $e) {
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
		if ($app === self::APP_NAME) {
			switch ($text) {
				case self::CREATE_TAG:
				case self::DELETE_TAG:
					return array(
						0 => 'username',
						//1 => 'systemtag description',
					);
				case self::UPDATE_TAG:
					return array(
						0 => 'username',
						//1 => 'systemtag description',
						//2 => 'systemtag description',
					);

				case self::ASSIGN_TAG:
				case self::UNASSIGN_TAG:
					return array(
						0 => 'username',
						1 => 'file',
						//2 => 'systemtag description',
					);
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
		return false;
	}

	/**
	 * The extension can check if a custom filter (given by a query string like filter=abc) is valid or not.
	 *
	 * @param string $filterValue
	 * @return boolean
	 */
	public function isFilterValid($filterValue) {
		return false;
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
		return false;
	}

	/**
	 * @param string $parameter
	 * @param IL10N $l
	 * @return string
	 */
	protected function convertParameterToTag($parameter, IL10N $l) {
		if (preg_match('/^\<parameter\>\{\{\{(.*)\|\|\|(.*)\}\}\}\<\/parameter\>$/', $parameter, $matches)) {
			switch ($matches[2]) {
				case 'assignable':
					return '<parameter>' . $matches[1] . '</parameter>';
				case 'not-assignable':
					return '<parameter>' . $l->t('%s (restricted)', $matches[1]) . '</parameter>';
				case 'invisible':
					return '<parameter>' . $l->t('%s (invisible)', $matches[1]) . '</parameter>';
			}
		}

		return $parameter;
	}
}
