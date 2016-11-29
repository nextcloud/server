<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\CalDAV\Activity;

use OCP\Activity\IExtension;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;

class Extension implements IExtension {
	const APP = 'dav';
	/**
	 * Filter with all sharing related activities
	 */
	const CALENDAR = 'calendar';
	const CALENDAR_EVENT = 'calendar_event';
	const CALENDAR_TODO = 'calendar_todo';

	const SUBJECT_ADD = 'calendar_add';
	const SUBJECT_UPDATE = 'calendar_update';
	const SUBJECT_DELETE = 'calendar_delete';
	const SUBJECT_SHARE_USER = 'calendar_user_share';
	const SUBJECT_SHARE_GROUP = 'calendar_group_share';
	const SUBJECT_UNSHARE_USER = 'calendar_user_unshare';
	const SUBJECT_UNSHARE_GROUP = 'calendar_group_unshare';

	const SUBJECT_OBJECT_ADD = 'object_add';
	const SUBJECT_OBJECT_UPDATE = 'object_update';
	const SUBJECT_OBJECT_DELETE = 'object_delete';

	/** @var IFactory */
	protected $languageFactory;

	/** @var IURLGenerator */
	protected $URLGenerator;

	/**
	 * @param IFactory $languageFactory
	 * @param IURLGenerator $URLGenerator
	 */
	public function __construct(IFactory $languageFactory, IURLGenerator $URLGenerator) {
		$this->languageFactory = $languageFactory;
		$this->URLGenerator = $URLGenerator;
	}

	protected function getL10N($languageCode = null) {
		return $this->languageFactory->get(self::APP, $languageCode);
	}

	/**
	 * The extension can return an array of additional notification types.
	 * If no additional types are to be added false is to be returned
	 *
	 * @param string $languageCode
	 * @return array|false
	 */
	public function getNotificationTypes($languageCode) {
		return false;
	}

	/**
	 * For a given method additional types to be displayed in the settings can be returned.
	 * In case no additional types are to be added false is to be returned.
	 *
	 * @param string $method
	 * @return array|false
	 */
	public function getDefaultTypes($method) {
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
		if ($app !== self::APP) {
			return false;
		}

		$l = $this->getL10N($languageCode);

		switch ($text) {
			case self::SUBJECT_ADD:
				return (string) $l->t('%1$s created calendar %2$s', $params);
			case self::SUBJECT_ADD . '_self':
				return (string) $l->t('You created calendar %2$s', $params);
			case self::SUBJECT_DELETE:
				return (string) $l->t('%1$s deleted calendar %2$s', $params);
			case self::SUBJECT_DELETE . '_self':
				return (string) $l->t('You deleted calendar %2$s', $params);
			case self::SUBJECT_UPDATE:
				return (string) $l->t('%1$s updated calendar %2$s', $params);
			case self::SUBJECT_UPDATE . '_self':
				return (string) $l->t('You updated calendar %2$s', $params);

			case self::SUBJECT_SHARE_USER:
				return (string) $l->t('%1$s shared calendar %2$s with you', $params);
			case self::SUBJECT_SHARE_USER . '_you':
				return (string) $l->t('You shared calendar %2$s with %1$s', $params);
			case self::SUBJECT_SHARE_USER . '_by':
				return (string) $l->t('%3$s shared calendar %2$s with %1$s', $params);
			case self::SUBJECT_UNSHARE_USER:
				return (string) $l->t('%1$s unshared calendar %2$s from you', $params);
			case self::SUBJECT_UNSHARE_USER . '_you':
				return (string) $l->t('You unshared calendar %2$s from %1$s', $params);
			case self::SUBJECT_UNSHARE_USER . '_by':
				return (string) $l->t('%3$s unshared calendar %2$s from %1$s', $params);
			case self::SUBJECT_UNSHARE_USER . '_self':
				return (string) $l->t('%1$s unshared calendar %2$s from themselves', $params);

			case self::SUBJECT_SHARE_GROUP . '_you':
				return (string) $l->t('You shared calendar %2$s with group %1$s', $params);
			case self::SUBJECT_SHARE_GROUP . '_by':
				return (string) $l->t('%3$s shared calendar %2$s with group %1$s', $params);
			case self::SUBJECT_UNSHARE_GROUP . '_you':
				return (string) $l->t('You unshared calendar %2$s from group %1$s', $params);
			case self::SUBJECT_UNSHARE_GROUP . '_by':
				return (string) $l->t('%3$s unshared calendar %2$s from group %1$s', $params);

			case self::SUBJECT_OBJECT_ADD . '_event':
				return (string) $l->t('%1$s created event %3$s in calendar %2$s', $params);
			case self::SUBJECT_OBJECT_ADD . '_event_self':
				return (string) $l->t('You created event %3$s in calendar %2$s', $params);
			case self::SUBJECT_OBJECT_DELETE . '_event':
				return (string) $l->t('%1$s deleted event %3$s from calendar %2$s', $params);
			case self::SUBJECT_OBJECT_DELETE . '_event_self':
				return (string) $l->t('You deleted event %3$s from calendar %2$s', $params);
			case self::SUBJECT_OBJECT_UPDATE . '_event':
				return (string) $l->t('%1$s updated event %3$s in calendar %2$s', $params);
			case self::SUBJECT_OBJECT_UPDATE . '_event_self':
				return (string) $l->t('You updated event %3$s in calendar %2$s', $params);

			case self::SUBJECT_OBJECT_ADD . '_todo':
				return (string) $l->t('%1$s created todo %3$s in list %2$s', $params);
			case self::SUBJECT_OBJECT_ADD . '_todo_self':
				return (string) $l->t('You created todo %3$s in list %2$s', $params);
			case self::SUBJECT_OBJECT_DELETE . '_todo':
				return (string) $l->t('%1$s deleted todo %3$s from list %2$s', $params);
			case self::SUBJECT_OBJECT_DELETE . '_todo_self':
				return (string) $l->t('You deleted todo %3$s from list %2$s', $params);
			case self::SUBJECT_OBJECT_UPDATE . '_todo':
				return (string) $l->t('%1$s updated todo %3$s in list %2$s', $params);
			case self::SUBJECT_OBJECT_UPDATE . '_todo_self':
				return (string) $l->t('You updated todo %3$s in list %2$s', $params);

			case self::SUBJECT_OBJECT_UPDATE . '_todo_completed':
				return (string) $l->t('%1$s solved todo %3$s in list %2$s', $params);
			case self::SUBJECT_OBJECT_UPDATE . '_todo_completed_self':
				return (string) $l->t('You solved todo %3$s in list %2$s', $params);
			case self::SUBJECT_OBJECT_UPDATE . '_todo_needs_action':
				return (string) $l->t('%1$s reopened todo %3$s in list %2$s', $params);
			case self::SUBJECT_OBJECT_UPDATE . '_todo_needs_action_self':
				return (string) $l->t('You reopened todo %3$s in list %2$s', $params);

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
		if ($app === self::APP) {
			switch ($text) {
				case self::SUBJECT_ADD:
				case self::SUBJECT_ADD . '_self':
				case self::SUBJECT_DELETE:
				case self::SUBJECT_DELETE . '_self':
				case self::SUBJECT_UPDATE:
				case self::SUBJECT_UPDATE . '_self':
				case self::SUBJECT_SHARE_USER:
				case self::SUBJECT_SHARE_USER . '_you':
				case self::SUBJECT_UNSHARE_USER:
				case self::SUBJECT_UNSHARE_USER . '_you':
				case self::SUBJECT_UNSHARE_USER . '_self':
					return [
						0 => 'username',
						//1 => 'calendar',
					];
				case self::SUBJECT_SHARE_USER . '_by':
				case self::SUBJECT_UNSHARE_USER . '_by':
					return [
						0 => 'username',
						//1 => 'calendar',
						2 => 'username',
					];
				case self::SUBJECT_SHARE_GROUP . '_you':
				case self::SUBJECT_UNSHARE_GROUP . '_you':
					return [
						//0 => 'group',
						//1 => 'calendar',
					];
				case self::SUBJECT_SHARE_GROUP . '_by':
				case self::SUBJECT_UNSHARE_GROUP . '_by':
					return [
						//0 => 'group',
						//1 => 'calendar',
						2 => 'username',
					];

				case self::SUBJECT_OBJECT_ADD . '_event':
				case self::SUBJECT_OBJECT_ADD . '_event_self':
				case self::SUBJECT_OBJECT_DELETE . '_event':
				case self::SUBJECT_OBJECT_DELETE . '_event_self':
				case self::SUBJECT_OBJECT_UPDATE . '_event':
				case self::SUBJECT_OBJECT_UPDATE . '_event_self':

				case self::SUBJECT_OBJECT_ADD . '_todo':
				case self::SUBJECT_OBJECT_ADD . '_todo_self':
				case self::SUBJECT_OBJECT_DELETE . '_todo':
				case self::SUBJECT_OBJECT_DELETE . '_todo_self':
				case self::SUBJECT_OBJECT_UPDATE . '_todo':
				case self::SUBJECT_OBJECT_UPDATE . '_todo_self':

				case self::SUBJECT_OBJECT_UPDATE . '_todo_completed':
				case self::SUBJECT_OBJECT_UPDATE . '_todo_completed_self':
				case self::SUBJECT_OBJECT_UPDATE . '_todo_needs_action':
				case self::SUBJECT_OBJECT_UPDATE . '_todo_needs_action_self':
					return [
						0 => 'username',
						//1 => 'calendar',
						//2 => 'object',
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

}
