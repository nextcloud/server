<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\External;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use OC\Files\Storage\DAV;
use OC\ForbiddenException;
use OC\Share\Share;
use OCA\Files_Sharing\External\Manager as ExternalShareManager;
use OCA\Files_Sharing\ISharedStorage;
use OCP\AppFramework\Http;
use OCP\Constants;
use OCP\Federation\ICloudId;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\IScanner;
use OCP\Files\Cache\IWatcher;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IDisableEncryptionStorage;
use OCP\Files\Storage\IReliableEtagStorage;
use OCP\Files\Storage\IStorage;
use OCP\Files\StorageInvalidException;
use OCP\Files\StorageNotAvailableException;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\LocalServerException;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\OCM\Exceptions\OCMArgumentException;
use OCP\OCM\Exceptions\OCMProviderException;
use OCP\OCM\IOCMDiscoveryService;
use OCP\Server;
use OCP\Util;
use Psr\Log\LoggerInterface;

class Storage extends DAV implements ISharedStorage, IDisableEncryptionStorage, IReliableEtagStorage {
	private ICloudId $cloudId;
	private string $mountPoint;
	private string $token;
	private ICacheFactory $memcacheFactory;
	private IClientService $httpClient;
	private bool $updateChecked = false;
	private ExternalShareManager $manager;
	private IConfig $config;

	/**
	 * @param array{HttpClientService: IClientService, manager: ExternalShareManager, cloudId: ICloudId, mountpoint: string, token: string, password: ?string}|array $options
	 */
	public function __construct($options) {
		$this->memcacheFactory = \OC::$server->getMemCacheFactory();
		$this->httpClient = $options['HttpClientService'];
		$this->manager = $options['manager'];
		$this->cloudId = $options['cloudId'];
		$this->logger = Server::get(LoggerInterface::class);
		$discoveryService = Server::get(IOCMDiscoveryService::class);
		$this->config = Server::get(IConfig::class);

		// use default path to webdav if not found on discovery
		try {
			$ocmProvider = $discoveryService->discover($this->cloudId->getRemote());
			$webDavEndpoint = $ocmProvider->extractProtocolEntry('file', 'webdav');
			$remote = $ocmProvider->getEndPoint();
		} catch (OCMProviderException|OCMArgumentException $e) {
			$this->logger->notice('exception while retrieving webdav endpoint', ['exception' => $e]);
			$webDavEndpoint = '/public.php/webdav';
			$remote = $this->cloudId->getRemote();
		}

		$host = parse_url($remote, PHP_URL_HOST);
		$port = parse_url($remote, PHP_URL_PORT);
		$host .= ($port === null) ? '' : ':' . $port; // we add port if available

		// in case remote NC is on a sub folder and using deprecated ocm provider
		$tmpPath = rtrim(parse_url($this->cloudId->getRemote(), PHP_URL_PATH) ?? '', '/');
		if (!str_starts_with($webDavEndpoint, $tmpPath)) {
			$webDavEndpoint = $tmpPath . $webDavEndpoint;
		}

		$this->mountPoint = $options['mountpoint'];
		$this->token = $options['token'];

		parent::__construct(
			[
				'secure' => ((parse_url($remote, PHP_URL_SCHEME) ?? 'https') === 'https'),
				'host' => $host,
				'root' => $webDavEndpoint,
				'user' => $options['token'],
				'authType' => \Sabre\DAV\Client::AUTH_BASIC,
				'password' => (string)$options['password']
			]
		);
	}

	public function getWatcher(string $path = '', ?IStorage $storage = null): IWatcher {
		if (!$storage) {
			$storage = $this;
		}
		if (!isset($this->watcher)) {
			$this->watcher = new Watcher($storage);
			$this->watcher->setPolicy(\OC\Files\Cache\Watcher::CHECK_ONCE);
		}
		return $this->watcher;
	}

	public function getRemoteUser(): string {
		return $this->cloudId->getUser();
	}

	public function getRemote(): string {
		return $this->cloudId->getRemote();
	}

	public function getMountPoint(): string {
		return $this->mountPoint;
	}

	public function getToken(): string {
		return $this->token;
	}

	public function getPassword(): ?string {
		return $this->password;
	}

	public function getId(): string {
		return 'shared::' . md5($this->token . '@' . $this->getRemote());
	}

	public function getCache(string $path = '', ?IStorage $storage = null): ICache {
		if (is_null($this->cache)) {
			$this->cache = new Cache($this, $this->cloudId);
		}
		return $this->cache;
	}

	public function getScanner(string $path = '', ?IStorage $storage = null): IScanner {
		if (!$storage) {
			$storage = $this;
		}
		if (!isset($this->scanner)) {
			$this->scanner = new Scanner($storage);
		}
		/** @var Scanner */
		return $this->scanner;
	}

	public function hasUpdated(string $path, int $time): bool {
		// since for owncloud webdav servers we can rely on etag propagation we only need to check the root of the storage
		// because of that we only do one check for the entire storage per request
		if ($this->updateChecked) {
			return false;
		}
		$this->updateChecked = true;
		try {
			return parent::hasUpdated('', $time);
		} catch (StorageInvalidException $e) {
			// check if it needs to be removed
			$this->checkStorageAvailability();
			throw $e;
		} catch (StorageNotAvailableException $e) {
			// check if it needs to be removed or just temp unavailable
			$this->checkStorageAvailability();
			throw $e;
		}
	}

	public function test(): bool {
		try {
			return parent::test();
		} catch (StorageInvalidException $e) {
			// check if it needs to be removed
			$this->checkStorageAvailability();
			throw $e;
		} catch (StorageNotAvailableException $e) {
			// check if it needs to be removed or just temp unavailable
			$this->checkStorageAvailability();
			throw $e;
		}
	}

	/**
	 * Check whether this storage is permanently or temporarily
	 * unavailable
	 *
	 * @throws StorageNotAvailableException
	 * @throws StorageInvalidException
	 */
	public function checkStorageAvailability() {
		// see if we can find out why the share is unavailable
		try {
			$this->getShareInfo(0);
		} catch (NotFoundException $e) {
			// a 404 can either mean that the share no longer exists or there is no Nextcloud on the remote
			if ($this->testRemote()) {
				// valid Nextcloud instance means that the public share no longer exists
				// since this is permanent (re-sharing the file will create a new token)
				// we remove the invalid storage
				$this->manager->removeShare($this->mountPoint);
				$this->manager->getMountManager()->removeMount($this->mountPoint);
				throw new StorageInvalidException('Remote share not found', 0, $e);
			} else {
				// Nextcloud instance is gone, likely to be a temporary server configuration error
				throw new StorageNotAvailableException('No nextcloud instance found at remote', 0, $e);
			}
		} catch (ForbiddenException $e) {
			// auth error, remove share for now (provide a dialog in the future)
			$this->manager->removeShare($this->mountPoint);
			$this->manager->getMountManager()->removeMount($this->mountPoint);
			throw new StorageInvalidException('Auth error when getting remote share');
		} catch (\GuzzleHttp\Exception\ConnectException $e) {
			throw new StorageNotAvailableException('Failed to connect to remote instance', 0, $e);
		} catch (\GuzzleHttp\Exception\RequestException $e) {
			throw new StorageNotAvailableException('Error while sending request to remote instance', 0, $e);
		}
	}

	public function file_exists(string $path): bool {
		if ($path === '') {
			return true;
		} else {
			return parent::file_exists($path);
		}
	}

	/**
	 * Check if the configured remote is a valid federated share provider
	 *
	 * @return bool
	 */
	protected function testRemote(): bool {
		try {
			return $this->testRemoteUrl($this->getRemote() . '/ocm-provider/index.php')
				   || $this->testRemoteUrl($this->getRemote() . '/ocm-provider/')
				   || $this->testRemoteUrl($this->getRemote() . '/status.php');
		} catch (\Exception $e) {
			return false;
		}
	}

	private function testRemoteUrl(string $url): bool {
		$cache = $this->memcacheFactory->createDistributed('files_sharing_remote_url');
		$cached = $cache->get($url);
		if ($cached !== null) {
			return (bool)$cached;
		}

		$client = $this->httpClient->newClient();
		try {
			$result = $client->get($url, $this->getDefaultRequestOptions())->getBody();
			$data = json_decode($result);
			$returnValue = (is_object($data) && !empty($data->version));
		} catch (ConnectException|ClientException|RequestException $e) {
			$returnValue = false;
			$this->logger->warning('Failed to test remote URL', ['exception' => $e]);
		}

		$cache->set($url, $returnValue, 60 * 60 * 24);
		return $returnValue;
	}

	/**
	 * Check whether the remote is an ownCloud/Nextcloud. This is needed since some sharing
	 * features are not standardized.
	 *
	 * @throws LocalServerException
	 */
	public function remoteIsOwnCloud(): bool {
		if (defined('PHPUNIT_RUN') || !$this->testRemoteUrl($this->getRemote() . '/status.php')) {
			return false;
		}
		return true;
	}

	/**
	 * @return mixed
	 * @throws ForbiddenException
	 * @throws NotFoundException
	 * @throws \Exception
	 */
	public function getShareInfo(int $depth = -1) {
		$remote = $this->getRemote();
		$token = $this->getToken();
		$password = $this->getPassword();

		try {
			// If remote is not an ownCloud do not try to get any share info
			if (!$this->remoteIsOwnCloud()) {
				return ['status' => 'unsupported'];
			}
		} catch (LocalServerException $e) {
			// throw this to be on the safe side: the share will still be visible
			// in the UI in case the failure is intermittent, and the user will
			// be able to decide whether to remove it if it's really gone
			throw new StorageNotAvailableException();
		}

		$url = rtrim($remote, '/') . '/index.php/apps/files_sharing/shareinfo?t=' . $token;

		// TODO: DI
		$client = \OC::$server->getHTTPClientService()->newClient();
		try {
			$response = $client->post($url, array_merge($this->getDefaultRequestOptions(), [
				'body' => ['password' => $password, 'depth' => $depth],
			]));
		} catch (\GuzzleHttp\Exception\RequestException $e) {
			$this->logger->warning('Failed to fetch share info', ['exception' => $e]);
			if ($e->getCode() === Http::STATUS_UNAUTHORIZED || $e->getCode() === Http::STATUS_FORBIDDEN) {
				throw new ForbiddenException();
			}
			if ($e->getCode() === Http::STATUS_NOT_FOUND) {
				throw new NotFoundException();
			}
			// throw this to be on the safe side: the share will still be visible
			// in the UI in case the failure is intermittent, and the user will
			// be able to decide whether to remove it if it's really gone
			throw new StorageNotAvailableException();
		}

		return json_decode($response->getBody(), true);
	}

	public function getOwner(string $path): string|false {
		return $this->cloudId->getDisplayId();
	}

	public function isSharable(string $path): bool {
		if (Util::isSharingDisabledForUser() || !Share::isResharingAllowed()) {
			return false;
		}
		return (bool)($this->getPermissions($path) & Constants::PERMISSION_SHARE);
	}

	public function getPermissions(string $path): int {
		$response = $this->propfind($path);
		if ($response === false) {
			return 0;
		}

		$ocsPermissions = $response['{http://open-collaboration-services.org/ns}share-permissions'] ?? null;
		$ocmPermissions = $response['{http://open-cloud-mesh.org/ns}share-permissions'] ?? null;
		$ocPermissions = $response['{http://owncloud.org/ns}permissions'] ?? null;
		// old federated sharing permissions
		if ($ocsPermissions !== null) {
			$permissions = (int)$ocsPermissions;
		} elseif ($ocmPermissions !== null) {
			// permissions provided by the OCM API
			$permissions = $this->ocmPermissions2ncPermissions($ocmPermissions, $path);
		} elseif ($ocPermissions !== null) {
			return $this->parsePermissions($ocPermissions);
		} else {
			// use default permission if remote server doesn't provide the share permissions
			$permissions = $this->getDefaultPermissions($path);
		}

		return $permissions;
	}

	public function needsPartFile(): bool {
		return false;
	}

	/**
	 * Translate OCM Permissions to Nextcloud permissions
	 *
	 * @param string $ocmPermissions json encoded OCM permissions
	 * @param string $path path to file
	 * @return int
	 */
	protected function ocmPermissions2ncPermissions(string $ocmPermissions, string $path): int {
		try {
			$ocmPermissions = json_decode($ocmPermissions);
			$ncPermissions = 0;
			foreach ($ocmPermissions as $permission) {
				switch (strtolower($permission)) {
					case 'read':
						$ncPermissions += Constants::PERMISSION_READ;
						break;
					case 'write':
						$ncPermissions += Constants::PERMISSION_CREATE + Constants::PERMISSION_UPDATE;
						break;
					case 'share':
						$ncPermissions += Constants::PERMISSION_SHARE;
						break;
					default:
						throw new \Exception();
				}
			}
		} catch (\Exception $e) {
			$ncPermissions = $this->getDefaultPermissions($path);
		}

		return $ncPermissions;
	}

	/**
	 * Calculate the default permissions in case no permissions are provided
	 */
	protected function getDefaultPermissions(string $path): int {
		if ($this->is_dir($path)) {
			$permissions = Constants::PERMISSION_ALL;
		} else {
			$permissions = Constants::PERMISSION_ALL & ~Constants::PERMISSION_CREATE;
		}

		return $permissions;
	}

	public function free_space(string $path): int|float|false {
		return parent::free_space('');
	}

	private function getDefaultRequestOptions(): array {
		$options = [
			'timeout' => 10,
			'connect_timeout' => 10,
		];
		if ($this->config->getSystemValueBool('sharing.federation.allowSelfSignedCertificates')) {
			$options['verify'] = false;
		}
		return $options;
	}
}
