<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 Bjoern Schiessle <bjoern@schiessle.org>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Maxence Lange <maxence@artificial-owl.com>
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
namespace OC\Federation;

use OC\AppFramework\Http;
use OCP\App\IAppManager;
use OCP\Federation\Exceptions\ProviderDoesNotExistsException;
use OCP\Federation\ICloudFederationNotification;
use OCP\Federation\ICloudFederationProvider;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudFederationShare;
use OCP\Federation\ICloudIdManager;
use OCP\Http\Client\IClientService;
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
		private IClientService $httpClientService,
		private ICloudIdManager $cloudIdManager,
		private IOCMDiscoveryService $discoveryService,
		private LoggerInterface $logger
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

	public function sendShare(ICloudFederationShare $share) {
		$cloudID = $this->cloudIdManager->resolveCloudId($share->getShareWith());
		try {
			$ocmProvider = $this->discoveryService->discover($cloudID->getRemote());
		} catch (OCMProviderException $e) {
			return false;
		}

		$client = $this->httpClientService->newClient();
		try {
			$response = $client->post($ocmProvider->getEndPoint() . '/shares', [
				'body' => json_encode($share->getShare()),
				'headers' => ['content-type' => 'application/json'],
				'verify' => !$this->config->getSystemValueBool('sharing.federation.allowSelfSignedCertificates', false),
				'timeout' => 10,
				'connect_timeout' => 10,
			]);

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
	 * @param string $url
	 * @param ICloudFederationNotification $notification
	 * @return array|false
	 */
	public function sendNotification($url, ICloudFederationNotification $notification) {
		try {
			$ocmProvider = $this->discoveryService->discover($url);
		} catch (OCMProviderException $e) {
			return false;
		}

		$client = $this->httpClientService->newClient();
		try {
			$response = $client->post($ocmProvider->getEndPoint() . '/notifications', [
				'body' => json_encode($notification->getMessage()),
				'headers' => ['content-type' => 'application/json'],
				'verify' => !$this->config->getSystemValueBool('sharing.federation.allowSelfSignedCertificates', false),
				'timeout' => 10,
				'connect_timeout' => 10,
			]);
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
	 * check if the new cloud federation API is ready to be used
	 *
	 * @return bool
	 */
	public function isReady() {
		return $this->appManager->isEnabledForUser('cloud_federation_api');
	}
}
