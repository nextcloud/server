<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Hooks;

class PublicEmitter extends BasicEmitter {
	/**
	 * @param string $scope
	 * @param string $method
	 * @param array $arguments optional
	 */
	public function emit($scope, $method, $arguments = array()) {
		parent::emit($scope, $method, $arguments);
	}
}
