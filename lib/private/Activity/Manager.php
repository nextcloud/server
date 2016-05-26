<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
namespace OC\Activity;


use OCP\Activity\IConsumer;
use OCP\Activity\IEvent;
use OCP\Activity\IExtension;
use OCP\Activity\IManager;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;

class Manager implements IManager {
	/** @var IRequest */
	protected $request;

	/** @var IUserSession */
	protected $session;

	/** @var IConfig */
	protected $config;

	/** @var string */
	protected $formattingObjectType;

	/** @var int */
	protected $formattingObjectId;

	/** @var string */
	protected $currentUserId;

	/**
	 * constructor of the controller
	 *
	 * @param IRequest $request
	 * @param IUserSession $session
	 * @param IConfig $config
	 */
	public function __construct(IRequest $request,
								IUserSession $session,
								IConfig $config) {
		$this->request = $request;
		$this->session = $session;
		$this->config = $config;
	}

	/** @var \Closure[] */
	private $consumersClosures = array();

	/** @var IConsumer[] */
	private $consumers = array();

	/** @var \Closure[] */
	private $extensionsClosures = array();

	/** @var IExtension[] */
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
	 * @return \OCP\Activity\IConsumer[]
	 */
	protected function getConsumers() {
		if (!empty($this->consumers)) {
			return $this->consumers;
		}

		$this->consumers = [];
		foreach($this->consumersClosures as $consumer) {
			$c = $consumer();
			if ($c instanceof IConsumer) {
				$this->consumers[] = $c;
			} else {
				throw new \InvalidArgumentException('The given consumer does not implement the \OCP\Activity\IConsumer interface');
			}
		}

		return $this->consumers;
	}

	/**
	 * @return \OCP\Activity\IExtension[]
	 */
	protected function getExtensions() {
		if (!empty($this->extensions)) {
			return $this->extensions;
		}

		$this->extensions = [];
		foreach($this->extensionsClosures as $extension) {
			$e = $extension();
			if ($e instanceof IExtension) {
				$this->extensions[] = $e;
			} else {
				throw new \InvalidArgumentException('The given extension does not implement the \OCP\Activity\IExtension interface');
			}
		}

		return $this->extensions;
	}

	/**
	 * Generates a new IEvent object
	 *
	 * Make sure to call at least the following methods before sending it to the
	 * app with via the publish() method:
	 *  - setApp()
	 *  - setType()
	 *  - setAffectedUser()
	 *  - setSubject()
	 *
	 * @return IEvent
	 */
	public function generateEvent() {
		return new Event();
	}

	/**
	 * Publish an event to the activity consumers
	 *
	 * Make sure to call at least the following methods before sending an Event:
	 *  - setApp()
	 *  - setType()
	 *  - setAffectedUser()
	 *  - setSubject()
	 *
	 * @param IEvent $event
	 * @return null
	 * @throws \BadMethodCallException if required values have not been set
	 */
	public function publish(IEvent $event) {
		if (!$event->getApp()) {
			throw new \BadMethodCallException('App not set', 10);
		}
		if (!$event->getType()) {
			throw new \BadMethodCallException('Type not set', 11);
		}
		if ($event->getAffectedUser() === null) {
			throw new \BadMethodCallException('Affected user not set', 12);
		}
		if ($event->getSubject() === null || $event->getSubjectParameters() === null) {
			throw new \BadMethodCallException('Subject not set', 13);
		}

		if ($event->getAuthor() === null) {
			if ($this->session->getUser() instanceof IUser) {
				$event->setAuthor($this->session->getUser()->getUID());
			}
		}

		if (!$event->getTimestamp()) {
			$event->setTimestamp(time());
		}

		foreach ($this->getConsumers() as $c) {
			$c->receive($event);
		}
	}

	/**
	 * @param string $app           The app where this event is associated with
	 * @param string $subject       A short description of the event
	 * @param array  $subjectParams Array with parameters that are filled in the subject
	 * @param string $message       A longer description of the event
	 * @param array  $messageParams Array with parameters that are filled in the message
	 * @param string $file          The file including path where this event is associated with
	 * @param string $link          A link where this event is associated with
	 * @param string $affectedUser  Recipient of the activity
	 * @param string $type          Type of the notification
	 * @param int    $priority      Priority of the notification
	 * @return null
	 */
	public function publishActivity($app, $subject, $subjectParams, $message, $messageParams, $file, $link, $affectedUser, $type, $priority) {
		$event = $this->generateEvent();
		$event->setApp($app)
			->setType($type)
			->setAffectedUser($affectedUser)
			->setSubject($subject, $subjectParams)
			->setMessage($message, $messageParams)
			->setObject('', 0, $file)
			->setLink($link);

		$this->publish($event);
	}

	/**
	 * In order to improve lazy loading a closure can be registered which will be called in case
	 * activity consumers are actually requested
	 *
	 * $callable has to return an instance of OCA\Activity\IConsumer
	 *
	 * @param \Closure $callable
	 */
	public function registerConsumer(\Closure $callable) {
		array_push($this->consumersClosures, $callable);
		$this->consumers = [];
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
	public function registerExtension(\Closure $callable) {
		array_push($this->extensionsClosures, $callable);
		$this->extensions = [];
	}

	/**
	 * Will return additional notification types as specified by other apps
	 *
	 * @param string $languageCode
	 * @return array
	 */
	public function getNotificationTypes($languageCode) {
		$filesNotificationTypes = [];
		$sharingNotificationTypes = [];

		$notificationTypes = array();
		foreach ($this->getExtensions() as $c) {
			$result = $c->getNotificationTypes($languageCode);
			if (is_array($result)) {
				if (class_exists('\OCA\Files\Activity', false) && $c instanceof \OCA\Files\Activity) {
					$filesNotificationTypes = $result;
					continue;
				}
				if (class_exists('\OCA\Files_Sharing\Activity', false) && $c instanceof \OCA\Files_Sharing\Activity) {
					$sharingNotificationTypes = $result;
					continue;
				}

				$notificationTypes = array_merge($notificationTypes, $result);
			}
		}

		return array_merge($filesNotificationTypes, $sharingNotificationTypes, $notificationTypes);
	}

	/**
	 * @param string $method
	 * @return array
	 */
	public function getDefaultTypes($method) {
		$defaultTypes = array();
		foreach ($this->getExtensions() as $c) {
			$types = $c->getDefaultTypes($method);
			if (is_array($types)) {
				$defaultTypes = array_merge($types, $defaultTypes);
			}
		}
		return $defaultTypes;
	}

	/**
	 * @param string $type
	 * @return string
	 */
	public function getTypeIcon($type) {
		if (isset($this->typeIcons[$type])) {
			return $this->typeIcons[$type];
		}

		foreach ($this->getExtensions() as $c) {
			$icon = $c->getTypeIcon($type);
			if (is_string($icon)) {
				$this->typeIcons[$type] = $icon;
				return $icon;
			}
		}

		$this->typeIcons[$type] = '';
		return '';
	}

	/**
	 * @param string $type
	 * @param string $id
	 */
	public function setFormattingObject($type, $id) {
		$this->formattingObjectType = $type;
		$this->formattingObjectId = (string) $id;
	}

	/**
	 * @return bool
	 */
	public function isFormattingFilteredObject() {
		return $this->formattingObjectType !== null && $this->formattingObjectId !== null
			&& $this->formattingObjectType === $this->request->getParam('object_type')
			&& $this->formattingObjectId === $this->request->getParam('object_id');
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
	public function translate($app, $text, $params, $stripPath, $highlightParams, $languageCode) {
		foreach ($this->getExtensions() as $c) {
			$translation = $c->translate($app, $text, $params, $stripPath, $highlightParams, $languageCode);
			if (is_string($translation)) {
				return $translation;
			}
		}

		return false;
	}

	/**
	 * @param string $app
	 * @param string $text
	 * @return array|false
	 */
	public function getSpecialParameterList($app, $text) {
		if (isset($this->specialParameters[$app][$text])) {
			return $this->specialParameters[$app][$text];
		}

		if (!isset($this->specialParameters[$app])) {
			$this->specialParameters[$app] = array();
		}

		foreach ($this->getExtensions() as $c) {
			$specialParameter = $c->getSpecialParameterList($app, $text);
			if (is_array($specialParameter)) {
				$this->specialParameters[$app][$text] = $specialParameter;
				return $specialParameter;
			}
		}

		$this->specialParameters[$app][$text] = false;
		return false;
	}

	/**
	 * @param array $activity
	 * @return integer|false
	 */
	public function getGroupParameter($activity) {
		foreach ($this->getExtensions() as $c) {
			$parameter = $c->getGroupParameter($activity);
			if ($parameter !== false) {
				return $parameter;
			}
		}

		return false;
	}

	/**
	 * @return array
	 */
	public function getNavigation() {
		$entries = array(
			'apps' => array(),
			'top' => array(),
		);
		foreach ($this->getExtensions() as $c) {
			$additionalEntries = $c->getNavigation();
			if (is_array($additionalEntries)) {
				$entries['apps'] = array_merge($entries['apps'], $additionalEntries['apps']);
				$entries['top'] = array_merge($entries['top'], $additionalEntries['top']);
			}
		}

		return $entries;
	}

	/**
	 * @param string $filterValue
	 * @return boolean
	 */
	public function isFilterValid($filterValue) {
		if (isset($this->validFilters[$filterValue])) {
			return $this->validFilters[$filterValue];
		}

		foreach ($this->getExtensions() as $c) {
			if ($c->isFilterValid($filterValue) === true) {
				$this->validFilters[$filterValue] = true;
				return true;
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
	public function filterNotificationTypes($types, $filter) {
		if (!$this->isFilterValid($filter)) {
			return $types;
		}

		foreach ($this->getExtensions() as $c) {
			$result = $c->filterNotificationTypes($types, $filter);
			if (is_array($result)) {
				$types = $result;
			}
		}
		return $types;
	}

	/**
	 * @param string $filter
	 * @return array
	 */
	public function getQueryForFilter($filter) {
		if (!$this->isFilterValid($filter)) {
			return [null, null];
		}

		$conditions = array();
		$parameters = array();

		foreach ($this->getExtensions() as $c) {
			$result = $c->getQueryForFilter($filter);
			if (is_array($result)) {
				list($condition, $parameter) = $result;
				if ($condition && is_array($parameter)) {
					$conditions[] = $condition;
					$parameters = array_merge($parameters, $parameter);
				}
			}
		}

		if (empty($conditions)) {
			return array(null, null);
		}

		return array(' and ((' . implode(') or (', $conditions) . '))', $parameters);
	}

	/**
	 * Set the user we need to use
	 *
	 * @param string|null $currentUserId
	 * @throws \UnexpectedValueException If the user is invalid
	 */
	public function setCurrentUserId($currentUserId) {
		if (!is_string($currentUserId) && $currentUserId !== null) {
			throw new \UnexpectedValueException('The given current user is invalid');
		}
		$this->currentUserId = $currentUserId;
	}

	/**
	 * Get the user we need to use
	 *
	 * Either the user is logged in, or we try to get it from the token
	 *
	 * @return string
	 * @throws \UnexpectedValueException If the token is invalid, does not exist or is not unique
	 */
	public function getCurrentUserId() {
		if ($this->currentUserId !== null) {
			return $this->currentUserId;
		} else if (!$this->session->isLoggedIn()) {
			return $this->getUserFromToken();
		} else {
			return $this->session->getUser()->getUID();
		}
	}

	/**
	 * Get the user for the token
	 *
	 * @return string
	 * @throws \UnexpectedValueException If the token is invalid, does not exist or is not unique
	 */
	protected function getUserFromToken() {
		$token = (string) $this->request->getParam('token', '');
		if (strlen($token) !== 30) {
			throw new \UnexpectedValueException('The token is invalid');
		}

		$users = $this->config->getUsersForUserValue('activity', 'rsstoken', $token);

		if (sizeof($users) !== 1) {
			// No unique user found
			throw new \UnexpectedValueException('The token is invalid');
		}

		// Token found login as that user
		return array_shift($users);
	}
}
