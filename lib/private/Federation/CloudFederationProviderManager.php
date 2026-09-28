<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Federation;

use NCU\Security\Signature\ISignatureManager;
use OC\AppFramework\Http;
use OC\OCM\OCMSignatoryManager;
use OCP\App\IAppManager;
use OCP\Federation\Exceptions\ProviderDoesNotExistsException;
use OCP\Federation\ICloudFederationNotification;
use OCP\Federation\ICloudFederationProvider;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudFederationShare;
use OCP\Federation\ICloudIdManager;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\OCM\Exceptions\OCMProviderException;
use OCP\OCM\IOCMDiscoveryService;
use Psr\Log\LoggerInterface;

/**
 * Class Manager
 *
 * Manage Cloud Federation Providers
 *
 * @package OC\Federation
 */
class CloudFederationProviderManager implements ICloudFederationProviderManager {
	/** @var array list of available cloud federation providers */
	private array $cloudFederationProvider = [];

	public function __construct(
		private IConfig $config,
		private IAppManager $appManager,
		private IAppConfig $appConfig,
		private IClientService $httpClientService,
		private ICloudIdManager $cloudIdManager,
		private IOCMDiscoveryService $discoveryService,
		private readonly ISignatureManager $signatureManager,
		private readonly OCMSignatoryManager $signatoryManager,
		private LoggerInterface $logger,
	) {
	}


	/**
	 * Registers an callback function which must return an cloud federation provider
	 *
	 * @param string $resourceType which resource type does the provider handles
	 * @param string $displayName user facing name of the federated share provider
	 * @param callable $callback
	 */
	public function addCloudFederationProvider($resourceType, $displayName, callable $callback) {
		$this->cloudFederationProvider[$resourceType] = [
			'resourceType' => $resourceType,
			'displayName' => $displayName,
			'callback' => $callback,
		];
	}

	/**
	 * remove cloud federation provider
	 *
	 * @param string $providerId
	 */
	public function removeCloudFederationProvider($providerId) {
		unset($this->cloudFederationProvider[$providerId]);
	}

	/**
	 * get a list of all cloudFederationProviders
	 *
	 * @return array [resourceType => ['resourceType' => $resourceType, 'displayName' => $displayName, 'callback' => callback]]
	 */
	public function getAllCloudFederationProviders() {
		return $this->cloudFederationProvider;
	}

	/**
	 * get a specific cloud federation provider
	 *
	 * @param string $resourceType
	 * @return ICloudFederationProvider
	 * @throws ProviderDoesNotExistsException
	 */
	public function getCloudFederationProvider($resourceType) {
		if (isset($this->cloudFederationProvider[$resourceType])) {
			return call_user_func($this->cloudFederationProvider[$resourceType]['callback']);
		} else {
			throw new ProviderDoesNotExistsException($resourceType);
		}
	}

	/**
	 * @deprecated 29.0.0 - Use {@see sendCloudShare()} instead and handle errors manually
	 */
	public function sendShare(ICloudFederationShare $share) {
		$cloudID = $this->cloudIdManager->resolveCloudId($share->getShareWith());
		try {
			try {
				$response = $this->postOcmPayload($cloudID->getRemote(), '/shares', json_encode($share->getShare()));
			} catch (OCMProviderException) {
				return false;
			}
			if ($response->getStatusCode() === Http::STATUS_CREATED) {
				$result = json_decode($response->getBody(), true);
				return (is_array($result)) ? $result : [];
			}
		} catch (\Exception $e) {
			$this->logger->debug($e->getMessage(), ['exception' => $e]);

			// if flat re-sharing is not supported by the remote server
			// we re-throw the exception and fall back to the old behaviour.
			// (flat re-shares has been introduced in Nextcloud 9.1)
			if ($e->getCode() === Http::STATUS_INTERNAL_SERVER_ERROR) {
				throw $e;
			}
		}

		return false;
	}

	/**
	 * @param ICloudFederationShare $share
	 * @return IResponse
	 * @throws OCMProviderException
	 */
	public function sendCloudShare(ICloudFederationShare $share): IResponse {
		$cloudID = $this->cloudIdManager->resolveCloudId($share->getShareWith());
		$client = $this->httpClientService->newClient();
		try {
			return $this->postOcmPayload($cloudID->getRemote(), '/shares', json_encode($share->getShare()), $client);
		} catch (\Throwable $e) {
			$this->logger->error('Error while sending share to federation server: ' . $e->getMessage(), ['exception' => $e]);
			try {
				return $client->getResponseFromThrowable($e);
			} catch (\Throwable $e) {
				throw new OCMProviderException($e->getMessage(), $e->getCode(), $e);
			}
		}
	}

	/**
	 * @param string $url
	 * @param ICloudFederationNotification $notification
	 * @return array|false
	 * @deprecated 29.0.0 - Use {@see sendCloudNotification()} instead and handle errors manually
	 */
	public function sendNotification($url, ICloudFederationNotification $notification) {
		try {
			try {
				$response = $this->postOcmPayload($url, '/notifications', json_encode($notification->getMessage()));
			} catch (OCMProviderException) {
				return false;
			}
			if ($response->getStatusCode() === Http::STATUS_CREATED) {
				$result = json_decode($response->getBody(), true);
				return (is_array($result)) ? $result : [];
			}
		} catch (\Exception $e) {
			// log the error and return false
			$this->logger->error('error while sending notification for federated share: ' . $e->getMessage(), ['exception' => $e]);
		}

		return false;
	}

	/**
	 * @param string $url
	 * @param ICloudFederationNotification $notification
	 * @return IResponse
	 * @throws OCMProviderException
	 */
	public function sendCloudNotification(string $url, ICloudFederationNotification $notification): IResponse {
		$client = $this->httpClientService->newClient();
		try {
			return $this->postOcmPayload($url, '/notifications', json_encode($notification->getMessage()), $client);
		} catch (\Throwable $e) {
			$this->logger->error('Error while sending notification to federation server: ' . $e->getMessage(), ['exception' => $e]);
			try {
				return $client->getResponseFromThrowable($e);
			} catch (\Throwable $e) {
				throw new OCMProviderException($e->getMessage(), $e->getCode(), $e);
			}
		}
	}

	/**
	 * check if the new cloud federation API is ready to be used
	 *
	 * @return bool
	 */
	public function isReady() {
		return $this->appManager->isEnabledForUser('cloud_federation_api');
	}

	/**
	 * @param string $cloudId
	 * @param string $uri
	 * @param string $payload
	 *
	 * @return IResponse
	 * @throws OCMProviderException
	 */
	private function postOcmPayload(string $cloudId, string $uri, string $payload, ?IClient $client = null): IResponse {
		$ocmProvider = $this->discoveryService->discover($cloudId);
		$uri = $ocmProvider->getEndPoint() . '/' . ltrim($uri, '/');
		$client = $client ?? $this->httpClientService->newClient();
		return $client->post($uri, $this->prepareOcmPayload($uri, $payload));
	}

	/**
	 * @param string $uri
	 * @param string $payload
	 *
	 * @return array
	 */
	private function prepareOcmPayload(string $uri, string $payload): array {
		$payload = array_merge($this->getDefaultRequestOptions(), ['body' => $payload]);

		if ($this->appConfig->getValueBool('core', OCMSignatoryManager::APPCONFIG_SIGN_ENFORCED, lazy: true)
			&& $this->signatoryManager->getRemoteSignatory($this->signatureManager->extractIdentityFromUri($uri)) === null) {
			return $payload;
		}

		if (!$this->appConfig->getValueBool('core', OCMSignatoryManager::APPCONFIG_SIGN_DISABLED, lazy: true)) {
			$signedPayload = $this->signatureManager->signOutgoingRequestIClientPayload(
				$this->signatoryManager,
				$payload,
				'post', $uri
			);
		}

		return $signedPayload ?? $payload;
	}

	private function getDefaultRequestOptions(): array {
		return [
			'headers' => ['content-type' => 'application/json'],
			'timeout' => 10,
			'connect_timeout' => 10,
			'verify' => !$this->config->getSystemValueBool('sharing.federation.allowSelfSignedCertificates', false),
		];
	}
}
