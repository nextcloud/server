<?php
declare(strict_types=1);


/**
 * Push - Nextcloud Push Service
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2019, Maxence Lange <maxence@artificial-owl.com>
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


namespace OC\Push;


use OCP\Push\Exceptions\PushInstallException;
use OCP\Push\Helper\IPushHelper;
use OCP\Push\IPushManager;
use OCP\Push\Service\IPushService;


/**
 * Class PushManager
 *
 * @package OC\Push
 */
class PushManager implements IPushManager {


	/** @var IPushService */
	private $pushService;

	/** @var IPushHelper */
	private $pushHelper;


	/**
	 * @param IPushService $pushService
	 * @param IPushHelper $pushHelper
	 *
	 * @since 18.0.0
	 */
	public function registerPushApp(IPushService $pushService, IPushHelper $pushHelper
	) {
		$this->pushService = $pushService;
		$this->pushHelper = $pushHelper;
	}


	/**
	 * @return bool
	 */
	public function isAvailable(): bool {
		try {
			$this->checkRegistration();

			return true;
		} catch (PushInstallException $e) {
		}

		return false;
	}


	/**
	 * @return IPushService
	 * @throws PushInstallException
	 */
	public function getPushService(): IPushService {
		$this->checkRegistration();

		return $this->pushService;
	}


	/**
	 * @return IPushHelper
	 * @throws PushInstallException
	 */
	public function getPushHelper(): IPushHelper {
		$this->checkRegistration();

		return $this->pushHelper;
	}


	/**
	 * @throws PushInstallException
	 */
	private function checkRegistration() {
		if ($this->pushService === null || $this->pushHelper === null) {
			throw new PushInstallException(
				'Nextcloud Push is not available. Please check the Nextcloud Push App is installed and enabled'
			);
		}
	}

}

