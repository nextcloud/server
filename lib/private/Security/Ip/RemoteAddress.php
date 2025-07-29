<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Security\Ip;

use OCP\IConfig;
use OCP\IRequest;
use OCP\Security\Ip\IAddress;
use OCP\Security\Ip\IRange;
use OCP\Security\Ip\IRemoteAddress;

class RemoteAddress implements IRemoteAddress, IAddress {
	public const SETTING_NAME = 'allowed_admin_ranges';

	private readonly ?IAddress $ip;

	public function __construct(
		private IConfig $config,
		IRequest $request,
	) {
		$remoteAddress = $request->getRemoteAddress();
		$this->ip = $remoteAddress === ''
			? null
			: new Address($remoteAddress);
	}

	public static function isValid(string $ip): bool {
		return Address::isValid($ip);
	}

	public function matches(IRange ... $ranges): bool {
		return $this->ip === null
			? true
			: $this->ip->matches(... $ranges);
	}

	public function allowsAdminActions(): bool {
		if ($this->ip === null) {
			return true;
		}

		$allowedAdminRanges = $this->config->getSystemValue(self::SETTING_NAME, false);

		// Don't apply restrictions on empty or invalid configuration
		if (
			$allowedAdminRanges === false
			|| !is_array($allowedAdminRanges)
			|| empty($allowedAdminRanges)
		) {
			return true;
		}

		foreach ($allowedAdminRanges as $allowedAdminRange) {
			if ((new Range($allowedAdminRange))->contains($this->ip)) {
				return true;
			}
		}

		return false;
	}

	public function __toString(): string {
		return (string)$this->ip;
	}
}
