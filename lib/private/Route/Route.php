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
	public function method($method) {
		$this->setMethods($method);
		return $this;
	}

	public function post() {
		$this->method('POST');
		return $this;
	}

	public function get() {
		$this->method('GET');
		return $this;
	}

	public function put() {
		$this->method('PUT');
		return $this;
	}

	public function delete() {
		$this->method('DELETE');
		return $this;
	}

	public function patch() {
		$this->method('PATCH');
		return $this;
	}

	public function defaults($defaults) {
		$action = $this->getDefault('action');
		$this->setDefaults($defaults);
		if (isset($defaults['action'])) {
			$action = $defaults['action'];
		}
		$this->action($action);
		return $this;
	}

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

	public function action($class, $function = null) {
		$action = [$class, $function];
		if (is_null($function)) {
			$action = $class;
		}
		$this->setDefault('action', $action);
		return $this;
	}

	public function actionInclude($file) {
		$function = function ($param) use ($file) {
			unset($param['_route']);
			$_GET = array_merge($_GET, $param);
			unset($param);
			require_once "$file";
		} ;
		$this->action($function);
	}
}
