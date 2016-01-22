<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCP\Notification;

/**
 * Interface IManager
 *
 * @package OCP\Notification
 * @since 9.0.0
 */
interface IManager extends IApp, INotifier {
	/**
	 * @param \Closure $service The service must implement IApp, otherwise a
	 *                          \InvalidArgumentException is thrown later
	 * @return null
	 * @since 9.0.0
	 */
	public function registerApp(\Closure $service);

	/**
	 * @param \Closure $service The service must implement INotifier, otherwise a
	 *                          \InvalidArgumentException is thrown later
	 * @param \Closure $info    An array with the keys 'id' and 'name' containing
	 *                          the app id and the app name
	 * @return null
	 * @since 9.0.0
	 */
	public function registerNotifier(\Closure $service, \Closure $info);

	/**
	 * @return array App ID => App Name
	 * @since 9.0.0
	 */
	public function listNotifiers();

	/**
	 * @return INotification
	 * @since 9.0.0
	 */
	public function createNotification();

	/**
	 * @return bool
	 * @since 9.0.0
	 */
	public function hasNotifiers();
}
