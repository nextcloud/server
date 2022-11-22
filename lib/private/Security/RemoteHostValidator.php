<?php

declare(strict_types=1);

/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 */

namespace OC\Security;

use OC\Net\HostnameClassifier;
use OC\Net\IpAddressClassifier;
use OCP\IConfig;
use OCP\Security\IRemoteHostValidator;
use Psr\Log\LoggerInterface;
use function strpos;
use function strtolower;
use function substr;
use function urldecode;

/**
 * @internal
 */
final class RemoteHostValidator implements IRemoteHostValidator {
	private IConfig $config;
	private HostnameClassifier $hostnameClassifier;
	private IpAddressClassifier $ipAddressClassifier;
	private LoggerInterface $logger;

	public function __construct(IConfig $config,
								HostnameClassifier $hostnameClassifier,
								IpAddressClassifier $ipAddressClassifier,
								LoggerInterface $logger) {
		$this->config = $config;
		$this->hostnameClassifier = $hostnameClassifier;
		$this->ipAddressClassifier = $ipAddressClassifier;
		$this->logger = $logger;
	}

	public function isValid(string $host): bool {
		if ($this->config->getSystemValueBool('allow_local_remote_servers', false)) {
			return true;
		}

		$host = idn_to_utf8(strtolower(urldecode($host)));
		// Remove brackets from IPv6 addresses
		if (strpos($host, '[') === 0 && substr($host, -1) === ']') {
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
