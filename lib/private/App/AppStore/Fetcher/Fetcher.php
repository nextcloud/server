<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\App\AppStore\Fetcher;

use GuzzleHttp\Exception\ConnectException;
use OC\Files\AppData\Factory;
use OCP\AppFramework\Http;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\Server;
use OCP\ServerVersion;
use OCP\Support\Subscription\IRegistry;
use Psr\Log\LoggerInterface;

abstract class Fetcher {
	public const INVALIDATE_AFTER_SECONDS = 3600;
	public const INVALIDATE_AFTER_SECONDS_UNSTABLE = 900;
	public const RETRY_AFTER_FAILURE_SECONDS = 300;
	public const APP_STORE_URL = 'https://apps.nextcloud.com/api/v1';

	/** @var IAppData */
	protected $appData;

	/** @var string */
	protected $fileName;
	/** @var string */
	protected $endpointName;
	/** @var ?string */
	protected $version = null;
	/** @var ?string */
	protected $channel = null;

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
	 * Fetches the response from the server
	 *
	 * @param string $ETag
	 * @param string $content
	 *
	 * @return array
	 */
	protected function fetch($ETag, $content) {
		$appstoreenabled = $this->config->getSystemValueBool('appstoreenabled', true);
		if ((int)$this->config->getAppValue('settings', 'appstore-fetcher-lastFailure', '0') > time() - self::RETRY_AFTER_FAILURE_SECONDS) {
			return [];
		}

		if (!$appstoreenabled) {
			return [];
		}

		$options = [
			'timeout' => 60,
		];

		if ($ETag !== '') {
			$options['headers'] = [
				'If-None-Match' => $ETag,
			];
		}

		if ($this->config->getSystemValueString('appstoreurl', self::APP_STORE_URL) === self::APP_STORE_URL) {
			// If we have a valid subscription key, send it to the appstore
			$subscriptionKey = $this->config->getAppValue('support', 'subscription_key');
			if ($this->registry->delegateHasValidSubscription() && $subscriptionKey) {
				$options['headers'] ??= [];
				$options['headers']['X-NC-Subscription-Key'] = $subscriptionKey;
			}
		}

		$client = $this->clientService->newClient();
		try {
			$response = $client->get($this->getEndpoint(), $options);
		} catch (ConnectException $e) {
			$this->config->setAppValue('settings', 'appstore-fetcher-lastFailure', (string)time());
			$this->logger->error('Failed to connect to the app store', ['exception' => $e]);
			return [];
		}

		$responseJson = [];
		if ($response->getStatusCode() === Http::STATUS_NOT_MODIFIED) {
			$responseJson['data'] = json_decode($content, true);
		} else {
			$responseJson['data'] = json_decode($response->getBody(), true);
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
	 * Returns the array with the entries on the appstore server
	 *
	 * @param bool [$allowUnstable] Allow unstable releases
	 * @return array
	 */
	public function get($allowUnstable = false) {
		$appstoreenabled = $this->config->getSystemValueBool('appstoreenabled', true);
		$internetavailable = $this->config->getSystemValueBool('has_internet_connection', true);
		$isDefaultAppStore = $this->config->getSystemValueString('appstoreurl', self::APP_STORE_URL) === self::APP_STORE_URL;

		if (!$appstoreenabled || (!$internetavailable && $isDefaultAppStore)) {
			$this->logger->info('AppStore is disabled or this instance has no Internet connection to access the default app store', ['app' => 'appstoreFetcher']);
			return [];
		}

		$rootFolder = $this->appData->getFolder('/');

		$ETag = '';
		$content = '';

		try {
			// File does already exists
			$file = $rootFolder->getFile($this->fileName);
			$jsonBlob = json_decode($file->getContent(), true);

			if (is_array($jsonBlob)) {
				// No caching when the version has been updated
				if (isset($jsonBlob['ncversion']) && $jsonBlob['ncversion'] === $this->getVersion()) {
					// If the timestamp is older than 3600 seconds request the files new
					$invalidateAfterSeconds = self::INVALIDATE_AFTER_SECONDS;

					if ($allowUnstable) {
						$invalidateAfterSeconds = self::INVALIDATE_AFTER_SECONDS_UNSTABLE;
					}

					if ((int)$jsonBlob['timestamp'] > ($this->timeFactory->getTime() - $invalidateAfterSeconds)) {
						return $jsonBlob['data'];
					}

					if (isset($jsonBlob['ETag'])) {
						$ETag = $jsonBlob['ETag'];
						$content = json_encode($jsonBlob['data']);
					}
				}
			}
		} catch (NotFoundException $e) {
			// File does not already exists
			$file = $rootFolder->newFile($this->fileName);
		}

		// Refresh the file content
		try {
			$responseJson = $this->fetch($ETag, $content, $allowUnstable);

			if (empty($responseJson) || empty($responseJson['data'])) {
				return [];
			}

			$file->putContent(json_encode($responseJson));
			return json_decode($file->getContent(), true)['data'];
		} catch (ConnectException $e) {
			$this->logger->warning('Could not connect to appstore: ' . $e->getMessage(), ['app' => 'appstoreFetcher']);
			return [];
		} catch (\Exception $e) {
			$this->logger->warning($e->getMessage(), [
				'exception' => $e,
				'app' => 'appstoreFetcher',
			]);
			return [];
		}
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
