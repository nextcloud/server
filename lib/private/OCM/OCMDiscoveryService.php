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
use OCP\OCM\ICapabilityAwareOCMProvider;
use OCP\OCM\IOCMDiscoveryService;
use Psr\Log\LoggerInterface;

/**
 * @since 28.0.0
 */
class OCMDiscoveryService implements IOCMDiscoveryService {
	private ICache $cache;

	public function __construct(
		ICacheFactory $cacheFactory,
		private IClientService $clientService,
		private IConfig $config,
		private ICapabilityAwareOCMProvider $provider,
		private LoggerInterface $logger,
	) {
		$this->cache = $cacheFactory->createDistributed('ocm-discovery');
	}


	/**
	 * @param string $remote
	 * @param bool $skipCache
	 *
	 * @return ICapabilityAwareOCMProvider
	 * @throws OCMProviderException
	 */
	public function discover(string $remote, bool $skipCache = false): ICapabilityAwareOCMProvider {
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
				return $this->provider;
			} catch (JsonException|OCMProviderException $e) {
				// we ignore cache on issues
			}
		}

		$client = $this->clientService->newClient();
		try {
			$options = [
				'timeout' => 10,
				'connect_timeout' => 10,
			];
			if ($this->config->getSystemValueBool('sharing.federation.allowSelfSignedCertificates') === true) {
				$options['verify'] = false;
			}
			$response = $client->get($remote . '/ocm-provider/', $options);

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

		return $this->provider;
	}
}
