<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\SystemTags\AppInfo;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Files_Sharing\Event\BeforeTemplateRenderedEvent;
use OCA\SystemTags\Activity\TagListener;
use OCA\SystemTags\Capabilities;
use OCA\SystemTags\Listeners\BeforeSabrePubliclyLoadedListener;
use OCA\SystemTags\Listeners\BeforeTemplateRenderedListener;
use OCA\SystemTags\Listeners\LoadAdditionalScriptsListener;
use OCA\SystemTags\Search\TagSearchProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\BeforeSabrePubliclyLoadedEvent;
use OCP\SystemTag\Events\TagCreatedEvent;
use OCP\SystemTag\Events\TagDeletedEvent;
use OCP\SystemTag\Events\TagUpdatedEvent;
use OCP\SystemTag\TagAssignedEvent;
use OCP\SystemTag\TagUnassignedEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'systemtags';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerSearchProvider(TagSearchProvider::class);
		$context->registerCapability(Capabilities::class);
		$context->registerEventListener(LoadAdditionalScriptsEvent::class, LoadAdditionalScriptsListener::class);
		$context->registerEventListener(BeforeTemplateRenderedEvent::class, BeforeTemplateRenderedListener::class);
		$context->registerEventListener(BeforeSabrePubliclyLoadedEvent::class, BeforeSabrePubliclyLoadedListener::class);

		$context->registerEventListener(TagCreatedEvent::class, TagListener::class);
		$context->registerEventListener(TagDeletedEvent::class, TagListener::class);
		$context->registerEventListener(TagUpdatedEvent::class, TagListener::class);

		$context->registerEventListener(TagAssignedEvent::class, TagListener::class);
		$context->registerEventListener(TagUnassignedEvent::class, TagListener::class);
	}

	public function boot(IBootContext $context): void {
	}
}
