<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use Symfony\Component\Routing\Route;

class OC_Route extends Route {
	public function method($method) {
		$this->setRequirement('_method', $method);
		return $this;
	}

	public function post() {
		$this->method('post');
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
		$method = $this->getRequirement('_method');
		$this->setRequirements($requirements);
		if (isset($requirements['_method'])) {
			$method = $requirements['_method'];
		}
		$this->method($method);
		return $this;
	}

	public function action($class, $function = null) {
		$action = array($class, $function);
		if (is_null($function)) {
			$action = $class;
		}
		$this->setDefault('action', $action);
		return $this;
	}
}
