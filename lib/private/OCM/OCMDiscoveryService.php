<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\OCM;

use GuzzleHttp\Exception\ConnectException;
use JsonException;
use NCU\Security\Signature\Exceptions\IdentityNotFoundException;
use NCU\Security\Signature\Exceptions\SignatoryException;
use OC\Core\AppInfo\ConfigLexicon;
use OC\OCM\Model\OCMProvider;
use OCP\AppFramework\Http;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Http\Client\IClientService;
use OCP\IAppConfig;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\OCM\Events\ResourceTypeRegisterEvent;
use OCP\OCM\Exceptions\OCMProviderException;
use OCP\OCM\ICapabilityAwareOCMProvider;
use OCP\OCM\IOCMDiscoveryService;
use Psr\Log\LoggerInterface;

/**
 * @since 28.0.0
 */
class OCMDiscoveryService implements IOCMDiscoveryService {
	private ICache $cache;
	public const API_VERSION = '1.1.0';

	private ?ICapabilityAwareOCMProvider $localProvider = null;
	/** @var array<string, ICapabilityAwareOCMProvider> */
	private array $remoteProviders = [];

	public function __construct(
		ICacheFactory $cacheFactory,
		private IClientService $clientService,
		private IEventDispatcher $eventDispatcher,
		protected IConfig $config,
		private IAppConfig $appConfig,
		private IURLGenerator $urlGenerator,
		private OCMSignatoryManager $ocmSignatoryManager,
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

		if (array_key_exists($remote, $this->remoteProviders)) {
			return $this->remoteProviders[$remote];
		}

		$provider = new OCMProvider();

		if (!$skipCache) {
			try {
				$cached = $this->cache->get($remote);
				if ($cached === false) {
					throw new OCMProviderException('Previous discovery failed.');
				}

				if ($cached !== null) {
					$provider->import(json_decode($cached, true, 8, JSON_THROW_ON_ERROR) ?? []);
					$this->remoteProviders[$remote] = $provider;
					return $provider;
				}
			} catch (JsonException|OCMProviderException $e) {
				$this->logger->warning('cache issue on ocm discovery', ['exception' => $e]);
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

			$body = null;
			if ($response->getStatusCode() === Http::STATUS_OK) {
				$body = $response->getBody();
				// update provider with data returned by the request
				$provider->import(json_decode($body, true, 8, JSON_THROW_ON_ERROR) ?? []);
				$this->cache->set($remote, $body, 60 * 60 * 24);
				$this->remoteProviders[$remote] = $provider;
				return $provider;
			}

			throw new OCMProviderException('invalid remote ocm endpoint');
		} catch (JsonException|OCMProviderException) {
			$this->cache->set($remote, false, 5 * 60);
			throw new OCMProviderException('data returned by remote seems invalid - status:' . $response->getStatusCode() . ' - ' . ($body ?? ''));
		} catch (\Exception $e) {
			$this->cache->set($remote, false, 5 * 60);
			$this->logger->warning('error while discovering ocm provider', [
				'exception' => $e,
				'remote' => $remote
			]);
			throw new OCMProviderException('error while requesting remote ocm provider');
		}
	}

	/**
	 * @return ICapabilityAwareOCMProvider
	 */
	public function getLocalOCMProvider(bool $fullDetails = true): ICapabilityAwareOCMProvider {
		if ($this->localProvider !== null) {
			return $this->localProvider;
		}

		$provider = new OCMProvider('Nextcloud ' . $this->config->getSystemValue('version'));
		if (!$this->appConfig->getValueBool('core', ConfigLexicon::OCM_DISCOVERY_ENABLED)) {
			return $provider;
		}

		$url = $this->urlGenerator->linkToRouteAbsolute('cloud_federation_api.requesthandlercontroller.addShare');
		$pos = strrpos($url, '/');
		if ($pos === false) {
			$this->logger->debug('generated route should contain a slash character');
			return $provider;
		}

		$provider->setEnabled(true);
		$provider->setApiVersion(self::API_VERSION);
		$provider->setEndPoint(substr($url, 0, $pos));
		$provider->setCapabilities(['/invite-accepted', '/notifications', '/shares']);

		// The inviteAcceptDialog is available from the contacts app, if this config value is set
		$inviteAcceptDialog = $this->appConfig->getValueString('core', ConfigLexicon::OCM_INVITE_ACCEPT_DIALOG);
		if ($inviteAcceptDialog !== '') {
			$provider->setInviteAcceptDialog($this->urlGenerator->linkToRouteAbsolute($inviteAcceptDialog));
		}

		$resource = $provider->createNewResourceType();
		$resource->setName('file')
			->setShareTypes(['user', 'group'])
			->setProtocols(['webdav' => '/public.php/webdav/']);
		$provider->addResourceType($resource);

		if ($fullDetails) {
			// Adding a public key to the ocm discovery
			try {
				if (!$this->appConfig->getValueBool('core', OCMSignatoryManager::APPCONFIG_SIGN_DISABLED, lazy: true)) {
					/**
					 * @experimental 31.0.0
					 * @psalm-suppress UndefinedInterfaceMethod
					 */
					$provider->setSignatory($this->ocmSignatoryManager->getLocalSignatory());
				} else {
					$this->logger->debug('ocm public key feature disabled');
				}
			} catch (SignatoryException|IdentityNotFoundException $e) {
				$this->logger->warning('cannot generate local signatory', ['exception' => $e]);
			}
		}

		$event = new ResourceTypeRegisterEvent($provider);
		$this->eventDispatcher->dispatchTyped($event);

		$this->localProvider = $provider;
		return $provider;
	}

}
