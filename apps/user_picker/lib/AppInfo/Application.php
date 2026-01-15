<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserPicker\AppInfo;

use OCA\UserPicker\Listener\UserPickerReferenceListener;
use OCA\UserPicker\Reference\ProfilePickerReferenceProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;

use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Collaboration\Reference\RenderReferenceEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'user_picker';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerReferenceProvider(ProfilePickerReferenceProvider::class);
		$context->registerEventListener(RenderReferenceEvent::class, UserPickerReferenceListener::class);
	}

	public function boot(IBootContext $context): void {
	}
}
