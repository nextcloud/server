<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\TwoFactorAuth;

use OCA\TwoFactorBackupCodes\Provider\BackupCodesProvider;
use OCP\Authentication\TwoFactorAuth\IProvider;
use function array_filter;

/**
 * Contains all two-factor provider information for the two-factor login challenge
 */
class ProviderSet {
	/** @var IProvider */
	private $providers;

	/** @var bool */
	private $providerMissing;

	/**
	 * @param IProvider[] $providers
	 * @param bool $providerMissing
	 */
	public function __construct(array $providers, bool $providerMissing) {
		$this->providers = [];
		foreach ($providers as $provider) {
			$this->providers[$provider->getId()] = $provider;
		}
		$this->providerMissing = $providerMissing;
	}

	/**
	 * @param string $providerId
	 * @return IProvider|null
	 */
	public function getProvider(string $providerId) {
		return $this->providers[$providerId] ?? null;
	}

	/**
	 * @return IProvider[]
	 */
	public function getProviders(): array {
		return $this->providers;
	}

	/**
	 * @return IProvider[]
	 */
	public function getPrimaryProviders(): array {
		return array_filter($this->providers, function (IProvider $provider) {
			return !($provider instanceof BackupCodesProvider);
		});
	}

	public function isProviderMissing(): bool {
		return $this->providerMissing;
	}
}
