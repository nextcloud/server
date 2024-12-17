<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\OCM;

use GuzzleHttp\Exception\ConnectException;
use JsonException;
use OCP\AppFramework\Http;
use OCP\Http\Client\IClientService;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\OCM\Exceptions\OCMProviderException;
use OCP\OCM\IOCMDiscoveryService;
use OCP\OCM\IOCMProvider;
use Psr\Log\LoggerInterface;

/**
 * @since 28.0.0
 */
class OCMDiscoveryService implements IOCMDiscoveryService {
	private ICache $cache;
	private array $supportedAPIVersion =
		[
			'1.0-proposal1',
			'1.0',
			'1.1'
		];

	public function __construct(
		ICacheFactory $cacheFactory,
		private IClientService $clientService,
		private IConfig $config,
		private IOCMProvider $provider,
		private LoggerInterface $logger,
	) {
		$this->cache = $cacheFactory->createDistributed('ocm-discovery');
	}


	/**
	 * @param string $remote
	 * @param bool $skipCache
	 *
	 * @return IOCMProvider
	 * @throws OCMProviderException
	 */
	public function discover(string $remote, bool $skipCache = false): IOCMProvider {
		$remote = rtrim($remote, '/');
		if (!str_starts_with($remote, 'http://') && !str_starts_with($remote, 'https://')) {
			// if scheme not specified, we test both;
			try {
				return $this->discover('https://' . $remote, $skipCache);
			} catch (OCMProviderException|ConnectException) {
				return $this->discover('http://' . $remote, $skipCache);
			}
		}

		if (!$skipCache) {
			try {
				$cached = $this->cache->get($remote);
				if ($cached === false) {
					throw new OCMProviderException('Previous discovery failed.');
				}

				$this->provider->import(json_decode($cached ?? '', true, 8, JSON_THROW_ON_ERROR) ?? []);
				if ($this->supportedAPIVersion($this->provider->getApiVersion())) {
					return $this->provider; // if cache looks valid, we use it
				}
			} catch (JsonException|OCMProviderException $e) {
				// we ignore cache on issues
			}
		}

		$client = $this->clientService->newClient();
		try {
			$response = $client->get(
				$remote . '/ocm-provider/',
				[
					'timeout' => 10,
					'verify' => !$this->config->getSystemValueBool('sharing.federation.allowSelfSignedCertificates'),
					'connect_timeout' => 10,
				]
			);

			if ($response->getStatusCode() === Http::STATUS_OK) {
				$body = $response->getBody();
				// update provider with data returned by the request
				$this->provider->import(json_decode($body, true, 8, JSON_THROW_ON_ERROR) ?? []);
				$this->cache->set($remote, $body, 60 * 60 * 24);
			}
		} catch (JsonException|OCMProviderException $e) {
			$this->cache->set($remote, false, 5 * 60);
			throw new OCMProviderException('data returned by remote seems invalid - ' . ($body ?? ''));
		} catch (\Exception $e) {
			$this->cache->set($remote, false, 5 * 60);
			$this->logger->warning('error while discovering ocm provider', [
				'exception' => $e,
				'remote' => $remote
			]);
			throw new OCMProviderException('error while requesting remote ocm provider');
		}

		if (!$this->supportedAPIVersion($this->provider->getApiVersion())) {
			$this->cache->set($remote, false, 5 * 60);
			throw new OCMProviderException('API version not supported');
		}

		return $this->provider;
	}

	/**
	 * Check the version from remote is supported.
	 * The minor version of the API will be ignored:
	 *    1.0.1 is identified as 1.0
	 *
	 * @param string $version
	 *
	 * @return bool
	 */
	private function supportedAPIVersion(string $version): bool {
		$dot1 = strpos($version, '.');
		$dot2 = strpos($version, '.', $dot1 + 1);

		if ($dot2 > 0) {
			$version = substr($version, 0, $dot2);
		}

		return (in_array($version, $this->supportedAPIVersion));
	}
}
