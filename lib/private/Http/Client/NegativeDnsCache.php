<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021, Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Http\Client;

use OCP\ICache;
use OCP\ICacheFactory;

class NegativeDnsCache {
	/** @var ICache */
	private $cache;

	public function __construct(ICacheFactory $memcache) {
		$this->cache = $memcache->createLocal('NegativeDnsCache');
	}

	private function createCacheKey(string $domain, int $type) : string {
		return $domain . "-" . (string)$type;
	}

	public function setNegativeCacheForDnsType(string $domain, int $type, int $ttl) : void {
		$this->cache->set($this->createCacheKey($domain, $type), "true", $ttl);
	}

	public function isNegativeCached(string $domain, int $type) : bool {
		return (bool)$this->cache->hasKey($this->createCacheKey($domain, $type));
	}
}
