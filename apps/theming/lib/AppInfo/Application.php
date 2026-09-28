<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\AppInfo;

use OCA\Theming\Capabilities;
use OCA\Theming\Listener\BeforePreferenceListener;
use OCA\Theming\Listener\BeforeTemplateRenderedListener;
use OCA\Theming\SetupChecks\PhpImagickModule;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeLoginTemplateRenderedEvent;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\Config\BeforePreferenceDeletedEvent;
use OCP\Config\BeforePreferenceSetEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'theming';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerCapability(Capabilities::class);
		$context->registerEventListener(BeforeTemplateRenderedEvent::class, BeforeTemplateRenderedListener::class);
		$context->registerEventListener(BeforeLoginTemplateRenderedEvent::class, BeforeTemplateRenderedListener::class);
		$context->registerEventListener(BeforePreferenceSetEvent::class, BeforePreferenceListener::class);
		$context->registerEventListener(BeforePreferenceDeletedEvent::class, BeforePreferenceListener::class);
		$context->registerSetupCheck(PhpImagickModule::class);
	}

	public function boot(IBootContext $context): void {
	}
}
