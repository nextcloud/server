<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
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
 *
 */

namespace OC\Files\ObjectStore;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use OCP\Files\StorageAuthException;
use OCP\Files\StorageNotAvailableException;
use OCP\ICache;
use OpenStack\Common\Error\BadResponseError;
use OpenStack\Identity\v2\Models\Token;
use OpenStack\Identity\v2\Service;
use OpenStack\OpenStack;
use OpenStack\Common\Transport\Utils as TransportUtils;
use Psr\Http\Message\RequestInterface;

class SwiftConnectionFactory {
	private $cache;

	public function __construct(ICache $cache) {
		$this->cache = $cache;
	}

	private function getCachedToken(string $cacheKey) {
		$cachedTokenString = $this->cache->get($cacheKey . '/token');
		if ($cachedTokenString) {
			return json_decode($cachedTokenString);
		} else {
			return null;
		}
	}

	private function cacheToken(Token $token, string $cacheKey) {
		$this->cache->set($cacheKey . '/token', json_encode($token));
	}

	/**
	 * @param array $params
	 * @return OpenStack
	 * @throws StorageAuthException
	 * @throws \Exception
	 */
	private function getClient(array &$params) {
		if (isset($params['bucket'])) {
			$params['container'] = $params['bucket'];
		}
		if (!isset($params['container'])) {
			$params['container'] = 'owncloud';
		}
		if (!isset($params['autocreate'])) {
			// should only be true for tests
			$params['autocreate'] = false;
		}

		$cacheKey = $params['username'] . '@' . $params['url'] . '/' . $params['bucket'];
		$token = $this->getCachedToken($cacheKey);
		$hasToken = is_array($token) && (new \DateTimeImmutable($token['expires_at'])) > (new \DateTimeImmutable('now'));
		if ($hasToken) {
			$params['cachedToken'] = $token;
		}
		$httpClient = new Client([
			'base_uri' => TransportUtils::normalizeUrl($params['url']),
			'handler' => HandlerStack::create()
		]);

		$authService = Service::factory($httpClient);
		$params['identityService'] = $authService;
		$params['authUrl'] = $params['url'];
		$client = new OpenStack($params);

		if (!$hasToken) {
			try {
				$token = $authService->generateToken($params);
				$this->cacheToken($token, $cacheKey);
			} catch (ConnectException $e) {
				throw new StorageAuthException('Failed to connect to keystone, verify the keystone url', $e);
			} catch (ClientException $e) {
				$statusCode = $e->getResponse()->getStatusCode();
				if ($statusCode === 404) {
					throw new StorageAuthException('Keystone not found, verify the keystone url', $e);
				} else if ($statusCode === 412) {
					throw new StorageAuthException('Precondition failed, verify the keystone url', $e);
				} else if ($statusCode === 401) {
					throw new StorageAuthException('Authentication failed, verify the username, password and possibly tenant', $e);
				} else {
					throw new StorageAuthException('Unknown error', $e);
				}
			} catch (RequestException $e) {
				throw new StorageAuthException('Connection reset while connecting to keystone, verify the keystone url', $e);
			}
		}

		return $client;
	}

	/**
	 * @param array $params
	 * @return \OpenStack\ObjectStore\v1\Models\Container
	 * @throws StorageAuthException
	 * @throws \Exception
	 */
	public function getContainer(array $params) {

		$client = $this->getClient($params);
		$objectStoreService = $client->objectStoreV1();

		$autoCreate = isset($params['autocreate']) && $params['autocreate'] === true;
		try {
			$container = $objectStoreService->getContainer($params['container']);
			if ($autoCreate) {
				$container->getMetadata();
			}
			return $container;
		} catch (BadResponseError $ex) {
			// if the container does not exist and autocreate is true try to create the container on the fly
			if ($ex->getResponse()->getStatusCode() === 404 && $autoCreate) {
				return $objectStoreService->createContainer([
					'name' => $params['container']
				]);
			} else {
				throw $ex;
			}
		} catch (ConnectException $e) {
			/** @var RequestInterface $request */
			$request = $e->getRequest();
			$host = $request->getUri()->getHost() . ':' . $request->getUri()->getPort();
			\OC::$server->getLogger()->error("Can't connect to object storage server at $host");
			throw new StorageNotAvailableException("Can't connect to object storage server at $host", StorageNotAvailableException::STATUS_ERROR, $e);
		}
	}
}
