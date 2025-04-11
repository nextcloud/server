<?php

declare(strict_types = 1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC;

use OCP\IBinaryFinder;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Service that find the binary path for a program
 */
class BinaryFinder implements IBinaryFinder {
	public const DEFAULT_BINARY_SEARCH_PATHS = [
		'/usr/local/sbin',
		'/usr/local/bin',
		'/usr/sbin',
		'/usr/bin',
		'/sbin',
		'/bin',
		'/opt/bin',
	];
	private ICache $cache;

	public function __construct(
		ICacheFactory $cacheFactory,
		private IConfig $config,
	) {
		$this->cache = $cacheFactory->createLocal('findBinaryPath');
	}

	/**
	 * Try to find a program
	 *
	 * @return false|string
	 */
	public function findBinaryPath(string $program) {
		$result = $this->cache->get($program);
		if ($result !== null) {
			return $result;
		}
		$result = false;
		if (\OCP\Util::isFunctionEnabled('exec')) {
			$exeSniffer = new ExecutableFinder();
			// Returns null if nothing is found
			$result = $exeSniffer->find(
				$program,
				null,
				$this->config->getSystemValue('binary_search_paths', self::DEFAULT_BINARY_SEARCH_PATHS));
			if ($result === null) {
				$result = false;
			}
		}
		// store the value for 5 minutes
		$this->cache->set($program, $result, 300);
		return $result;
	}
}
