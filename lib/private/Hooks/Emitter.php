<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Hooks;

/**
 * Class Emitter
 *
 * interface for all classes that are able to emit events
 *
 * @package OC\Hooks
 * @deprecated 18.0.0 use events and the \OCP\EventDispatcher\IEventDispatcher service
 */
interface Emitter {
	/**
	 * @param string $scope
	 * @param string $method
	 * @param callable $callback
	 * @return void
	 * @deprecated 18.0.0 use \OCP\EventDispatcher\IEventDispatcher::addListener
	 */
	public function listen($scope, $method, callable $callback);

	/**
	 * @param string $scope optional
	 * @param string $method optional
	 * @param callable $callback optional
	 * @return void
	 * @deprecated 18.0.0 use \OCP\EventDispatcher\IEventDispatcher::removeListener
	 */
	public function removeListener($scope = null, $method = null, ?callable $callback = null);
}
