<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\ContactsInteraction\AppInfo;

use OCA\ContactsInteraction\Listeners\ContactInteractionListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Contacts\Events\ContactInteractedWithEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'contactsinteraction';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(ContactInteractedWithEvent::class, ContactInteractionListener::class);
	}

	public function boot(IBootContext $context): void {
	}
}
