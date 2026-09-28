<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\OCS;

use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class Provider extends Controller {
	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IAppManager $appManager
	 */
	public function __construct(
		$appName,
		\OCP\IRequest $request,
		private \OCP\App\IAppManager $appManager,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @return JSONResponse
	 */
	public function buildProviderList(): JSONResponse {
		$services = [
			'PRIVATE_DATA' => [
				'version' => 1,
				'endpoints' => [
					'store' => '/ocs/v2.php/privatedata/setattribute',
					'read' => '/ocs/v2.php/privatedata/getattribute',
					'delete' => '/ocs/v2.php/privatedata/deleteattribute',
				],
			],
		];

		if ($this->appManager->isEnabledForUser('files_sharing')) {
			$services['SHARING'] = [
				'version' => 1,
				'endpoints' => [
					'share' => '/ocs/v2.php/apps/files_sharing/api/v1/shares',
				],
			];
			$services['FEDERATED_SHARING'] = [
				'version' => 1,
				'endpoints' => [
					'share' => '/ocs/v2.php/cloud/shares',
					'webdav' => '/public.php/webdav/',
				],
			];
		}

		if ($this->appManager->isEnabledForUser('federation')) {
			if (isset($services['FEDERATED_SHARING'])) {
				$services['FEDERATED_SHARING']['endpoints']['shared-secret'] = '/ocs/v2.php/cloud/shared-secret';
				$services['FEDERATED_SHARING']['endpoints']['system-address-book'] = '/remote.php/dav/addressbooks/system/system/system';
				$services['FEDERATED_SHARING']['endpoints']['carddav-user'] = 'system';
			} else {
				$services['FEDERATED_SHARING'] = [
					'version' => 1,
					'endpoints' => [
						'shared-secret' => '/ocs/v2.php/cloud/shared-secret',
						'system-address-book' => '/remote.php/dav/addressbooks/system/system/system',
						'carddav-user' => 'system'
					],
				];
			}
		}

		if ($this->appManager->isEnabledForUser('activity')) {
			$services['ACTIVITY'] = [
				'version' => 1,
				'endpoints' => [
					'list' => '/ocs/v2.php/cloud/activity',
				],
			];
		}

		if ($this->appManager->isEnabledForUser('provisioning_api')) {
			$services['PROVISIONING'] = [
				'version' => 1,
				'endpoints' => [
					'user' => '/ocs/v2.php/cloud/users',
					'groups' => '/ocs/v2.php/cloud/groups',
					'apps' => '/ocs/v2.php/cloud/apps',
				],
			];
		}

		return new JSONResponse([
			'version' => 2,
			'services' => $services,
		]);
	}
}
