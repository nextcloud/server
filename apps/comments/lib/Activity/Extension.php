<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
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

namespace OCA\Comments\Activity;

use OCP\Activity\IExtension;
use OCP\Activity\IManager;
use OCP\Comments\ICommentsManager;
use OCP\Comments\NotFoundException;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;

/**
 * Class Extension
 *
 * @package OCA\Comments\Activity
 */
class Extension implements IExtension {
	const APP_NAME = 'comments';

	const ADD_COMMENT_SUBJECT = 'add_comment_subject';
	const ADD_COMMENT_MESSAGE = 'add_comment_message';

	/** @var IFactory */
	protected $languageFactory;

	/** @var IManager */
	protected $activityManager;

	/** @var ICommentsManager */
	protected $commentsManager;

	/** @var IURLGenerator */
	protected $URLGenerator;

	/**
	 * @param IFactory $languageFactory
	 * @param IManager $activityManager
	 * @param ICommentsManager $commentsManager
	 * @param IURLGenerator $URLGenerator
	 */
	public function __construct(IFactory $languageFactory, IManager $activityManager, ICommentsManager $commentsManager, IURLGenerator $URLGenerator) {
		$this->languageFactory = $languageFactory;
		$this->activityManager = $activityManager;
		$this->commentsManager = $commentsManager;
		$this->URLGenerator = $URLGenerator;
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
			self::APP_NAME => [
				'desc' => (string) $l->t('<strong>Comments</strong> for files'),
				'methods' => [self::METHOD_MAIL, self::METHOD_STREAM],
			],
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
				return 'icon-comment';
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
			case self::ADD_COMMENT_SUBJECT:
				if ($this->authorIsCurrentUser($params[0])) {
					return (string) $l->t('You commented');
				}
				return (string) $l->t('%1$s commented', $params);
			case self::ADD_COMMENT_MESSAGE:
				return $this->convertParameterToComment($params[0], 120);
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
			case self::ADD_COMMENT_SUBJECT:
				if ($this->authorIsCurrentUser($params[0])) {
					return (string) $l->t('You commented on %2$s', $params);
				}
				return (string) $l->t('%1$s commented on %2$s', $params);
			case self::ADD_COMMENT_MESSAGE:
				return $this->convertParameterToComment($params[0]);
		}

		return false;
	}

	/**
	 * Check if the author is the current user
	 *
	 * @param string $user Parameter e.g. `<user display-name="admin">admin</user>`
	 * @return bool
	 */
	protected function authorIsCurrentUser($user) {
		try {
			return strip_tags($user) === $this->activityManager->getCurrentUserId();
		} catch (\UnexpectedValueException $e) {
			// FIXME this is awkward, but we have no access to the current user in emails
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
				case self::ADD_COMMENT_SUBJECT:
					return [
						0 => 'username',
						1 => 'file',
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
		$l = $this->getL10N();
		return [
			'apps' => [],
			'top' => [
				self::APP_NAME => [
					'id' => self::APP_NAME,
					'name' => (string) $l->t('Comments'),
					'url' => $this->URLGenerator->linkToRoute('activity.Activities.showList', ['filter' => self::APP_NAME]),
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
		return $filterValue === self::APP_NAME;
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
		if ($filter === self::APP_NAME) {
			return array_intersect($types, [self::APP_NAME]);
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
		return false;
	}

	/**
	 * @param string $parameter
	 * @return string
	 */
	protected function convertParameterToComment($parameter, $maxLength = 0) {
		if (preg_match('/^\<parameter\>(\d*)\<\/parameter\>$/', $parameter, $matches)) {
			try {
				$comment = $this->commentsManager->get((int) $matches[1]);
				$message = $comment->getMessage();
				$message = str_replace("\n", '<br />', str_replace(['<', '>'], ['&lt;', '&gt;'], $message));

				if ($maxLength && isset($message[$maxLength + 20])) {
					$findSpace = strpos($message, ' ', $maxLength);
					if ($findSpace !== false && $findSpace < $maxLength + 20) {
						return substr($message, 0, $findSpace) . '…';
					}
					return substr($message, 0, $maxLength + 20) . '…';
				}

				return $message;
			} catch (NotFoundException $e) {
				return '';
			}
		}

		return '';
	}
}
