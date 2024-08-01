<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Hooks;

/**
 * @deprecated 18.0.0 use events and the \OCP\EventDispatcher\IEventDispatcher service
 */
trait EmitterTrait {
	/**
	 * @var callable[][] $listeners
	 */
	protected $listeners = [];

	/**
	 * @param string $scope
	 * @param string $method
	 * @param callable $callback
	 * @deprecated 18.0.0 use \OCP\EventDispatcher\IEventDispatcher::addListener
	 */
	public function listen($scope, $method, callable $callback) {
		$eventName = $scope . '::' . $method;
		if (!isset($this->listeners[$eventName])) {
			$this->listeners[$eventName] = [];
		}
		if (!in_array($callback, $this->listeners[$eventName], true)) {
			$this->listeners[$eventName][] = $callback;
		}
	}

	/**
	 * @param string $scope optional
	 * @param string $method optional
	 * @param callable $callback optional
	 * @deprecated 18.0.0 use \OCP\EventDispatcher\IEventDispatcher::removeListener
	 */
	public function removeListener($scope = null, $method = null, ?callable $callback = null) {
		$names = [];
		$allNames = array_keys($this->listeners);
		if ($scope and $method) {
			$name = $scope . '::' . $method;
			if (isset($this->listeners[$name])) {
				$names[] = $name;
			}
		} elseif ($scope) {
			foreach ($allNames as $name) {
				$parts = explode('::', $name, 2);
				if ($parts[0] == $scope) {
					$names[] = $name;
				}
			}
		} elseif ($method) {
			foreach ($allNames as $name) {
				$parts = explode('::', $name, 2);
				if ($parts[1] == $method) {
					$names[] = $name;
				}
			}
		} else {
			$names = $allNames;
		}

		foreach ($names as $name) {
			if ($callback) {
				$index = array_search($callback, $this->listeners[$name], true);
				if ($index !== false) {
					unset($this->listeners[$name][$index]);
				}
			} else {
				$this->listeners[$name] = [];
			}
		}
	}

	/**
	 * @param string $scope
	 * @param string $method
	 * @param array $arguments optional
	 * @deprecated 18.0.0 use \OCP\EventDispatcher\IEventDispatcher::dispatchTyped
	 */
	protected function emit($scope, $method, array $arguments = []) {
		$eventName = $scope . '::' . $method;
		if (isset($this->listeners[$eventName])) {
			foreach ($this->listeners[$eventName] as $callback) {
				call_user_func_array($callback, $arguments);
			}
		}
	}
}
