<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Federation\AppInfo;

use OCA\DAV\Events\SabrePluginAuthInitEvent;
use OCA\Federation\Listener\SabrePluginAuthInitListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {

	/**
	 * @param array $urlParams
	 */
	public function __construct($urlParams = []) {
		parent::__construct('federation', $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(SabrePluginAuthInitEvent::class, SabrePluginAuthInitListener::class);
	}

	public function boot(IBootContext $context): void {
	}
}
