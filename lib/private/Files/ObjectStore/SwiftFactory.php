<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
use OpenStack\Common\Auth\Token;
use OpenStack\Common\Error\BadResponseError;
use OpenStack\Common\Transport\Utils as TransportUtils;
use OpenStack\Identity\v2\Models\Catalog;
use OpenStack\Identity\v2\Service as IdentityV2Service;
use OpenStack\Identity\v3\Service as IdentityV3Service;
use OpenStack\ObjectStore\v1\Models\Container;
use OpenStack\OpenStack;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

class SwiftFactory {
	private ?Container $container = null;

	public const DEFAULT_OPTIONS = [
		'autocreate' => false,
		'urlType' => 'publicURL',
		'catalogName' => 'swift',
		'catalogType' => 'object-store'
	];

	public function __construct(
		private ICache $cache,
		private array $params,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * Gets currently cached token id
	 *
	 * @throws StorageAuthException
	 */
	public function getCachedTokenId(): string {
		if (!isset($this->params['cachedToken'])) {
			throw new StorageAuthException('Unauthenticated ObjectStore connection');
		}

		// Is it V2 token?
		if (isset($this->params['cachedToken']['token'])) {
			return $this->params['cachedToken']['token']['id'];
		}

		return $this->params['cachedToken']['id'];
	}

	private function getCachedToken(string $cacheKey) {
		$cachedTokenString = $this->cache->get($cacheKey . '/token');
		if ($cachedTokenString) {
			return json_decode($cachedTokenString, true);
		} else {
			return null;
		}
	}

	private function cacheToken(Token $token, string $serviceUrl, string $cacheKey) {
		if ($token instanceof \OpenStack\Identity\v3\Models\Token) {
			// for v3 the catalog is cached as part of the token, so no need to cache $serviceUrl separately
			$value = $token->export();
		} else {
			/** @var \OpenStack\Identity\v2\Models\Token $token */
			$value = [
				'serviceUrl' => $serviceUrl,
				'token' => [
					'issued_at' => $token->issuedAt->format('c'),
					'expires' => $token->expires->format('c'),
					'id' => $token->id,
					'tenant' => $token->tenant
				]
			];
		}

		$this->params['cachedToken'] = $value;
		$this->cache->set($cacheKey . '/token', json_encode($value));
	}

	/**
	 * @throws StorageAuthException
	 */
	private function getClient(): OpenStack {
		if (isset($this->params['bucket'])) {
			$this->params['container'] = $this->params['bucket'];
		}
		if (!isset($this->params['container'])) {
			$this->params['container'] = 'nextcloud';
		}
		if (isset($this->params['user']) && is_array($this->params['user'])) {
			$userName = $this->params['user']['name'];
		} else {
			if (!isset($this->params['username']) && isset($this->params['user'])) {
				$this->params['username'] = $this->params['user'];
			}
			$userName = $this->params['username'];
		}
		if (!isset($this->params['tenantName']) && isset($this->params['tenant'])) {
			$this->params['tenantName'] = $this->params['tenant'];
		}
		if (isset($this->params['domain'])) {
			$this->params['scope']['project']['name'] = $this->params['tenant'];
			$this->params['scope']['project']['domain']['name'] = $this->params['domain'];
		}
		$this->params = array_merge(self::DEFAULT_OPTIONS, $this->params);

		$cacheKey = $userName . '@' . $this->params['url'] . '/' . $this->params['container'];
		$token = $this->getCachedToken($cacheKey);
		$this->params['cachedToken'] = $token;

		$httpClient = new Client([
			'base_uri' => TransportUtils::normalizeUrl($this->params['url']),
			'handler' => HandlerStack::create()
		]);

		if (isset($this->params['user']) && is_array($this->params['user']) && isset($this->params['user']['name'])) {
			if (!isset($this->params['scope'])) {
				throw new StorageAuthException('Scope has to be defined for V3 requests');
			}

			return $this->auth(IdentityV3Service::factory($httpClient), $cacheKey);
		} else {
			return $this->auth(SwiftV2CachingAuthService::factory($httpClient), $cacheKey);
		}
	}

	/**
	 * @throws StorageAuthException
	 */
	private function auth(IdentityV2Service|IdentityV3Service $authService, string $cacheKey): OpenStack {
		$this->params['identityService'] = $authService;
		$this->params['authUrl'] = $this->params['url'];

		$cachedToken = $this->params['cachedToken'];
		$hasValidCachedToken = false;
		if (\is_array($cachedToken)) {
			if ($authService instanceof IdentityV3Service) {
				$token = $authService->generateTokenFromCache($cachedToken);
				if (\is_null($token->catalog)) {
					$this->logger->warning('Invalid cached token for swift, no catalog set: ' . json_encode($cachedToken));
				} elseif ($token->hasExpired()) {
					$this->logger->debug('Cached token for swift expired');
				} else {
					$hasValidCachedToken = true;
				}
			} else {
				try {
					/** @var \OpenStack\Identity\v2\Models\Token $token */
					$token = $authService->model(\OpenStack\Identity\v2\Models\Token::class, $cachedToken['token']);
					$now = new \DateTimeImmutable('now');
					if ($token->expires > $now) {
						$hasValidCachedToken = true;
						$this->params['v2cachedToken'] = $token;
						$this->params['v2serviceUrl'] = $cachedToken['serviceUrl'];
					} else {
						$this->logger->debug('Cached token for swift expired');
					}
				} catch (\Exception $e) {
					$this->logger->error($e->getMessage(), ['exception' => $e]);
				}
			}
		}

		if (!$hasValidCachedToken) {
			unset($this->params['cachedToken']);
			try {
				[$token, $serviceUrl] = $authService->authenticate($this->params);
				$this->cacheToken($token, $serviceUrl, $cacheKey);
			} catch (ConnectException $e) {
				throw new StorageAuthException('Failed to connect to keystone, verify the keystone url', $e);
			} catch (ClientException $e) {
				$statusCode = $e->getResponse()->getStatusCode();
				if ($statusCode === 404) {
					throw new StorageAuthException('Keystone not found while connecting to object storage, verify the keystone url', $e);
				} elseif ($statusCode === 412) {
					throw new StorageAuthException('Precondition failed while connecting to object storage, verify the keystone url', $e);
				} elseif ($statusCode === 401) {
					throw new StorageAuthException('Authentication failed while connecting to object storage, verify the username, password and possibly tenant', $e);
				} else {
					throw new StorageAuthException('Unknown error while connecting to object storage', $e);
				}
			} catch (RequestException $e) {
				throw new StorageAuthException('Connection reset while connecting to keystone, verify the keystone url', $e);
			}
		}


		$client = new OpenStack($this->params);

		return $client;
	}

	/**
	 * @throws StorageAuthException
	 * @throws StorageNotAvailableException
	 */
	public function getContainer(): Container {
		if (is_null($this->container)) {
			$this->container = $this->createContainer();
		}

		return $this->container;
	}

	/**
	 * @throws StorageAuthException
	 * @throws StorageNotAvailableException
	 */
	private function createContainer(): Container {
		$client = $this->getClient();
		$objectStoreService = $client->objectStoreV1();

		$autoCreate = isset($this->params['autocreate']) && $this->params['autocreate'] === true;
		try {
			$container = $objectStoreService->getContainer($this->params['container']);
			if ($autoCreate) {
				$container->getMetadata();
			}
			return $container;
		} catch (BadResponseError $ex) {
			// if the container does not exist and autocreate is true try to create the container on the fly
			if ($ex->getResponse()->getStatusCode() === 404 && $autoCreate) {
				return $objectStoreService->createContainer([
					'name' => $this->params['container']
				]);
			} else {
				throw new StorageNotAvailableException('Invalid response while trying to get container info', StorageNotAvailableException::STATUS_ERROR, $ex);
			}
		} catch (ConnectException $e) {
			/** @var RequestInterface $request */
			$request = $e->getRequest();
			$host = $request->getUri()->getHost() . ':' . $request->getUri()->getPort();
			$this->logger->error("Can't connect to object storage server at $host", ['exception' => $e]);
			throw new StorageNotAvailableException("Can't connect to object storage server at $host", StorageNotAvailableException::STATUS_ERROR, $e);
		}
	}
}
