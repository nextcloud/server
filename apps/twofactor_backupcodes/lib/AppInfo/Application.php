<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\AppInfo;

use OCA\TwoFactorBackupCodes\Event\CodesGenerated;
use OCA\TwoFactorBackupCodes\Listener\ActivityPublisher;
use OCA\TwoFactorBackupCodes\Listener\ClearNotifications;
use OCA\TwoFactorBackupCodes\Listener\ProviderDisabled;
use OCA\TwoFactorBackupCodes\Listener\ProviderEnabled;
use OCA\TwoFactorBackupCodes\Listener\RegistryUpdater;
use OCA\TwoFactorBackupCodes\Listener\UserDeleted;
use OCA\TwoFactorBackupCodes\Notifications\Notifier;
use OCA\TwoFactorBackupCodes\Provider\BackupCodesProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserRegistered;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserUnregistered;
use OCP\User\Events\UserDeletedEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'twofactor_backupcodes';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerNotifierService(Notifier::class);

		$context->registerEventListener(CodesGenerated::class, ActivityPublisher::class);
		$context->registerEventListener(CodesGenerated::class, RegistryUpdater::class);
		$context->registerEventListener(CodesGenerated::class, ClearNotifications::class);
		$context->registerEventListener(TwoFactorProviderForUserRegistered::class, ProviderEnabled::class);
		$context->registerEventListener(TwoFactorProviderForUserUnregistered::class, ProviderDisabled::class);
		$context->registerEventListener(UserDeletedEvent::class, UserDeleted::class);


		$context->registerTwoFactorProvider(BackupCodesProvider::class);
	}

	public function boot(IBootContext $context): void {
	}
}
