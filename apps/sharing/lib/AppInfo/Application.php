<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\AppInfo;

use OCA\Sharing\Capabilities;
use OCA\Sharing\Features\ExpirationShareFeature;
use OCA\Sharing\Features\LabelShareFeature;
use OCA\Sharing\Features\NoteShareFeature;
use OCA\Sharing\RecipientTypes\GroupShareRecipientType;
use OCA\Sharing\RecipientTypes\UserShareRecipientType;
use OCA\Sharing\Registry;
use OCA\Sharing\SourceTypes\NodeShareSourceType;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Server;

class Application extends App implements IBootstrap {
	public const APP_ID = 'sharing';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerCapability(Capabilities::class);

		$registry = Server::get(Registry::class);

		$registry->registerSourceType(new NodeShareSourceType());

		$registry->registerRecipientType(new GroupShareRecipientType());
		$registry->registerRecipientType(new UserShareRecipientType());

		$registry->registerFeature(new ExpirationShareFeature());
		$registry->registerFeature(new LabelShareFeature());
		$registry->registerFeature(new NoteShareFeature());
	}

	public function boot(IBootContext $context): void {
	}
}
