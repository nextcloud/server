<?php

declare(strict_types = 1);
/**
 * @copyright 2022 Carl Schwan <carl@carlschwan.eu>
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC;

use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IBinaryFinder;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Service that find the binary path for a program
 */
class BinaryFinder implements IBinaryFinder {
	private ICache $cache;

	public function __construct(ICacheFactory $cacheFactory) {
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
			$result = $exeSniffer->find($program, null, [
				'/usr/local/sbin',
				'/usr/local/bin',
				'/usr/sbin',
				'/usr/bin',
				'/sbin',
				'/bin',
				'/opt/bin',
			]);
			if ($result === null) {
				$result = false;
			}
		}
		// store the value for 5 minutes
		$this->cache->set($program, $result, 300);
		return $result;
	}
}
