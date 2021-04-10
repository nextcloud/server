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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\TwoFactorBackupCodes\AppInfo;

use Closure;
use OCA\TwoFactorBackupCodes\Db\BackupCodeMapper;
use OCA\TwoFactorBackupCodes\Event\CodesGenerated;
use OCA\TwoFactorBackupCodes\Listener\ActivityPublisher;
use OCA\TwoFactorBackupCodes\Listener\ClearNotifications;
use OCA\TwoFactorBackupCodes\Listener\ProviderDisabled;
use OCA\TwoFactorBackupCodes\Listener\ProviderEnabled;
use OCA\TwoFactorBackupCodes\Listener\RegistryUpdater;
use OCA\TwoFactorBackupCodes\Notifications\Notifier;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\Notification\IManager;
use OCP\Util;

class Application extends App implements IBootstrap {
	public const APP_ID = 'twofactor_backupcodes';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$this->registerHooksAndEvents($context);
	}

	public function boot(IBootContext $context): void {
		Util::connectHook('OC_User', 'post_deleteUser', $this, 'deleteUser');

		$context->injectFn(Closure::fromCallable([$this, 'registerNotification']));
	}

	/**
	 * Register the hooks and events
	 */
	public function registerHooksAndEvents(IRegistrationContext $context) {
		$context->registerEventListener(CodesGenerated::class, ActivityPublisher::class);
		$context->registerEventListener(CodesGenerated::class, RegistryUpdater::class);
		$context->registerEventListener(CodesGenerated::class, ClearNotifications::class);
		$context->registerEventListener(IRegistry::EVENT_PROVIDER_ENABLED, ProviderEnabled::class);
		$context->registerEventListener(IRegistry::EVENT_PROVIDER_DISABLED, ProviderDisabled::class);
	}

	private function registerNotification(IManager $manager) {
		$manager->registerNotifierService(Notifier::class);
	}

	public function deleteUser($params) {
		/** @var BackupCodeMapper $mapper */
		$mapper = $this->getContainer()->query(BackupCodeMapper::class);
		$mapper->deleteCodesByUserId($params['uid']);
	}
}
