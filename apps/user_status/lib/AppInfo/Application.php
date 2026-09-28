<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserStatus\AppInfo;

use OCA\UserStatus\Capabilities;
use OCA\UserStatus\Connector\UserStatusProvider;
use OCA\UserStatus\Dashboard\UserStatusWidget;
use OCA\UserStatus\Listener\BeforeTemplateRenderedListener;
use OCA\UserStatus\Listener\OutOfOfficeStatusListener;
use OCA\UserStatus\Listener\UserDeletedListener;
use OCA\UserStatus\Listener\UserLiveStatusListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\IConfig;
use OCP\User\Events\OutOfOfficeChangedEvent;
use OCP\User\Events\OutOfOfficeClearedEvent;
use OCP\User\Events\OutOfOfficeEndedEvent;
use OCP\User\Events\OutOfOfficeScheduledEvent;
use OCP\User\Events\OutOfOfficeStartedEvent;
use OCP\User\Events\UserDeletedEvent;
use OCP\User\Events\UserLiveStatusEvent;
use OCP\UserStatus\IManager;

/**
 * Class Application
 *
 * @package OCA\UserStatus\AppInfo
 */
class Application extends App implements IBootstrap {

	/** @var string */
	public const APP_ID = 'user_status';

	/**
	 * Application constructor.
	 *
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	/**
	 * @inheritDoc
	 */
	public function register(IRegistrationContext $context): void {
		// Register OCS Capabilities
		$context->registerCapability(Capabilities::class);

		// Register Event Listeners
		$context->registerEventListener(UserDeletedEvent::class, UserDeletedListener::class);
		$context->registerEventListener(UserLiveStatusEvent::class, UserLiveStatusListener::class);
		$context->registerEventListener(BeforeTemplateRenderedEvent::class, BeforeTemplateRenderedListener::class);
		$context->registerEventListener(OutOfOfficeChangedEvent::class, OutOfOfficeStatusListener::class);
		$context->registerEventListener(OutOfOfficeScheduledEvent::class, OutOfOfficeStatusListener::class);
		$context->registerEventListener(OutOfOfficeClearedEvent::class, OutOfOfficeStatusListener::class);
		$context->registerEventListener(OutOfOfficeStartedEvent::class, OutOfOfficeStatusListener::class);
		$context->registerEventListener(OutOfOfficeEndedEvent::class, OutOfOfficeStatusListener::class);

		$config = $this->getContainer()->query(IConfig::class);
		$shareeEnumeration = $config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes') === 'yes';
		$shareeEnumerationInGroupOnly = $shareeEnumeration && $config->getAppValue('core', 'shareapi_restrict_user_enumeration_to_group', 'no') === 'yes';
		$shareeEnumerationPhone = $shareeEnumeration && $config->getAppValue('core', 'shareapi_restrict_user_enumeration_to_phone', 'no') === 'yes';

		// Register the Dashboard panel if user enumeration is enabled and not limited
		if ($shareeEnumeration && !$shareeEnumerationInGroupOnly && !$shareeEnumerationPhone) {
			$context->registerDashboardWidget(UserStatusWidget::class);
		}
	}

	public function boot(IBootContext $context): void {
		/** @var IManager $userStatusManager */
		$userStatusManager = $context->getServerContainer()->get(IManager::class);
		$userStatusManager->registerProvider(UserStatusProvider::class);
	}
}
