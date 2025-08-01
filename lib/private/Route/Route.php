<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Route;

use OCP\Route\IRoute;
use Symfony\Component\Routing\Route as SymfonyRoute;

class Route extends SymfonyRoute implements IRoute {
	/**
	 * Specify the method when this route is to be used
	 *
	 * @param string $method HTTP method (uppercase)
	 * @return \OC\Route\Route
	 */
	public function method($method) {
		$this->setMethods($method);
		return $this;
	}

	/**
	 * Specify POST as the method to use with this route
	 * @return \OC\Route\Route
	 */
	public function post() {
		$this->method('POST');
		return $this;
	}

	/**
	 * Specify GET as the method to use with this route
	 * @return \OC\Route\Route
	 */
	public function get() {
		$this->method('GET');
		return $this;
	}

	/**
	 * Specify PUT as the method to use with this route
	 * @return \OC\Route\Route
	 */
	public function put() {
		$this->method('PUT');
		return $this;
	}

	/**
	 * Specify DELETE as the method to use with this route
	 * @return \OC\Route\Route
	 */
	public function delete() {
		$this->method('DELETE');
		return $this;
	}

	/**
	 * Specify PATCH as the method to use with this route
	 * @return \OC\Route\Route
	 */
	public function patch() {
		$this->method('PATCH');
		return $this;
	}

	/**
	 * Defaults to use for this route
	 *
	 * @param array $defaults The defaults
	 * @return \OC\Route\Route
	 */
	public function defaults($defaults) {
		$action = $this->getDefault('action');
		$this->setDefaults($defaults);
		if (isset($defaults['action'])) {
			$action = $defaults['action'];
		}
		$this->action($action);
		return $this;
	}

	/**
	 * Requirements for this route
	 *
	 * @param array $requirements The requirements
	 * @return \OC\Route\Route
	 */
	public function requirements($requirements) {
		$method = $this->getMethods();
		$this->setRequirements($requirements);
		if (isset($requirements['_method'])) {
			$method = $requirements['_method'];
		}
		if ($method) {
			$this->method($method);
		}
		return $this;
	}

	/**
	 * The action to execute when this route matches
	 *
	 * @param string|callable $class the class or a callable
	 * @param string $function the function to use with the class
	 * @return \OC\Route\Route
	 *
	 * This function is called with $class set to a callable or
	 * to the class with $function
	 */
	public function action($class, $function = null) {
		$action = [$class, $function];
		if (is_null($function)) {
			$action = $class;
		}
		$this->setDefault('action', $action);
		return $this;
	}

	/**
	 * The action to execute when this route matches, includes a file like
	 * it is called directly
	 * @param string $file
	 */
	public function actionInclude($file) {
		$this->setDefault('file', $file);
		return $this;
	}
}
