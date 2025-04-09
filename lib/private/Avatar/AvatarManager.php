<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Avatar;

use OC\KnownUser\KnownUserService;
use OC\User\Manager;
use OC\User\NoUserException;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\PropertyDoesNotExistException;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\StorageNotAvailableException;
use OCP\IAvatar;
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * This class implements methods to access Avatar functionality
 */
class AvatarManager implements IAvatarManager {
	public function __construct(
		private IUserSession $userSession,
		private Manager $userManager,
		private IAppData $appData,
		private IL10N $l,
		private LoggerInterface $logger,
		private IConfig $config,
		private IAccountManager $accountManager,
		private KnownUserService $knownUserService,
	) {
	}

	/**
	 * return a user specific instance of \OCP\IAvatar
	 *
	 * If the user is disabled a guest avatar will be returned
	 *
	 * @see \OCP\IAvatar
	 * @param string $userId the ownCloud user id
	 * @throws \Exception In case the username is potentially dangerous
	 * @throws NotFoundException In case there is no user folder yet
	 */
	public function getAvatar(string $userId): IAvatar {
		$user = $this->userManager->get($userId);
		if ($user === null) {
			throw new \Exception('user does not exist');
		}

		if (!$user->isEnabled()) {
			return $this->getGuestAvatar($userId);
		}

		// sanitize userID - fixes casing issue (needed for the filesystem stuff that is done below)
		$userId = $user->getUID();

		$requestingUser = $this->userSession->getUser();

		try {
			$folder = $this->appData->getFolder($userId);
		} catch (NotFoundException $e) {
			$folder = $this->appData->newFolder($userId);
		}

		// If we are requesting the avatar of another user;
		if ($requestingUser === null || $requestingUser->getUID() !== $user->getUID()) {
			// And if the user has not set a custom avatar, nor a display name;
			// Then we can use the guest avatar for single letters.
			if (empty($user->getDisplayName()) || !$this->hasComplexDisplayName($user->getDisplayName())
				&& !$this->hasCustomAvatar($user)) {
				return $this->getGuestAvatar($user->getUID());
			}
		}

		// Else, let's check the avatar scope
		// If the requesting user is the same as the user,
		// the knownUserService will obviously return true
		try {
			$account = $this->accountManager->getAccount($user);
			$avatarProperties = $account->getProperty(IAccountManager::PROPERTY_AVATAR);
			$avatarScope = $avatarProperties->getScope();
		} catch (PropertyDoesNotExistException $e) {
			$avatarScope = '';
		}

		switch ($avatarScope) {
			// v2-private scope hides the avatar from public access and from unknown users
			case IAccountManager::SCOPE_PRIVATE:
				if ($requestingUser !== null && $this->knownUserService->isKnownToUser($requestingUser->getUID(), $userId)) {
					return new UserAvatar($folder, $this->l, $user, $this->logger, $this->config);
				}
				break;
			case IAccountManager::SCOPE_LOCAL:
			case IAccountManager::SCOPE_FEDERATED:
			case IAccountManager::SCOPE_PUBLISHED:
				return new UserAvatar($folder, $this->l, $user, $this->logger, $this->config);
			default:
				// use a placeholder avatar which caches the generated images
				return new PlaceholderAvatar($folder, $user, $this->logger);
		}

		return new PlaceholderAvatar($folder, $user, $this->logger);
	}

	// Check if the user have set a custom avatar
	private function hasCustomAvatar(IUser $user): bool {
		// generated will be true if the system generated the avatar
		// and false if the user uploaded a custom avatar
		// It can also be non-existent if the user has no avatar yet
		return $this->config->getUserValue($user->getUID(), 'avatar', 'generated', '') === 'false';
	}

	/**
	 * Check if the display name is complex (e.g. "John Doe" or "John Doe Jr.")
	 * @see the getAvatarText() method in Avatar.php
	 */
	private function hasComplexDisplayName(string $displayName): bool {
		$firstTwoLetters = array_map(function ($namePart) {
			return mb_strtoupper(mb_substr($namePart, 0, 1), 'UTF-8');
		}, explode(' ', $displayName, 2));
		return count($firstTwoLetters) > 1;
	}

	/**
	 * Clear generated avatars
	 */
	public function clearCachedAvatars(): void {
		$users = $this->config->getUsersForUserValue('avatar', 'generated', 'true');
		foreach ($users as $userId) {
			// This also bumps the avatar version leading to cache invalidation in browsers
			$this->getAvatar($userId)->remove();
		}
	}

	public function deleteUserAvatar(string $userId): void {
		try {
			$folder = $this->appData->getFolder($userId);
			$folder->delete();
		} catch (NotFoundException $e) {
			$this->logger->debug("No cache for the user $userId. Ignoring avatar deletion");
		} catch (NotPermittedException|StorageNotAvailableException $e) {
			$this->logger->error("Unable to delete user avatars for $userId. gnoring avatar deletion");
		} catch (NoUserException $e) {
			$this->logger->debug("Account $userId not found. Ignoring avatar deletion");
		}
		$this->config->deleteUserValue($userId, 'avatar', 'generated');
	}

	/**
	 * Returns a GuestAvatar.
	 *
	 * @param string $name The guest name, e.g. "Albert".
	 */
	public function getGuestAvatar(string $name): IAvatar {
		return new GuestAvatar($name, $this->logger);
	}
}
