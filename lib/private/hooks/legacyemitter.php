<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Hooks;

abstract class LegacyEmitter extends BasicEmitter {
	protected function emit($scope, $method, $arguments = array()) {
		\OC_Hook::emit($scope, $method, $arguments);
		parent::emit($scope, $method, $arguments);
	}
}
