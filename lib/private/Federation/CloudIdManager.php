<?php
/**
 * @copyright Copyright (c) 2017, Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Federation;

use OCP\Federation\ICloudId;
use OCP\Federation\ICloudIdManager;
use OCP\Http\Client\IClientService;
use OCP\ICache;

class CloudIdManager implements ICloudIdManager {
	/** @var IClientService */
	private $clientService;

	/** @var ICache */
	private $cache;

	const CACHE_TTL = 5 * 60;

	/**
	 * CloudIdManager constructor.
	 *
	 * @param IClientService $clientService
	 * @param ICache $cache
	 */
	public function __construct($clientService, $cache) {
		$this->clientService = $clientService;
		$this->cache = $cache;
	}

	/**
	 * @param string $cloudId
	 * @return ICloudId
	 * @throws \InvalidArgumentException
	 */
	public function resolveCloudId($cloudId) {
		// TODO magic here to get the url and user instead of just splitting on @

		if (!$this->isValidCloudId($cloudId)) {
			throw new \InvalidArgumentException('Invalid cloud id');
		}

		// Find the first character that is not allowed in user names
		$id = $this->fixRemoteURL($cloudId);
		$posSlash = strpos($id, '/');
		$posColon = strpos($id, ':');

		if ($posSlash === false && $posColon === false) {
			$invalidPos = strlen($id);
		} else if ($posSlash === false) {
			$invalidPos = $posColon;
		} else if ($posColon === false) {
			$invalidPos = $posSlash;
		} else {
			$invalidPos = min($posSlash, $posColon);
		}

		// Find the last @ before $invalidPos
		$pos = $lastAtPos = 0;
		while ($lastAtPos !== false && $lastAtPos <= $invalidPos) {
			$pos = $lastAtPos;
			$lastAtPos = strpos($id, '@', $pos + 1);
		}

		if ($pos !== false) {
			$user = substr($id, 0, $pos);
			$remote = substr($id, $pos + 1);

			$wellKnownResult = $this->resolveWellKnown($remote, $id);
			if ($wellKnownResult instanceof ICloudId) {
				return $wellKnownResult;
			}

			if (!empty($user) && !empty($remote)) {
				return new CloudId($id, $user, $remote);
			}
		}
		throw new \InvalidArgumentException('Invalid cloud id');
	}

	/**
	 * @param string $user
	 * @param string $remote
	 * @return CloudId
	 */
	public function getCloudId($user, $remote) {
		// TODO check what the correct url is for remote (asking the remote)
		return new CloudId($user . '@' . $remote, $user, $remote);
	}

	/**
	 * Strips away a potential file names and trailing slashes:
	 * - http://localhost
	 * - http://localhost/
	 * - http://localhost/index.php
	 * - http://localhost/index.php/s/{shareToken}
	 *
	 * all return: http://localhost
	 *
	 * @param string $remote
	 * @return string
	 */
	protected function fixRemoteURL($remote) {
		$remote = str_replace('\\', '/', $remote);
		if ($fileNamePosition = strpos($remote, '/index.php')) {
			$remote = substr($remote, 0, $fileNamePosition);
		}
		$remote = rtrim($remote, '/');

		return $remote;
	}

	/**
	 * @param string $cloudId
	 * @return bool
	 */
	public function isValidCloudId($cloudId) {
		return strpos($cloudId, '@') !== false;
	}

	private function resolveWellKnown($remote, $id) {
		$cachedEntry = $this->cache->get($id);
		if ($cachedEntry === false) {
			return false;
		}
		if (is_array($cachedEntry)) {
			return new CloudId($id, $cachedEntry['user'], $cachedEntry['remote']);
		}

		if (strpos($remote, '://')) {
			$result = $this->tryWellKnown($remote, $id);
		} else {
			$result = $this->tryWellKnown('https://' . $remote, $id);
			if (!$result) {
				$result = $this->tryWellKnown('http://' . $remote, $id);
			}
		}

		if ($result && $result['found']) {
			$this->cache->set($id, $result, self::CACHE_TTL);
			return new CloudId($id, $result['user'], $result['remote']);
		} else {
			$this->cache->set($id, false, self::CACHE_TTL);
			return false;
		}
	}

	private function tryWellKnown($remote, $id) {
		$client = $this->clientService->newClient();
		if (!$client) {
			return false;
		}
		try {
			$response = $client->get($remote . '/.well-known/cloud-id', [
				'query' => [
					'id' => $id
				]
			]);
			if (!$response) {
				return false;
			}
			$body = $response->getBody();
			return @json_decode($body, true);
		} catch (\Exception $e) {
			return false;
		}
	}
}
