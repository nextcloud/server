<?php
/**
 * @copyright Copyright (c) 2016 Bjoern Schiessle <bjoern@schiessle.org>
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


namespace OCA\ShareByMail;


use OCP\Activity\IExtension;
use OCP\Activity\IManager;
use OCP\IL10N;
use OCP\L10N\IFactory;

class Activity implements IExtension {

	const SHARE_BY_MAIL_APP = 'sharebymail';

	const SUBJECT_SHARED_EMAIL_SELF = 'shared_with_email_self';
	const SUBJECT_SHARED_EMAIL_BY = 'shared_with_email_by';

	/** @var IFactory */
	private $languageFactory;

	/** @var IManager */
	private $activityManager;

	/**
	 * @param IFactory $languageFactory
	 * @param IManager $activityManager
	 */
	public function __construct(IFactory $languageFactory, IManager $activityManager) {
		$this->languageFactory = $languageFactory;
		$this->activityManager = $activityManager;
	}

	/**
	 * The extension can return an array of additional notification types.
	 * If no additional types are to be added false is to be returned
	 *
	 * @param string $languageCode
	 * @return array|false Array "stringID of the type" => "translated string description for the setting"
	 *                or Array "stringID of the type" => [
	 *                    'desc' => "translated string description for the setting"
	 *                    'methods' => [self::METHOD_*],
	 *                ]
	 * @since 8.0.0 - 8.2.0: Added support to allow limiting notifications to certain methods
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
	 * @since 8.0.0
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
	 * @since 8.0.0
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
	 * @since 8.0.0
	 */
	public function translate($app, $text, $params, $stripPath, $highlightParams, $languageCode) {
		if ($app !== self::SHARE_BY_MAIL_APP) {
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
	 * The extension can define the type of parameters for translation
	 *
	 * Currently known types are:
	 * * file        => will strip away the path of the file and add a tooltip with it
	 * * username    => will add the avatar of the user
	 *
	 * @param string $app
	 * @param string $text
	 * @return array|false
	 * @since 8.0.0
	 */
	public function getSpecialParameterList($app, $text) {
		if ($app === self::SHARE_BY_MAIL_APP) {
			switch ($text) {
				case self::SUBJECT_SHARED_EMAIL_BY:
					return [
						0 => 'file',
						1 => 'email',
						2 => 'user',
					];
				case self::SUBJECT_SHARED_EMAIL_SELF:
					return [
						0 => 'file',
						1 => 'email',
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
	 * @since 8.0.0
	 */
	public function getGroupParameter($activity) {
		if ($activity['app'] === self::SHARE_BY_MAIL_APP) {
			switch ($activity['subject']) {
				case self::SUBJECT_SHARED_EMAIL_BY:
					// Group by file name
					return 1;
				case self::SUBJECT_SHARED_EMAIL_SELF:
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
	 * @since 8.0.0
	 */
	public function getNavigation() {
		return false;
	}

	/**
	 * The extension can check if a customer filter (given by a query string like filter=abc) is valid or not.
	 *
	 * @param string $filterValue
	 * @return boolean
	 * @since 8.0.0
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
	 * @since 8.0.0
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
	 * @since 8.0.0
	 */
	public function getQueryForFilter($filter) {
		return false;
	}

	protected function getL10N($languageCode = null) {
		return $this->languageFactory->get(self::SHARE_BY_MAIL_APP, $languageCode);
	}

	/**
	 * @param string $text
	 * @param IL10N $l
	 * @param array $params
	 * @return bool|string
	 */
	protected function translateLong($text, IL10N $l, array $params) {

		switch ($text) {
			case self::SUBJECT_SHARED_EMAIL_SELF:
				return (string) $l->t('You shared %1$s with %2$s by mail', $params);
			case self::SUBJECT_SHARED_EMAIL_BY:
				return (string) $l->t('%3$s shared %1$s with %2$s by mail', $params);
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
			case self::SUBJECT_SHARED_EMAIL_SELF:
				return (string) $l->t('Shared with %2$s', $params);
			case self::SUBJECT_SHARED_EMAIL_BY:
				return (string) $l->t('Shared with %3$s by %2$s', $params);
		}

		return false;
	}

}
