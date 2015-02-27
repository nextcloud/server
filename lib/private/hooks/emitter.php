<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Hooks;

/**
 * Class Emitter
 *
 * interface for all classes that are able to emit events
 *
 * @package OC\Hooks
 */
interface Emitter {
	/**
	 * @param string $scope
	 * @param string $method
	 * @param callable $callback
	 * @return void
	 */
	public function listen($scope, $method, $callback);

	/**
	 * @param string $scope optional
	 * @param string $method optional
	 * @param callable $callback optional
	 * @return void
	 */
	public function removeListener($scope = null, $method = null, $callback = null);
}
