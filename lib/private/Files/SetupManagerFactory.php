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
	private IEventLogger $eventLogger;
	private IMountProviderCollection $mountProviderCollection;
	private IUserManager $userManager;
	private IEventDispatcher $eventDispatcher;
	private IUserMountCache $userMountCache;
	private ILockdownManager $lockdownManager;
	private IUserSession $userSession;
	private ?SetupManager $setupManager;
	private ICacheFactory $cacheFactory;
	private LoggerInterface $logger;
	private IConfig $config;

	public function __construct(
		IEventLogger $eventLogger,
		IMountProviderCollection $mountProviderCollection,
		IUserManager $userManager,
		IEventDispatcher $eventDispatcher,
		IUserMountCache $userMountCache,
		ILockdownManager $lockdownManager,
		IUserSession $userSession,
		ICacheFactory $cacheFactory,
		LoggerInterface $logger,
		IConfig $config
	) {
		$this->eventLogger = $eventLogger;
		$this->mountProviderCollection = $mountProviderCollection;
		$this->userManager = $userManager;
		$this->eventDispatcher = $eventDispatcher;
		$this->userMountCache = $userMountCache;
		$this->lockdownManager = $lockdownManager;
		$this->userSession = $userSession;
		$this->cacheFactory = $cacheFactory;
		$this->logger = $logger;
		$this->config = $config;
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
				$this->config
			);
		}
		return $this->setupManager;
	}
}
