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


namespace OCP\Push;


use OCP\Push\Exceptions\PushInstallException;
use OCP\Push\Helper\IPushHelper;
use OCP\Push\Service\IPushService;


/**
 * Interface IPushManager
 *
 * IPushManager is a service available in Core that can be integrated within any app:
 *
 *    use OCP\Push\IPushManager;
 *    public function __construct(IPushManager $pushManager) {
 *       $this->pushManager = $pushManager;
 *    }
 *
 * Once defined, it will be used to obtains more services from the Push App, if the App is available.
 *
 * @since 18.0.0
 *
 * @package OCP\Push
 */
interface IPushManager {


	/**
	 * Register a IPushService and IPushHelper.
	 * This is used by the Push App itself.
	 *
	 * @param IPushService $pushService
	 * @param IPushHelper $pushHelper
	 *
	 * @since 18.0.0
	 *
	 */
	public function registerPushApp(IPushService $pushService, IPushHelper $pushHelper): void;


	/**
	 * returns if the Push App is available or not.
	 *
	 * @return bool
	 *
	 * @since 18.0.0
	 */
	public function isAvailable(): bool;


	/**
	 * returns the registered IPushService
	 *
	 * @return IPushService
	 * @throws PushInstallException
	 *
	 * @since 18.0.0
	 */
	public function getPushService(): IPushService;


	/**
	 * returns the registered IPushHelper
	 *
	 * @return IPushHelper
	 * @throws PushInstallException
	 * @since 18.0.0
	 *
	 */
	public function getPushHelper(): IPushHelper;

}

