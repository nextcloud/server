<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
