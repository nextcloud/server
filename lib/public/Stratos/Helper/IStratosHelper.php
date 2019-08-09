<?php
declare(strict_types=1);


/**
 * Stratos - above your cloud
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


namespace OCP\Stratos\Helper;


use OCP\Stratos\Model\IStratosEvent;
use OCP\Stratos\Model\IStratosNotification;
use OCP\Stratos\Model\IStratosWrapper;


/**
 * Interface IStratosHelper
 *
 * @since 18.0.0
 *
 * @package OCP\Stratos\Helper
 */
interface IStratosHelper {


	/**
	 * @param string $userId
	 *
	 * @return IStratosWrapper
	 */
	public function test(string $userId): IStratosWrapper;


	/**
	 * @param IStratosNotification $notification
	 * @param string $userId
	 *
	 * @return IStratosWrapper
	 */
	public function pushNotification(IStratosNotification $notification, string $userId
	): IStratosWrapper;

	/**
	 * @param IStratosNotification $notification
	 * @param array $users
	 *
	 * @return IStratosWrapper
	 */
	public function pushNotifications(IStratosNotification $notification, array $users
	): IStratosWrapper;


	/**
	 * @param IStratosEvent $event
	 * @param string $userId
	 *
	 * @return IStratosWrapper
	 */
	public function broadcastEvent(IStratosEvent $event, string $userId): IStratosWrapper;

	/**
	 * @param IStratosEvent $event
	 * @param array $users
	 *
	 * @return IStratosWrapper
	 */
	public function broadcastEvents(IStratosEvent $event, array $users): IStratosWrapper;

}

