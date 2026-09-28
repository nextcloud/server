<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Comments\AppInfo;

use OCA\Comments\Capabilities;
use OCA\Comments\Listener\CommentsEntityEventListener;
use OCA\Comments\Listener\CommentsEventListener;
use OCA\Comments\Listener\LoadAdditionalScripts;
use OCA\Comments\Listener\LoadSidebarScripts;
use OCA\Comments\MaxAutoCompleteResultsInitialState;
use OCA\Comments\Notification\Notifier;
use OCA\Comments\Search\CommentsSearchProvider;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Files\Event\LoadSidebar;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Comments\CommentsEntityEvent;
use OCP\Comments\CommentsEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'comments';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerCapability(Capabilities::class);

		$context->registerEventListener(
			LoadAdditionalScriptsEvent::class,
			LoadAdditionalScripts::class
		);
		$context->registerEventListener(
			LoadSidebar::class,
			LoadSidebarScripts::class
		);
		$context->registerEventListener(
			CommentsEntityEvent::class,
			CommentsEntityEventListener::class
		);
		$context->registerEventListener(
			CommentsEvent::class,
			CommentsEventListener::class,
		);

		$context->registerSearchProvider(CommentsSearchProvider::class);

		$context->registerInitialStateProvider(MaxAutoCompleteResultsInitialState::class);

		$context->registerNotifierService(Notifier::class);
	}

	public function boot(IBootContext $context): void {
	}
}
