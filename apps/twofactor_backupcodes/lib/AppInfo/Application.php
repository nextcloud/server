<?php

/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\TwoFactorBackupCodes\AppInfo;

use OCA\TwoFactorBackupCodes\Db\BackupCodeMapper;
use OCA\TwoFactorBackupCodes\Event\CodesGenerated;
use OCA\TwoFactorBackupCodes\Listener\ActivityPublisher;
use OCA\TwoFactorBackupCodes\Listener\ClearNotifications;
use OCA\TwoFactorBackupCodes\Listener\IListener;
use OCA\TwoFactorBackupCodes\Listener\ProviderEnabled;
use OCA\TwoFactorBackupCodes\Listener\RegistryUpdater;
use OCA\TwoFactorBackupCodes\Notifications\Notifier;
use OCP\AppFramework\App;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\Authentication\TwoFactorAuth\RegistryEvent;
use OCP\IL10N;
use OCP\Notification\IManager;
use OCP\Util;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Application extends App {
	public function __construct() {
		parent::__construct('twofactor_backupcodes');
	}

	/**
	 * Register the different app parts
	 */
	public function register() {
		$this->registerHooksAndEvents();
		$this->registerNotification();
	}

	/**
	 * Register the hooks and events
	 */
	public function registerHooksAndEvents() {
		Util::connectHook('OC_User', 'post_deleteUser', $this, 'deleteUser');

		$container = $this->getContainer();
		/** @var EventDispatcherInterface $eventDispatcher */
		$eventDispatcher = $container->query(EventDispatcherInterface::class);
		$eventDispatcher->addListener(CodesGenerated::class, function (CodesGenerated $event) use ($container) {
			/** @var IListener[] $listeners */
			$listeners = [
				$container->query(ActivityPublisher::class),
				$container->query(RegistryUpdater::class),
				$container->query(ClearNotifications::class),
			];

			foreach ($listeners as $listener) {
				$listener->handle($event);
			}
		});

		$eventDispatcher->addListener(IRegistry::EVENT_PROVIDER_ENABLED, function(RegistryEvent $event) use ($container) {
			/** @var IListener $listener */
			$listener = $container->query(ProviderEnabled::class);
			$listener->handle($event);
		});
	}

	public function registerNotification() {
		$container = $this->getContainer();
		/** @var IManager $manager */
		$manager = $container->query(IManager::class);
		$manager->registerNotifier(
			function() use ($container) {
				return $container->query(Notifier::class);
			},
			function () use ($container) {
				$l = $container->query(IL10N::class);
				return ['id' => 'twofactor_backupcodes', 'name' => $l->t('Second-factor backup codes')];
			}
		);
	}

	public function deleteUser($params) {
		/** @var BackupCodeMapper $mapper */
		$mapper = $this->getContainer()->query(BackupCodeMapper::class);
		$mapper->deleteCodesByUserId($params['uid']);
	}
}
