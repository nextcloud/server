<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\Ip;

use OCP\IAppConfig;
use OCP\Security\Ip\IFactory;

class BruteforceAllowList {
	/** @var array<string, bool> */
	protected array $ipIsAllowListed = [];

	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly IFactory $factory,
	) {
	}

	/**
	 * Check if the IP is allowed to bypass bruteforce protection
	 */
	public function isBypassListed(string $ip): bool {
		if (isset($this->ipIsAllowListed[$ip])) {
			return $this->ipIsAllowListed[$ip];
		}

		try {
			$address = $this->factory->addressFromString($ip);
		} catch (\InvalidArgumentException) {
			$this->ipIsAllowListed[$ip] = false;
			return false;
		}

		$keys = $this->appConfig->getKeys('bruteForce');
		$keys = array_filter($keys, static fn ($key): bool => str_starts_with($key, 'whitelist_'));

		foreach ($keys as $key) {
			$rangeString = $this->appConfig->getValueString('bruteForce', $key);
			try {
				$range = $this->factory->rangeFromString($rangeString);
			} catch (\InvalidArgumentException) {
				continue;
			}

			$allowed = $range->contains($address);
			if ($allowed) {
				$this->ipIsAllowListed[$ip] = true;
				return true;
			}
		}

		$this->ipIsAllowListed[$ip] = false;
		return false;
	}
}
