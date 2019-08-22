<?php
declare(strict_types=1);


/**
 * Push - Nextcloud Push Service
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018, Maxence Lange <maxence@artificial-owl.com>
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
 * @since 18.0.0
 *
 * @package OCP\Push\Helper
 */
interface IPushHelper {


	/**
	 * @param string $userId
	 *
	 * @return IPushWrapper
	 */
	public function test(string $userId): IPushWrapper;


	/**
	 * @param IPushCallback $callback
	 *
	 * @return IPushWrapper
	 */
	public function toCallback(IPushCallback $callback): IPushWrapper;

	/**
	 * @param IPushCallback $callback
	 *
	 * @return IPushWrapper
	 */
	public function generateFromCallback(IPushCallback $callback): IPushWrapper;




	/**
	 * @param IPushNotification $notification
	 *
	 * @return IPushWrapper
	 */
	public function pushNotification(IPushNotification $notification): IPushWrapper;

	/**
	 * @param IPushNotification $notification
	 *
	 * @return IPushWrapper
	 */
	public function generateFromNotification(IPushNotification $notification): IPushWrapper;


	/**
	 * @param IPushEvent $event
	 *
	 * @return IPushWrapper
	 */
	public function broadcastEvent(IPushEvent $event): IPushWrapper;

	/**
	 * @param IPushEvent $event
	 *
	 * @return IPushWrapper
	 */
	public function generateFromEvent(IPushEvent $event): IPushWrapper;

}
