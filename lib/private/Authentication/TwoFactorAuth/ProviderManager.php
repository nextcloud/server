<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\TwoFactorAuth;

use OC\Authentication\Exceptions\InvalidProviderException;
use OCP\Authentication\TwoFactorAuth\IActivatableByAdmin;
use OCP\Authentication\TwoFactorAuth\IDeactivatableByAdmin;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\IUser;

class ProviderManager {
	public function __construct(
		private ProviderLoader $providerLoader,
		private IRegistry $providerRegistry,
	) {
	}

	private function getProvider(string $providerId, IUser $user): IProvider {
		$providers = $this->providerLoader->getProviders($user);

		if (!isset($providers[$providerId])) {
			throw new InvalidProviderException($providerId);
		}

		return $providers[$providerId];
	}

	/**
	 * Try to enable the provider with the given id for the given user
	 *
	 * @param IUser $user
	 *
	 * @return bool whether the provider supports this operation
	 */
	public function tryEnableProviderFor(string $providerId, IUser $user): bool {
		$provider = $this->getProvider($providerId, $user);

		if ($provider instanceof IActivatableByAdmin) {
			$provider->enableFor($user);
			$this->providerRegistry->enableProviderFor($provider, $user);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Try to disable the provider with the given id for the given user
	 *
	 * @param IUser $user
	 *
	 * @return bool whether the provider supports this operation
	 */
	public function tryDisableProviderFor(string $providerId, IUser $user): bool {
		$provider = $this->getProvider($providerId, $user);

		if ($provider instanceof IDeactivatableByAdmin) {
			$provider->disableFor($user);
			$this->providerRegistry->disableProviderFor($provider, $user);
			return true;
		} else {
			return false;
		}
	}
}
