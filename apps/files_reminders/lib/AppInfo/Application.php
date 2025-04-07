<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesReminders\AppInfo;

use OCA\DAV\Events\SabrePluginAddEvent;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\FilesReminders\Listener\LoadAdditionalScriptsListener;
use OCA\FilesReminders\Listener\NodeDeletedListener;
use OCA\FilesReminders\Listener\SabrePluginAddListener;
use OCA\FilesReminders\Listener\UserDeletedListener;
use OCA\FilesReminders\Notification\Notifier;
use OCA\FilesReminders\SetupChecks\NeedNotificationsApp;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\User\Events\UserDeletedEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'files_reminders';

	public function __construct() {
		parent::__construct(static::APP_ID);
	}

	public function boot(IBootContext $context): void {
	}

	public function register(IRegistrationContext $context): void {
		$context->registerNotifierService(Notifier::class);

		$context->registerEventListener(SabrePluginAddEvent::class, SabrePluginAddListener::class);

		$context->registerEventListener(NodeDeletedEvent::class, NodeDeletedListener::class);
		$context->registerEventListener(UserDeletedEvent::class, UserDeletedListener::class);

		$context->registerEventListener(LoadAdditionalScriptsEvent::class, LoadAdditionalScriptsListener::class);

		$context->registerSetupCheck(NeedNotificationsApp::class);
	}
}
