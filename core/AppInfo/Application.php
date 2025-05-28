<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\AppInfo;

use OC\Authentication\Events\RemoteWipeFinished;
use OC\Authentication\Events\RemoteWipeStarted;
use OC\Authentication\Listeners\RemoteWipeActivityListener;
use OC\Authentication\Listeners\RemoteWipeEmailListener;
use OC\Authentication\Listeners\RemoteWipeNotificationsListener;
use OC\Authentication\Listeners\UserDeletedFilesCleanupListener;
use OC\Authentication\Listeners\UserDeletedStoreCleanupListener;
use OC\Authentication\Listeners\UserDeletedTokenCleanupListener;
use OC\Authentication\Listeners\UserDeletedWebAuthnCleanupListener;
use OC\Authentication\Notifications\Notifier as AuthenticationNotifier;
use OC\Core\Listener\AddMissingIndicesListener;
use OC\Core\Listener\AddMissingPrimaryKeyListener;
use OC\Core\Listener\BeforeTemplateRenderedListener;
use OC\Core\Notification\CoreNotifier;
use OC\TagManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeLoginTemplateRenderedEvent;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\DB\Events\AddMissingIndicesEvent;
use OCP\DB\Events\AddMissingPrimaryKeyEvent;
use OCP\User\Events\BeforeUserDeletedEvent;
use OCP\User\Events\UserDeletedEvent;
use OCP\Util;

/**
 * Class Application
 *
 * @package OC\Core
 */
class Application extends App implements IBootstrap {

	public const APP_ID = 'core';

	/**
	 * Application constructor.
	 */
	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerService('defaultMailAddress', function () {
			return Util::getDefaultEmailAddress('lostpassword-noreply');
		});

		// register notifier
		$context->registerNotifierService(CoreNotifier::class);
		$context->registerNotifierService(AuthenticationNotifier::class);

		// register event listeners
		$context->registerEventListener(AddMissingIndicesEvent::class, AddMissingIndicesListener::class);
		$context->registerEventListener(AddMissingPrimaryKeyEvent::class, AddMissingPrimaryKeyListener::class);
		$context->registerEventListener(BeforeTemplateRenderedEvent::class, BeforeTemplateRenderedListener::class);
		$context->registerEventListener(BeforeLoginTemplateRenderedEvent::class, BeforeTemplateRenderedListener::class);
		$context->registerEventListener(RemoteWipeStarted::class, RemoteWipeActivityListener::class);
		$context->registerEventListener(RemoteWipeStarted::class, RemoteWipeNotificationsListener::class);
		$context->registerEventListener(RemoteWipeStarted::class, RemoteWipeEmailListener::class);
		$context->registerEventListener(RemoteWipeFinished::class, RemoteWipeActivityListener::class);
		$context->registerEventListener(RemoteWipeFinished::class, RemoteWipeNotificationsListener::class);
		$context->registerEventListener(RemoteWipeFinished::class, RemoteWipeEmailListener::class);
		$context->registerEventListener(UserDeletedEvent::class, UserDeletedStoreCleanupListener::class);
		$context->registerEventListener(UserDeletedEvent::class, UserDeletedTokenCleanupListener::class);
		$context->registerEventListener(BeforeUserDeletedEvent::class, UserDeletedFilesCleanupListener::class);
		$context->registerEventListener(UserDeletedEvent::class, UserDeletedFilesCleanupListener::class);
		$context->registerEventListener(UserDeletedEvent::class, UserDeletedWebAuthnCleanupListener::class);

		// Tags
		$context->registerEventListener(UserDeletedEvent::class, TagManager::class);
	}

	public function boot(IBootContext $context): void {
		// ...
	}

}
