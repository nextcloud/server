<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
use OCP\Authentication\TwoFactorAuth\IRegistry;
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
		$context->registerEventListener(IRegistry::EVENT_PROVIDER_ENABLED, ProviderEnabled::class);
		$context->registerEventListener(IRegistry::EVENT_PROVIDER_DISABLED, ProviderDisabled::class);
		$context->registerEventListener(UserDeletedEvent::class, UserDeleted::class);


		$context->registerTwoFactorProvider(BackupCodesProvider::class);
	}

	public function boot(IBootContext $context): void {
	}
}
