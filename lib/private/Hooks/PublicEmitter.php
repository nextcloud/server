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
class PublicEmitter extends BasicEmitter {
	/**
	 * @param string $scope
	 * @param string $method
	 * @param array $arguments optional
	 * @deprecated 18.0.0 use \OCP\EventDispatcher\IEventDispatcher::dispatchTyped
	 *
	 * @suppress PhanAccessMethodProtected
	 */
	public function emit($scope, $method, array $arguments = []) {
		parent::emit($scope, $method, $arguments);
	}
}
