<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\AppInfo;

use OCA\Viewer\Listener\SettingsListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;

/**
 * The server side of the viewer: the viewer itself is the @nextcloud/viewer
 * package, which core puts on every page.
 */
class Application extends App implements IBootstrap {
	public const APP_ID = 'viewer';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	#[\Override]
	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(BeforeTemplateRenderedEvent::class, SettingsListener::class);
	}

	#[\Override]
	public function boot(IBootContext $context): void {
	}
}
