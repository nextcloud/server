<?php
/**
 * ownCloud - Files Activity Extension
 *
 * @author Joas Schilling
 * @copyright 2015 Joas Schilling nickvergessen@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files;

use OC\L10N\Factory;
use OCP\Activity\IExtension;
use OCP\IL10N;
use OCP\IURLGenerator;

class Activity implements IExtension {
	const FILTER_FILES = 'files';

	const TYPE_SHARE_CREATED = 'file_created';
	const TYPE_SHARE_CHANGED = 'file_changed';
	const TYPE_SHARE_DELETED = 'file_deleted';
	const TYPE_SHARE_RESTORED = 'file_restored';

	/** @var IL10N */
	protected $l;

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
		$this->l = $this->getL10N();
	}

	/**
	 * @param string|null $languageCode
	 * @return IL10N
	 */
	protected function getL10N($languageCode = null) {
		return $this->languageFactory->get('files', $languageCode);
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
		return [
			self::TYPE_SHARE_CREATED => (string) $l->t('A new file or folder has been <strong>created</strong>'),
			self::TYPE_SHARE_CHANGED => (string) $l->t('A file or folder has been <strong>changed</strong>'),
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
		if ($method === 'stream') {
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
		if ($app !== 'files') {
			return false;
		}

		switch ($text) {
			case 'created_self':
				return (string) $this->l->t('You created %1$s', $params);
			case 'created_by':
				return (string) $this->l->t('%2$s created %1$s', $params);
			case 'created_public':
				return (string) $this->l->t('%1$s was created in a public folder', $params);
			case 'changed_self':
				return (string) $this->l->t('You changed %1$s', $params);
			case 'changed_by':
				return (string) $this->l->t('%2$s changed %1$s', $params);
			case 'deleted_self':
				return (string) $this->l->t('You deleted %1$s', $params);
			case 'deleted_by':
				return (string) $this->l->t('%2$s deleted %1$s', $params);
			case 'restored_self':
				return (string) $this->l->t('You restored %1$s', $params);
			case 'restored_by':
				return (string) $this->l->t('%2$s restored %1$s', $params);

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
		if ($app === 'files') {
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
		if ($activity['app'] === 'files') {
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
			'apps' => [
				self::FILTER_FILES => [
					'id' => self::FILTER_FILES,
					'name' => (string) $this->l->t('Files'),
					'url' => $this->URLGenerator->linkToRoute('activity.Activities.showList', ['filter' => self::FILTER_FILES]),
				],
			],
			'top' => [],
		];
	}

	/**
	 * The extension can check if a customer filter (given by a query string like filter=abc) is valid or not.
	 *
	 * @param string $filterValue
	 * @return boolean
	 */
	public function isFilterValid($filterValue) {
		return $filterValue === self::FILTER_FILES;
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
		if ($filter === self::FILTER_FILES) {
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
		if ($filter === self::FILTER_FILES) {
			return ['`app` = ?', ['files']];
		}
		return false;
	}
}
