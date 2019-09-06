<?php
declare(strict_types=1);


/**
 * Push - Nextcloud Push Service
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2020, Maxence Lange <maxence@artificial-owl.com>
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCP\Push\Helper;


use OCP\Push\Model\Helper\IPushCallback;
use OCP\Push\Model\Helper\IPushEvent;
use OCP\Push\Model\Helper\IPushNotification;
use OCP\Push\Model\IPushWrapper;


/**
 * Interface IPushHelper
 *
 * this Interface is used to quickly generate and save items, based on basic templates:
 *  - IPushNotification
 *  - IPushEvent
 *  - IPushCallback
 *
 * A PushHelper is registered by the Push App (when installed)
 *
 * @since 18.0.0
 *
 * @package OCP\Push\Helper
 */
interface IPushHelper {


	/**
	 * test the Push App integration, sending a test notification to $userId
	 *
	 * @param string $userId
	 *
	 * @return IPushWrapper
	 *
	 * @since 18.0.0
	 */
	public function test(string $userId): IPushWrapper;


	/**
	 * Using an IPushCallback, generates a IPushWrapper and save it in database.
	 *
	 * @param IPushCallback $callback
	 *
	 * @return IPushWrapper
	 *
	 * @since 18.0.0
	 */
	public function toCallback(IPushCallback $callback): IPushWrapper;

	/**
	 * Generates a IPushWrapper from an IPushCallback
	 *
	 * @param IPushCallback $callback
	 *
	 * @return IPushWrapper
	 *
	 * @since 18.0.0
	 */
	public function generateFromCallback(IPushCallback $callback): IPushWrapper;


	/**
	 * Using an IPushNotification, generates a IPushWrapper and save it in database.
	 *
	 * @param IPushNotification $notification
	 *
	 * @return IPushWrapper
	 *
	 * @since 18.0.0
	 */
	public function pushNotification(IPushNotification $notification): IPushWrapper;

	/**
	 * Generates a IPushWrapper from an IPushNotification
	 *
	 * @param IPushNotification $notification
	 *
	 * @return IPushWrapper
	 *
	 * @since 18.0.0
	 */
	public function generateFromNotification(IPushNotification $notification): IPushWrapper;


	/**
	 * Using an IPushEvent, generates a IPushWrapper and save it in database.
	 *
	 * @param IPushEvent $event
	 *
	 * @return IPushWrapper
	 *
	 * @since 18.0.0
	 */
	public function broadcastEvent(IPushEvent $event): IPushWrapper;

	/**
	 * Generates a IPushWrapper from an IPushEvent
	 *
	 * @param IPushEvent $event
	 *
	 * @return IPushWrapper
	 *
	 * @since 18.0.0
	 */
	public function generateFromEvent(IPushEvent $event): IPushWrapper;

}

