<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\TwoFactorAuth;

use OC\Authentication\TwoFactorAuth\Db\ProviderUserAssignmentDao;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\Authentication\TwoFactorAuth\RegistryEvent;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderDisabled;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserRegistered;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserUnregistered;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderUserDeleted;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;

class Registry implements IRegistry {
	/** @var ProviderUserAssignmentDao */
	private $assignmentDao;

	/** @var IEventDispatcher */
	private $dispatcher;

	public function __construct(ProviderUserAssignmentDao $assignmentDao,
		IEventDispatcher $dispatcher) {
		$this->assignmentDao = $assignmentDao;
		$this->dispatcher = $dispatcher;
	}

	public function getProviderStates(IUser $user): array {
		return $this->assignmentDao->getState($user->getUID());
	}

	public function enableProviderFor(IProvider $provider, IUser $user) {
		$this->assignmentDao->persist($provider->getId(), $user->getUID(), 1);

		$event = new RegistryEvent($provider, $user);
		$this->dispatcher->dispatch(self::EVENT_PROVIDER_ENABLED, $event);
		$this->dispatcher->dispatchTyped(new TwoFactorProviderForUserRegistered($user, $provider));
	}

	public function disableProviderFor(IProvider $provider, IUser $user) {
		$this->assignmentDao->persist($provider->getId(), $user->getUID(), 0);

		$event = new RegistryEvent($provider, $user);
		$this->dispatcher->dispatch(self::EVENT_PROVIDER_DISABLED, $event);
		$this->dispatcher->dispatchTyped(new TwoFactorProviderForUserUnregistered($user, $provider));
	}

	public function deleteUserData(IUser $user): void {
		foreach ($this->assignmentDao->deleteByUser($user->getUID()) as $provider) {
			$event = new TwoFactorProviderDisabled($provider['provider_id']);
			$this->dispatcher->dispatchTyped($event);
			$this->dispatcher->dispatchTyped(new TwoFactorProviderUserDeleted($user, $provider['provider_id']));
		}
	}

	public function cleanUp(string $providerId) {
		$this->assignmentDao->deleteAll($providerId);
	}
}
