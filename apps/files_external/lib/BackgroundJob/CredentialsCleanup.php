<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\BackgroundJob;

use OCA\Files_External\Lib\Auth\Password\LoginCredentials;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\Service\UserGlobalStoragesService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Security\ICredentialsManager;

class CredentialsCleanup extends TimedJob {
	private $credentialsManager;
	private $userGlobalStoragesService;
	private $userManager;

	public function __construct(
		ITimeFactory $time,
		ICredentialsManager $credentialsManager,
		UserGlobalStoragesService $userGlobalStoragesService,
		IUserManager $userManager
	) {
		parent::__construct($time);

		$this->credentialsManager = $credentialsManager;
		$this->userGlobalStoragesService = $userGlobalStoragesService;
		$this->userManager = $userManager;

		// run every day
		$this->setInterval(24 * 60 * 60);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	protected function run($argument) {
		$this->userManager->callForSeenUsers(function (IUser $user) {
			$storages = $this->userGlobalStoragesService->getAllStoragesForUser($user);

			$usesLoginCredentials = array_reduce($storages, function (bool $uses, StorageConfig $storage) {
				return $uses || $storage->getAuthMechanism() instanceof LoginCredentials;
			}, false);

			if (!$usesLoginCredentials) {
				$this->credentialsManager->delete($user->getUID(), LoginCredentials::CREDENTIALS_IDENTIFIER);
			}
		});
	}
}
