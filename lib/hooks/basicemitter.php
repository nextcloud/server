<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Hooks;

abstract class BasicEmitter implements Emitter {

	/**
	 * @var (callable[])[] $listeners
	 */
	protected $listeners = array();

	/**
	 * @param string $scope
	 * @param string $method
	 * @param callable $callback
	 */
	public function listen($scope, $method, $callback) {
		$eventName = $scope . '::' . $method;
		if (!isset($this->listeners[$eventName])) {
			$this->listeners[$eventName] = array();
		}
		if (array_search($callback, $this->listeners[$eventName]) === false) {
			$this->listeners[$eventName][] = $callback;
		}
	}

	/**
	 * @param string $scope optional
	 * @param string $method optional
	 * @param callable $callback optional
	 */
	public function removeListener($scope = null, $method = null, $callback = null) {
		$names = array();
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
				$index = array_search($callback, $this->listeners[$name]);
				if ($index !== false) {
					unset($this->listeners[$name][$index]);
				}
			} else {
				$this->listeners[$name] = array();
			}
		}
	}

	/**
	 * @param string $scope
	 * @param string $method
	 * @param array $arguments optional
	 */
	protected function emit($scope, $method, $arguments = array()) {
		$eventName = $scope . '::' . $method;
		if (isset($this->listeners[$eventName])) {
			foreach ($this->listeners[$eventName] as $callback) {
				call_user_func_array($callback, $arguments);
			}
		}
	}
}
