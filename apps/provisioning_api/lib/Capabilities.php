<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Provisioning_API;

use OCA\FederatedFileSharing\FederatedShareProvider;
use OCP\App\IAppManager;
use OCP\Capabilities\ICapability;

class Capabilities implements ICapability {

	/** @var IAppManager */
	private $appManager;

	public function __construct(IAppManager $appManager) {
		$this->appManager = $appManager;
	}

	/**
	 * Function an app uses to return the capabilities
	 *
	 * @return array{
	 *     provisioning_api: array{
	 *         version: string,
	 *         AccountPropertyScopesVersion: int,
	 *         AccountPropertyScopesFederatedEnabled: bool,
	 *         AccountPropertyScopesPublishedEnabled: bool,
	 *     },
	 * }
	 */
	public function getCapabilities() {
		$federatedScopeEnabled = $this->appManager->isEnabledForUser('federation');

		$publishedScopeEnabled = false;

		$federatedFileSharingEnabled = $this->appManager->isEnabledForUser('federatedfilesharing');
		if ($federatedFileSharingEnabled) {
			/** @var FederatedShareProvider $shareProvider */
			$shareProvider = \OC::$server->query(FederatedShareProvider::class);
			$publishedScopeEnabled = $shareProvider->isLookupServerUploadEnabled();
		}

		return [
			'provisioning_api' => [
				'version' => $this->appManager->getAppVersion('provisioning_api'),
				'AccountPropertyScopesVersion' => 2,
				'AccountPropertyScopesFederatedEnabled' => $federatedScopeEnabled,
				'AccountPropertyScopesPublishedEnabled' => $publishedScopeEnabled,
			]
		];
	}
}
