<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\App\AppStore\Fetcher;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use OC\Files\AppData\Factory;
use OCP\AppFramework\Http;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\GenericFileException;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\Server;
use OCP\ServerVersion;
use OCP\Support\Subscription\IRegistry;
use Psr\Log\LoggerInterface;

/**
 * Base class for fetching app store data
 *
 * @template T of array
 */
abstract class Fetcher {
	public const INVALIDATE_AFTER_SECONDS = 3600;
	public const INVALIDATE_AFTER_SECONDS_UNSTABLE = 900;
	public const RETRY_AFTER_FAILURE_SECONDS = 300;
	/**
	 * Maximum age of same-version cache data eligible for refresh failure fallback.
	 */
	public const MAX_STALE_SECONDS = 7 * 24 * 60 * 60;
	public const APP_STORE_URL = 'https://apps.nextcloud.com/api/v1';

	protected IAppData $appData;
	protected string $fileName;
	protected string $endpointName;
	protected ?string $version = null;
	protected ?string $channel = null;

	public function __construct(
		Factory $appDataFactory,
		protected IClientService $clientService,
		protected ITimeFactory $timeFactory,
		protected IConfig $config,
		protected LoggerInterface $logger,
		protected IRegistry $registry,
	) {
		$this->appData = $appDataFactory->get('appstore');
	}

	/**
	 * Fetches and validates the response from the App Store server.
	 *
	 * A successful response contains a list of App Store entries and cache
	 * metadata. A suppressed, failed, or invalid refresh returns an empty
	 * array, allowing get() to consider an eligible stale cache.
	 *
	 * @param string $ETag          The ETag of the cached response, if available.
	 * @param string $content       The serialized cached response data used for a
	 *                              304 Not Modified response.
	 * @param bool   $allowUnstable Whether unstable releases should be requested.
	 *
	 * @return array{
	 *     data: list<T>,
	 *     ETag?: string,
	 *     timestamp: int,
	 *     ncversion: string
	 * }|array<never, never>
	 */
	protected function fetch(string $ETag, string $content, bool $allowUnstable = false): array {
		$appstoreEnabled = $this->config->getSystemValueBool('appstoreenabled', true);
		if (!$appstoreEnabled) {
			return [];
		}

		$lastFailure = (int)$this->config->getAppValue('settings', 'appstore-fetcher-lastFailure', '0');
		if ($lastFailure > (time() - self::RETRY_AFTER_FAILURE_SECONDS)) {
			return [];
		}

		$options = [
			'timeout' => (int)$this->config->getAppValue('settings', 'appstore-timeout', '120')
		];

		if ($ETag !== '') {
			$options['headers'] = [
				'If-None-Match' => $ETag,
			];
		}

		$appStoreUrl = $this->config->getSystemValueString('appstoreurl', self::APP_STORE_URL);
		if ($appStoreUrl === self::APP_STORE_URL && $this->registry->delegateHasValidSubscription()) {
			$subscriptionKey = $this->config->getAppValue('support', 'subscription_key');

			if ($subscriptionKey) {
				$options['headers'] ??= [];
				$options['headers']['X-NC-Subscription-Key'] = $subscriptionKey;
			}
		}

		$client = $this->clientService->newClient();
		try {
			$response = $client->get($this->getEndpoint(), $options);
		} catch (ConnectException|ClientException|ServerException $e) {
			$this->config->setAppValue('settings', 'appstore-fetcher-lastFailure', (string)time());
			$this->logger->error('Failed to connect to the app store', ['exception' => $e]);
			return [];
		}

		$responseJson = [];
		if ($response->getStatusCode() === Http::STATUS_NOT_MODIFIED) {
			// Reuse the locally cached data after the server confirms the ETag is unchanged.
			$decoded = json_decode($content, true);
			if (!is_array($decoded) || !array_is_list($decoded)) {
				return [];
			}

			/** @var list<T> $decoded */
			$responseJson['data'] = $decoded;
		} else {
			$decoded = json_decode($response->getBody(), true);
			if (!is_array($decoded) || !array_is_list($decoded)) {
				return [];
			}

			/** @var list<T> $decoded */
			$responseJson['data'] = $decoded;
			$ETag = $response->getHeader('ETag');
		}

		$this->config->deleteAppValue('settings', 'appstore-fetcher-lastFailure');

		$responseJson['timestamp'] = $this->timeFactory->getTime();
		$responseJson['ncversion'] = $this->getVersion();
		if ($ETag !== '') {
			$responseJson['ETag'] = $ETag;
		}

		return $responseJson;
	}

	/**
	 * Returns App Store entries, using the cache when appropriate.
	 *
	 * Fresh, same-version cache data is returned immediately. When refreshing
	 * stale cache data fails, valid same-version data may be used as a
	 * fallback while it is no older than MAX_STALE_SECONDS.
	 *
	 * Cache data from another Nextcloud version, missing or invalid cache
	 * data, and cache data older than MAX_STALE_SECONDS are not used as
	 * fallbacks.
	 *
	 * A valid empty response from the App Store is returned and written to
	 * the cache as an empty list; invalid responses are treated as refresh
	 * failures.
	 *
	 * @param bool $allowUnstable Whether unstable releases should be included
	 * @return list<T>
	 */
	public function get(bool $allowUnstable = false): array {
		$appstoreEnabled = $this->config->getSystemValueBool('appstoreenabled', true);
		if (!$appstoreEnabled) {
			$this->logger->info('The appstore is disabled', ['app' => 'appstoreFetcher']);
			return [];
		}

		$internetAvailable = $this->config->getSystemValueBool('has_internet_connection', true);
		$appStoreUrl = $this->config->getSystemValueString('appstoreurl', self::APP_STORE_URL);
		if (!$internetAvailable && $appStoreUrl === self::APP_STORE_URL) {
			$this->logger->info(
				'The default app store cannot be accessed since Internet connectivity is disabled on this instance',
				['app' => 'appstoreFetcher']
			);
			return [];
		}

		$ETag = '';
		$content = '';
		/** @var ?list<T> $sameVersionCachedData */
		$sameVersionCachedData = null;
		$sameVersionCacheTimestamp = null;

		$rootFolder = $this->appData->getFolder('/');
		try {
			// Read the existing cache file.
			$file = $rootFolder->getFile($this->fileName);
			$jsonBlob = json_decode($file->getContent(), true);

			if (is_array($jsonBlob)) {
				// Only use cache data generated for the current Nextcloud version.
				if (isset($jsonBlob['ncversion']) && $jsonBlob['ncversion'] === $this->getVersion()) {
					if (
						isset($jsonBlob['data'])
						&& is_array($jsonBlob['data'])
						&& array_is_list($jsonBlob['data'])
					) {
						/** @var list<T> $cachedData */
						$cachedData = $jsonBlob['data'];
						$sameVersionCachedData = $cachedData;
					}

					if (isset($jsonBlob['timestamp']) && is_numeric($jsonBlob['timestamp'])) {
						$sameVersionCacheTimestamp = (int)$jsonBlob['timestamp'];
					}

					// If the timestamp is older than 3600 seconds request the files new
					$invalidateAfterSeconds = self::INVALIDATE_AFTER_SECONDS;

					if ($allowUnstable) {
						$invalidateAfterSeconds = self::INVALIDATE_AFTER_SECONDS_UNSTABLE;
					}

					if (
						$sameVersionCachedData !== null
						&& $sameVersionCacheTimestamp !== null
						&& $sameVersionCacheTimestamp > ($this->timeFactory->getTime() - $invalidateAfterSeconds)
					) {
						$this->logger->debug('Using still fresh appstore cache file', ['app' => 'appstoreFetcher']);
						return $sameVersionCachedData;
					}

					// Reuse the ETag only when valid same-version cached data is available.
					if ($sameVersionCachedData !== null && isset($jsonBlob['ETag'])) {
						$ETag = $jsonBlob['ETag'];
						try {
							$content = json_encode($sameVersionCachedData, JSON_THROW_ON_ERROR);
						} catch (\JsonException $e) {
							$this->logger->warning(
								'Could not re-encode cached appstore data for conditional request',
								['app' => 'appstoreFetcher', 'exception' => $e]
							);
							$ETag = '';
							$content = '';
						}
					}
				}
			}
		} catch (NotFoundException $e) {
			// Create the cache file when it does not already exist.
			$file = $rootFolder->newFile($this->fileName);
		} catch (GenericFileException $e) {
			try {
				$file->delete();
			} catch (\Exception) {
				$this->logger->error(
					'Could not read appstore cache file',
					['app' => 'appstoreFetcher', 'exception' => $e]
				);
				return [];
			}
			$this->logger->warning(
				'Could not read appstore cache file, it will be refreshed',
				['app' => 'appstoreFetcher', 'exception' => $e]
			);
			$file = $rootFolder->newFile($this->fileName);
		}

		try {
			$responseJson = $this->fetch($ETag, $content, $allowUnstable);

			// An empty list is a valid successful response. Missing or invalid response
			// data is treated as a failed refresh and falls back to eligible cached data.
			if (
				!isset($responseJson['data'])
				|| !is_array($responseJson['data'])
				|| !array_is_list($responseJson['data'])
			) {
				return $this->useCachedData($sameVersionCachedData, $sameVersionCacheTimestamp);
			}

			/** @var list<T> $responseData */
			$responseData = $responseJson['data'];

			try {
				$file->putContent(json_encode($responseJson, JSON_THROW_ON_ERROR));
			} catch (\Exception $e) {
				// Return fresh data even when updating the cache fails, but log for admin visibility.
				$this->logger->warning(
					'Could not write appstore cache file: ' . $e->getMessage(),
					['app' => 'appstoreFetcher']
				);
			}

			return $responseData;
		} catch (ConnectException $e) {
			// Handle connection exceptions that escape an overridden or future fetch().
			$this->logger->warning(
				'Could not connect to appstore: ' . $e->getMessage(),
				['app' => 'appstoreFetcher']
			);

			return $this->useCachedData($sameVersionCachedData, $sameVersionCacheTimestamp);
		} catch (\Exception $e) {
			$this->logger->warning($e->getMessage(), [
				'exception' => $e,
				'app' => 'appstoreFetcher',
			]);

			return $this->useCachedData($sameVersionCachedData, $sameVersionCacheTimestamp);
		}
	}

	/**
	 * @param ?list<T> $sameVersionCachedData
	 * @param ?int $sameVersionCacheTimestamp
	 * @return list<T>
	 */
	private function useCachedData(?array $sameVersionCachedData, ?int $sameVersionCacheTimestamp): array {
		$now = $this->timeFactory->getTime();

		if ($sameVersionCachedData === null || $sameVersionCacheTimestamp === null) {
			return [];
		}

		if ($sameVersionCacheTimestamp >= ($now - self::MAX_STALE_SECONDS)) {
			$this->logger->warning(
				'Could not refresh appstore cache, using stale data',
				['app' => 'appstoreFetcher']
			);

			return $sameVersionCachedData;
		}

		$this->logger->warning(
			'Could not refresh appstore cache and cached data is too old',
			[
				'app' => 'appstoreFetcher',
				'cacheAge' => $now - $sameVersionCacheTimestamp,
			]
		);

		return [];
	}

	/**
	 * Get the currently Nextcloud version
	 * @return string
	 */
	protected function getVersion() {
		if ($this->version === null) {
			$this->version = $this->config->getSystemValueString('version', '0.0.0');
		}
		return $this->version;
	}

	/**
	 * Set the current Nextcloud version
	 * @param string $version
	 */
	public function setVersion(string $version) {
		$this->version = $version;
	}

	/**
	 * Get the currently Nextcloud update channel
	 * @return string
	 */
	protected function getChannel() {
		if ($this->channel === null) {
			$this->channel = Server::get(ServerVersion::class)->getChannel();
		}
		return $this->channel;
	}

	/**
	 * Set the current Nextcloud update channel
	 * @param string $channel
	 */
	public function setChannel(string $channel) {
		$this->channel = $channel;
	}

	protected function getEndpoint(): string {
		return $this->config->getSystemValueString('appstoreurl', 'https://apps.nextcloud.com/api/v1') . '/' . $this->endpointName;
	}
}
