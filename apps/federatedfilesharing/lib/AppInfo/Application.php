<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\FederatedFileSharing\AppInfo;

use Closure;
use OCA\FederatedFileSharing\Listeners\LoadAdditionalScriptsListener;
use OCA\FederatedFileSharing\Notifier;
use OCA\FederatedFileSharing\OCM\CloudFederationProviderFiles;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\IAppContainer;
use OCP\Federation\ICloudFederationProviderManager;

class Application extends App implements IBootstrap {
	public function __construct() {
		parent::__construct('federatedfilesharing');
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(LoadAdditionalScriptsEvent::class, LoadAdditionalScriptsListener::class);
		$context->registerNotifierService(Notifier::class);
	}

	public function boot(IBootContext $context): void {
		$context->injectFn(Closure::fromCallable([$this, 'registerCloudFederationProvider']));
	}

	private function registerCloudFederationProvider(ICloudFederationProviderManager $manager,
		IAppContainer $appContainer): void {
		$manager->addCloudFederationProvider('file',
			'Federated Files Sharing',
			function () use ($appContainer): CloudFederationProviderFiles {
				return $appContainer->get(CloudFederationProviderFiles::class);
			});
	}
}
