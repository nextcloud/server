<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC;

use OCP\IConfig;
use OCP\IServerInfo;
use Override;

readonly class ServerInfo implements IServerInfo {
	public function __construct(
		private IConfig $config,
	) {
	}

	#[Override]
	public function getServerId(): int {
		$serverid = $this->config->getSystemValueInt('serverid', -1);
		if ($serverid < 1) {
			// Fallback: generates a server ID based on hostname
			/** @var int<0,max> */
			$serverid = PHP_INT_SIZE === 4
				? hexdec(hash('xxh32', $this->getHostname()))
				// Makes sure it doesn't overflow 32 bits int
				: hexdec(substr(hash('xxh32', $this->getHostname()), -3));
		}

		/** @var int<0,511> */
		return $serverid & 0x1FF;
	}

	#[Override]
	public function getHostname(): string {
		$hostname = gethostname();
		if ($hostname === false) {
			// Use a random hostname
			return bin2hex(random_bytes(8));
		}

		return $hostname;
	}
}
