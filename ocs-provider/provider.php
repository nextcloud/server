<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

class Provider extends \OCP\AppFramework\Controller {
	/** @var \OCP\App\IAppManager */
	private $appManager;

	/**
	 * @param string $appName
	 * @param \OCP\IRequest $request
	 * @param \OCP\App\IAppManager $appManager
	 */
	public function __construct($appName,
								\OCP\IRequest $request,
								\OCP\App\IAppManager $appManager) {
		parent::__construct($appName, $request);
		$this->appManager = $appManager;
	}

	/**
	 * @return \OCP\AppFramework\Http\JSONResponse
	 */
	public function buildProviderList() {
		$services = [
			'version' => 2,
			'PRIVATE_DATA' => [
				'version' => 1,
				'endpoints' => [
					'store' => '/ocs/v1.php/privatedata/setattribute',
					'read' => '/ocs/v1.php/privatedata/getattribute',
					'delete' => '/ocs/v1.php/privatedata/deleteattribute',
				],
			],
		];

		if($this->appManager->isEnabledForUser('files_sharing')) {
			$services['SHARING'] = [
				'version' => 1,
				'endpoints' => [
					'share' => '/ocs/v1.php/apps/files_sharing/api/v1/shares',
				],
			];
			$services['FEDERATED_SHARING'] = [
				'version' => 1,
				'endpoints' => [
					'share' => '/ocs/v1.php/cloud/shares',
					'webdav' => '/public.php/webdav/',
				],
			];
		}

		if($this->appManager->isEnabledForUser('activity')) {
			$services['ACTIVITY'] = [
				'version' => 1,
				'endpoints' => [
					'list' => '/ocs/v1.php/cloud/activity',
				],
			];
		}

		if($this->appManager->isEnabledForUser('provisioning_api')) {
			$services['PROVISIONING'] = [
				'version' => 1,
				'endpoints' => [
					'user' => '/ocs/v1.php/cloud/users',
					'groups' => '/ocs/v1.php/cloud/groups',
					'apps' => '/ocs/v1.php/cloud/apps',
				],
			];
		}

		return new \OCP\AppFramework\Http\JSONResponse($services);
	}
}