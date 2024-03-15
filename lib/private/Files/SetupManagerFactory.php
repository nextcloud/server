<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Robin Appelman <robin@icewind.nl>
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

namespace OC\Files;

use OC\Share20\ShareDisableChecker;
use OCP\Diagnostics\IEventLogger;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\Mount\IMountManager;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Lockdown\ILockdownManager;
use Psr\Log\LoggerInterface;

class SetupManagerFactory {
	private ?SetupManager $setupManager;

	public function __construct(
		private IEventLogger $eventLogger,
		private IMountProviderCollection $mountProviderCollection,
		private IUserManager $userManager,
		private IEventDispatcher $eventDispatcher,
		private IUserMountCache $userMountCache,
		private ILockdownManager $lockdownManager,
		private IUserSession $userSession,
		private ICacheFactory $cacheFactory,
		private LoggerInterface $logger,
		private IConfig $config,
		private ShareDisableChecker $shareDisableChecker,
	) {
		$this->setupManager = null;
	}

	public function create(IMountManager $mountManager): SetupManager {
		if (!$this->setupManager) {
			$this->setupManager = new SetupManager(
				$this->eventLogger,
				$this->mountProviderCollection,
				$mountManager,
				$this->userManager,
				$this->eventDispatcher,
				$this->userMountCache,
				$this->lockdownManager,
				$this->userSession,
				$this->cacheFactory,
				$this->logger,
				$this->config,
				$this->shareDisableChecker,
			);
		}
		return $this->setupManager;
	}
}
