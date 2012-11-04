<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use Symfony\Component\Routing\Route;

class OC_Route extends Route {
	/**
	 * Specify the method when this route is to be used
	 *
	 * @param string $method HTTP method (uppercase)
	 */
	public function method($method) {
		$this->setRequirement('_method', strtoupper($method));
		return $this;
	}

	/**
	 * Specify POST as the method to use with this route
	 */
	public function post() {
		$this->method('POST');
		return $this;
	}

	/**
	 * Specify GET as the method to use with this route
	 */
	public function get() {
		$this->method('GET');
		return $this;
	}

	/**
	 * Specify PUT as the method to use with this route
	 */
	public function put() {
		$this->method('PUT');
		return $this;
	}

	/**
	 * Specify DELETE as the method to use with this route
	 */
	public function delete() {
		$this->method('DELETE');
		return $this;
	}

	/**
	 * Defaults to use for this route
	 *
	 * @param array $defaults The defaults
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
	 */
	public function requirements($requirements) {
		$method = $this->getRequirement('_method');
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
	 * @param string|callable $class the class or a callable
	 * @param string $function the function to use with the class
	 *
	 * This function is called with $class set to a callable or
	 * to the class with $function
	 */
	public function action($class, $function = null) {
		$action = array($class, $function);
		if (is_null($function)) {
			$action = $class;
		}
		$this->setDefault('action', $action);
		return $this;
	}

	/**
	 * The action to execute when this route matches, includes a file like
	 * it is called directly
	 * @param $file
	 */
	public function actionInclude($file) {
		$function = create_function('$param',
			'unset($param["_route"]);'
			.'$_GET=array_merge($_GET, $param);'
			.'unset($param);'
			.'require_once "'.$file.'";');
		$this->action($function);
	}
}
