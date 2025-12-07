<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\OCM;

use Exception;
use GuzzleHttp\Exception\ConnectException;
use JsonException;
use NCU\Security\Signature\Exceptions\IdentityNotFoundException;
use NCU\Security\Signature\Exceptions\IncomingRequestException;
use NCU\Security\Signature\Exceptions\SignatoryException;
use NCU\Security\Signature\Exceptions\SignatoryNotFoundException;
use NCU\Security\Signature\Exceptions\SignatureException;
use NCU\Security\Signature\Exceptions\SignatureNotFoundException;
use NCU\Security\Signature\IIncomingSignedRequest;
use NCU\Security\Signature\ISignatureManager;
use OC\Core\AppInfo\ConfigLexicon;
use OC\OCM\Model\OCMProvider;
use OCP\AppFramework\Attribute\Consumable;
use OCP\AppFramework\Http;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IAppConfig;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\OCM\Events\LocalOCMDiscoveryEvent;
use OCP\OCM\Events\ResourceTypeRegisterEvent;
use OCP\OCM\Exceptions\OCMCapabilityException;
use OCP\OCM\Exceptions\OCMProviderException;
use OCP\OCM\Exceptions\OCMRequestException;
use OCP\OCM\IOCMDiscoveryService;
use OCP\OCM\IOCMProvider;
use Psr\Log\LoggerInterface;

/**
 * @since 28.0.0
 */
#[Consumable(since: '28.0.0')]
final class OCMDiscoveryService implements IOCMDiscoveryService {
	private ICache $cache;
	public const API_VERSION = '1.1.0';
	private ?IOCMProvider $localProvider = null;
	/** @var array<string, IOCMProvider> */
	private array $remoteProviders = [];

	public function __construct(
		ICacheFactory $cacheFactory,
		private IClientService $clientService,
		private IEventDispatcher $eventDispatcher,
		protected IConfig $config,
		private IAppConfig $appConfig,
		private IURLGenerator $urlGenerator,
		private readonly ISignatureManager $signatureManager,
		private readonly OCMSignatoryManager $signatoryManager,
		private LoggerInterface $logger,
	) {
		$this->cache = $cacheFactory->createDistributed('ocm-discovery');
	}


	/**
	 * @inheritDoc
	 *
	 * @param string $remote address of the remote provider
	 * @param bool $skipCache ignore cache, refresh data
	 *
	 * @return IOCMProvider
	 * @throws OCMProviderException if no valid discovery data can be returned
	 * @since 28.0.0
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
			$urls = [
				$remote . '/.well-known/ocm',
				$remote . '/ocm-provider',
			];


			foreach ($urls as $url) {
				$exception = null;
				$body = null;
				$status = null;
				try {
					$response = $client->get($url, $options);
					if ($response->getStatusCode() === Http::STATUS_OK) {
						$body = $response->getBody();
						$status = $response->getStatusCode();
						// update provider with data returned by the request
						$provider->import(json_decode($body, true, 8, JSON_THROW_ON_ERROR) ?? []);
						$this->cache->set($remote, $body, 60 * 60 * 24);
						$this->remoteProviders[$remote] = $provider;
						return $provider;
					}
				} catch (\Exception $e) {
					$this->logger->debug("Tried unsuccesfully to do discovery at: {$url}", [
						'exception' => $e,
						'remote' => $remote
					]);
					// We want to throw only the last exception
					$exception = $e;
					continue;
				}
			}
			if ($exception) {
				throw $exception;
			}

			throw new OCMProviderException('invalid remote ocm endpoint');
		} catch (JsonException|OCMProviderException) {
			$this->cache->set($remote, false, 5 * 60);
			throw new OCMProviderException('data returned by remote seems invalid - status: ' . ($status ?? '') . ' - body: ' . ($body ?? ''));
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
	 * @inheritDoc
	 *
	 * @param bool $fullDetails complete details, including public keys.
	 *                          Set to FALSE for client (capabilities) purpose.
	 *
	 * @return IOCMProvider
	 * @since 33.0.0
	 */
	public function getLocalOCMProvider(bool $fullDetails = true): IOCMProvider {
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
		$provider->setCapabilities(['invite-accepted', 'notifications', 'shares']);

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
					$provider->setSignatory($this->signatoryManager->getLocalSignatory());
				} else {
					$this->logger->debug('ocm public key feature disabled');
				}
			} catch (SignatoryException|IdentityNotFoundException $e) {
				$this->logger->warning('cannot generate local signatory', ['exception' => $e]);
			}
		}

		$event = new LocalOCMDiscoveryEvent($provider);
		$this->eventDispatcher->dispatchTyped($event);

		// deprecated since 33.0.0
		$event = new ResourceTypeRegisterEvent($provider);
		$this->eventDispatcher->dispatchTyped($event);

		$this->localProvider = $provider;
		return $provider;
	}

	/**
	 * @inheritDoc
	 *
	 * @return IIncomingSignedRequest|null null if remote does not (and never did) support signed request
	 * @throws IncomingRequestException
	 * @since 33.0.0
	 */
	public function getIncomingSignedRequest(): ?IIncomingSignedRequest {
		try {
			$signedRequest = $this->signatureManager->getIncomingSignedRequest($this->signatoryManager);
			$this->logger->debug('signed request available', ['signedRequest' => $signedRequest]);
			return $signedRequest;
		} catch (SignatureNotFoundException|SignatoryNotFoundException $e) {
			$this->logger->debug('remote does not support signed request', ['exception' => $e]);
			// remote does not support signed request.
			// currently we still accept unsigned request until lazy appconfig
			// core.enforce_signed_ocm_request is set to true (default: false)
			if ($this->appConfig->getValueBool('core', OCMSignatoryManager::APPCONFIG_SIGN_ENFORCED, lazy: true)) {
				$this->logger->notice('ignored unsigned request', ['exception' => $e]);
				throw new IncomingRequestException('Unsigned request');
			}
		} catch (SignatureException $e) {
			$this->logger->warning('wrongly signed request', ['exception' => $e]);
			throw new IncomingRequestException('Invalid signature');
		}
		return null;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string|null $capability when not NULL, method will throw
	 *                                {@see OCMCapabilityException}
	 *                                if remote does not support the capability
	 * @param string $remote remote ocm cloud id
	 * @param string $ocmSubPath path to reach, complementing the ocm endpoint extracted
	 *                           from remote discovery data
	 * @param array|null $payload payload attached to the request
	 * @param string $method method to use ('get', 'post', 'put', 'delete')
	 * @param IClient|null $client NULL to use default {@see IClient}
	 * @param array|null $options options related to IClient
	 * @param bool $signed FALSE to not auth the request
	 *
	 * @throws OCMCapabilityException if remote does not support $capability
	 * @throws OCMProviderException if remote ocm provider is disabled or invalid data returned
	 * @throws OCMRequestException on internal issue
	 * @since 33.0.0
	 */
	public function requestRemoteOcmEndpoint(
		?string $capability,
		string $remote,
		string $ocmSubPath,
		?array $payload = null,
		string $method = 'get',
		?IClient $client = null,
		?array $options = null,
		bool $signed = true,
	): IResponse {
		$ocmProvider = $this->discover($remote);
		if (!$ocmProvider->isEnabled()) {
			throw new OCMProviderException('remote ocm provider is disabled');
		}

		if ($capability !== null && !$ocmProvider->hasCapability($capability)) {
			throw new OCMCapabilityException(sprintf('remote does not support %s', $capability));
		}

		$uri = $ocmProvider->getEndPoint() . '/' . ltrim($ocmSubPath, '/');
		$client = $client ?? $this->clientService->newClient();

		try {
			$body = json_encode($payload ?? [], JSON_THROW_ON_ERROR);
		} catch (JsonException $e) {
			$this->logger->warning('payload could not be converted to JSON', ['exception' => $e]);
			throw new OCMRequestException('ocm payload issue');
		}

		try {
			$options = $options ?? [];
			return match (strtolower($method)) {
				'get' => $client->get($uri, $this->prepareOcmPayload($uri, 'get', $options, $body, $signed)),
				'post' => $client->post($uri, $this->prepareOcmPayload($uri, 'post', $options, $body, $signed)),
				'put' => $client->put($uri, $this->prepareOcmPayload($uri, 'put', $options, $body, $signed)),
				'delete' => $client->delete($uri, $this->prepareOcmPayload($uri, 'delete', $options, $body, $signed)),
				default => throw new OCMRequestException('unknown method'),
			};
		} catch (OCMRequestException $e) {
			throw $e;
		} catch (Exception $e) {
			$this->logger->warning('error while requesting remote ocm endpoint', ['exception' => $e]);
			throw new OCMProviderException('error while requesting remote endpoint');
		}
	}

	/**
	 * add entries to the payload to auth the whole request
	 *
	 * @throws OCMProviderException
	 * @return array
	 */
	private function prepareOcmPayload(string $uri, string $method, array $options, string $payload, bool $signed): array {
		$payload = array_merge($this->generateRequestOptions($options), ['body' => $payload]);
		if (!$signed) {
			return $payload;
		}

		if ($this->appConfig->getValueBool('core', OCMSignatoryManager::APPCONFIG_SIGN_ENFORCED, lazy: true)
			&& $this->signatoryManager->getRemoteSignatory($this->signatureManager->extractIdentityFromUri($uri)) === null) {
			throw new OCMProviderException('remote endpoint does not support signed request');
		}

		if (!$this->appConfig->getValueBool('core', OCMSignatoryManager::APPCONFIG_SIGN_DISABLED, lazy: true)) {
			$signedPayload = $this->signatureManager->signOutgoingRequestIClientPayload(
				$this->signatoryManager,
				$payload,
				$method, $uri
			);
		}

		return $signedPayload ?? $payload;
	}

	private function generateRequestOptions(array $options): array {
		return array_merge(
			[
				'headers' => ['content-type' => 'application/json'],
				'timeout' => 5,
				'connect_timeout' => 5,
			],
			$options
		);
	}
}
