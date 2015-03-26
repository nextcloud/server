<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
namespace OC;


use OCP\Activity\IConsumer;
use OCP\Activity\IExtension;
use OCP\Activity\IManager;

class ActivityManager implements IManager {

	/**
	 * @var \Closure[]
	 */
	private $consumers = array();

	/**
	 * @var \Closure[]
	 */
	private $extensions = array();

	/** @var array list of filters "name" => "is valid" */
	protected $validFilters = array(
		'all'	=> true,
		'by'	=> true,
		'self'	=> true,
	);

	/** @var array list of type icons "type" => "css class" */
	protected $typeIcons = array();

	/** @var array list of special parameters "app" => ["text" => ["parameter" => "type"]] */
	protected $specialParameters = array();

	/**
	 * @param $app
	 * @param $subject
	 * @param $subjectParams
	 * @param $message
	 * @param $messageParams
	 * @param $file
	 * @param $link
	 * @param $affectedUser
	 * @param $type
	 * @param $priority
	 * @return mixed
	 */
	function publishActivity($app, $subject, $subjectParams, $message, $messageParams, $file, $link, $affectedUser, $type, $priority) {
		foreach($this->consumers as $consumer) {
			$c = $consumer();
			if ($c instanceof IConsumer) {
				try {
				$c->receive(
					$app,
					$subject,
					$subjectParams,
					$message,
					$messageParams,
					$file,
					$link,
					$affectedUser,
					$type,
					$priority);
				} catch (\Exception $ex) {
					// TODO: log the exception
				}
			}

		}
	}

	/**
	 * In order to improve lazy loading a closure can be registered which will be called in case
	 * activity consumers are actually requested
	 *
	 * $callable has to return an instance of OCA\Activity\IConsumer
	 *
	 * @param \Closure $callable
	 */
	function registerConsumer(\Closure $callable) {
		array_push($this->consumers, $callable);
	}

	/**
	 * In order to improve lazy loading a closure can be registered which will be called in case
	 * activity consumers are actually requested
	 *
	 * $callable has to return an instance of OCA\Activity\IConsumer
	 *
	 * @param \Closure $callable
	 * @return void
	 */
	function registerExtension(\Closure $callable) {
		array_push($this->extensions, $callable);
	}

	/**
	 * Will return additional notification types as specified by other apps
	 *
	 * @param string $languageCode
	 * @return array
	 */
	function getNotificationTypes($languageCode) {
		$notificationTypes = array();
		foreach($this->extensions as $extension) {
			$c = $extension();
			if ($c instanceof IExtension) {
				$result = $c->getNotificationTypes($languageCode);
				if (is_array($result)) {
					$notificationTypes = array_merge($notificationTypes, $result);
				}
			}
		}

		return $notificationTypes;
	}

	/**
	 * @param string $method
	 * @return array
	 */
	function getDefaultTypes($method) {
		$defaultTypes = array();
		foreach($this->extensions as $extension) {
			$c = $extension();
			if ($c instanceof IExtension) {
				$types = $c->getDefaultTypes($method);
				if (is_array($types)) {
					$defaultTypes = array_merge($types, $defaultTypes);
				}
			}
		}
		return $defaultTypes;
	}

	/**
	 * @param string $type
	 * @return string
	 */
	function getTypeIcon($type) {
		if (isset($this->typeIcons[$type])) {
			return $this->typeIcons[$type];
		}

		foreach($this->extensions as $extension) {
			$c = $extension();
			if ($c instanceof IExtension) {
				$icon = $c->getTypeIcon($type);
				if (is_string($icon)) {
					$this->typeIcons[$type] = $icon;
					return $icon;
				}
			}
		}

		$this->typeIcons[$type] = '';
		return '';
	}

	/**
	 * @param string $app
	 * @param string $text
	 * @param array $params
	 * @param boolean $stripPath
	 * @param boolean $highlightParams
	 * @param string $languageCode
	 * @return string|false
	 */
	function translate($app, $text, $params, $stripPath, $highlightParams, $languageCode) {
		foreach($this->extensions as $extension) {
			$c = $extension();
			if ($c instanceof IExtension) {
				$translation = $c->translate($app, $text, $params, $stripPath, $highlightParams, $languageCode);
				if (is_string($translation)) {
					return $translation;
				}
			}
		}

		return false;
	}

	/**
	 * @param string $app
	 * @param string $text
	 * @return array|false
	 */
	function getSpecialParameterList($app, $text) {
		if (isset($this->specialParameters[$app][$text])) {
			return $this->specialParameters[$app][$text];
		}

		if (!isset($this->specialParameters[$app])) {
			$this->specialParameters[$app] = array();
		}

		foreach($this->extensions as $extension) {
			$c = $extension();
			if ($c instanceof IExtension) {
				$specialParameter = $c->getSpecialParameterList($app, $text);
				if (is_array($specialParameter)) {
					$this->specialParameters[$app][$text] = $specialParameter;
					return $specialParameter;
				}
			}
		}

		$this->specialParameters[$app][$text] = false;
		return false;
	}

	/**
	 * @param array $activity
	 * @return integer|false
	 */
	function getGroupParameter($activity) {
		foreach($this->extensions as $extension) {
			$c = $extension();
			if ($c instanceof IExtension) {
				$parameter = $c->getGroupParameter($activity);
				if ($parameter !== false) {
					return $parameter;
				}
			}
		}

		return false;
	}

	/**
	 * @return array
	 */
	function getNavigation() {
		$entries = array(
			'apps' => array(),
			'top' => array(),
		);
		foreach($this->extensions as $extension) {
			$c = $extension();
			if ($c instanceof IExtension) {
				$additionalEntries = $c->getNavigation();
				if (is_array($additionalEntries)) {
					$entries['apps'] = array_merge($entries['apps'], $additionalEntries['apps']);
					$entries['top'] = array_merge($entries['top'], $additionalEntries['top']);
				}
			}
		}

		return $entries;
	}

	/**
	 * @param string $filterValue
	 * @return boolean
	 */
	function isFilterValid($filterValue) {
		if (isset($this->validFilters[$filterValue])) {
			return $this->validFilters[$filterValue];
		}

		foreach($this->extensions as $extension) {
			$c = $extension();
			if ($c instanceof IExtension) {
				if ($c->isFilterValid($filterValue) === true) {
					$this->validFilters[$filterValue] = true;
					return true;
				}
			}
		}

		$this->validFilters[$filterValue] = false;
		return false;
	}

	/**
	 * @param array $types
	 * @param string $filter
	 * @return array
	 */
	function filterNotificationTypes($types, $filter) {
		if (!$this->isFilterValid($filter)) {
			return $types;
		}

		foreach($this->extensions as $extension) {
			$c = $extension();
			if ($c instanceof IExtension) {
				$result = $c->filterNotificationTypes($types, $filter);
				if (is_array($result)) {
					$types = $result;
				}
			}
		}
		return $types;
	}

	/**
	 * @param string $filter
	 * @return array
	 */
	function getQueryForFilter($filter) {
		if (!$this->isFilterValid($filter)) {
			return [null, null];
		}

		$conditions = array();
		$parameters = array();

		foreach($this->extensions as $extension) {
			$c = $extension();
			if ($c instanceof IExtension) {
				$result = $c->getQueryForFilter($filter);
				if (is_array($result)) {
					list($condition, $parameter) = $result;
					if ($condition && is_array($parameter)) {
						$conditions[] = $condition;
						$parameters = array_merge($parameters, $parameter);
					}
				}
			}
		}

		if (empty($conditions)) {
			return array(null, null);
		}

		return array(' and ((' . implode(') or (', $conditions) . '))', $parameters);
	}
}
