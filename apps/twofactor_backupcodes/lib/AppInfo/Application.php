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
use OCA\TwoFactorBackupCodes\Listener\IListener;
use OCA\TwoFactorBackupCodes\Listener\RegistryUpdater;
use OCP\AppFramework\App;
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
			];

			foreach ($listeners as $listener) {
				$listener->handle($event);
			}
		});
	}

	public function deleteUser($params) {
		/** @var BackupCodeMapper $mapper */
		$mapper = $this->getContainer()->query(BackupCodeMapper::class);
		$mapper->deleteCodesByUserId($params['uid']);
	}
}
