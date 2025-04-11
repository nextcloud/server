<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Security;

use OC\Net\HostnameClassifier;
use OC\Net\IpAddressClassifier;
use OCP\IConfig;
use OCP\Security\IRemoteHostValidator;
use Psr\Log\LoggerInterface;
use function strtolower;
use function substr;
use function urldecode;

/**
 * @internal
 */
final class RemoteHostValidator implements IRemoteHostValidator {
	public function __construct(
		private IConfig $config,
		private HostnameClassifier $hostnameClassifier,
		private IpAddressClassifier $ipAddressClassifier,
		private LoggerInterface $logger,
	) {
	}

	public function isValid(string $host): bool {
		if ($this->config->getSystemValueBool('allow_local_remote_servers', false)) {
			return true;
		}

		$host = idn_to_utf8(strtolower(urldecode($host)));
		if ($host === false) {
			return false;
		}

		// Remove brackets from IPv6 addresses
		if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
			$host = substr($host, 1, -1);
		}

		if ($this->hostnameClassifier->isLocalHostname($host)
			|| $this->ipAddressClassifier->isLocalAddress($host)) {
			$this->logger->warning("Host $host was not connected to because it violates local access rules");
			return false;
		}

		return true;
	}
}
