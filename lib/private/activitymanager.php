<?php
/**
 * Copyright (c) 2013 Thomas Müller thomas.mueller@tmit.eu
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
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
	 * @param array $types
	 * @param string $filter
	 * @return array
	 */
	function filterNotificationTypes($types, $filter) {
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
		foreach($this->extensions as $extension) {
			$c = $extension();
			if ($c instanceof IExtension) {
				$specialParameter = $c->getSpecialParameterList($app, $text);
				if (is_array($specialParameter)) {
					return $specialParameter;
				}
			}
		}

		return false;
	}

	/**
	 * @param string $type
	 * @return string
	 */
	function getTypeIcon($type) {
		foreach($this->extensions as $extension) {
			$c = $extension();
			if ($c instanceof IExtension) {
				$icon = $c->getTypeIcon($type);
				if (is_string($icon)) {
					return $icon;
				}
			}
		}

		return '';
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
		foreach($this->extensions as $extension) {
			$c = $extension();
			if ($c instanceof IExtension) {
				if ($c->isFilterValid($filterValue) === true) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @param string $filter
	 * @return array
	 */
	function getQueryForFilter($filter) {

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
