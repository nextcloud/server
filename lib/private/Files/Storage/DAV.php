<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Storage;

use Exception;
use Icewind\Streams\CallbackWrapper;
use Icewind\Streams\IteratorDirectory;
use OC\Files\Filesystem;
use OC\MemCache\ArrayCache;
use OCP\AppFramework\Http;
use OCP\Constants;
use OCP\Diagnostics\IEventLogger;
use OCP\Files\FileInfo;
use OCP\Files\ForbiddenException;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\StorageInvalidException;
use OCP\Files\StorageNotAvailableException;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\ICertificateManager;
use OCP\IConfig;
use OCP\Server;
use OCP\Util;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Client;
use Sabre\DAV\Xml\Property\ResourceType;
use Sabre\HTTP\ClientException;
use Sabre\HTTP\ClientHttpException;
use Sabre\HTTP\RequestInterface;

/**
 * Class DAV
 *
 * @package OC\Files\Storage
 */
class DAV extends Common {
	/** @var string */
	protected $password;
	/** @var string */
	protected $user;
	/** @var string|null */
	protected $authType;
	/** @var string */
	protected $host;
	/** @var bool */
	protected $secure;
	/** @var string */
	protected $root;
	/** @var string */
	protected $certPath;
	/** @var bool */
	protected $ready;
	/** @var Client */
	protected $client;
	/** @var ArrayCache */
	protected $statCache;
	/** @var IClientService */
	protected $httpClientService;
	/** @var ICertificateManager */
	protected $certManager;
	protected LoggerInterface $logger;
	protected IEventLogger $eventLogger;
	protected IMimeTypeDetector $mimeTypeDetector;

	/** @var int */
	private $timeout;

	protected const PROPFIND_PROPS = [
		'{DAV:}getlastmodified',
		'{DAV:}getcontentlength',
		'{DAV:}getcontenttype',
		'{http://owncloud.org/ns}permissions',
		'{http://open-collaboration-services.org/ns}share-permissions',
		'{DAV:}resourcetype',
		'{DAV:}getetag',
		'{DAV:}quota-available-bytes',
	];

	/**
	 * @param array $parameters
	 * @throws \Exception
	 */
	public function __construct(array $parameters) {
		$this->statCache = new ArrayCache();
		$this->httpClientService = Server::get(IClientService::class);
		if (isset($parameters['host']) && isset($parameters['user']) && isset($parameters['password'])) {
			$host = $parameters['host'];
			//remove leading http[s], will be generated in createBaseUri()
			if (str_starts_with($host, 'https://')) {
				$host = substr($host, 8);
			} elseif (str_starts_with($host, 'http://')) {
				$host = substr($host, 7);
			}
			$this->host = $host;
			$this->user = $parameters['user'];
			$this->password = $parameters['password'];
			if (isset($parameters['authType'])) {
				$this->authType = $parameters['authType'];
			}
			if (isset($parameters['secure'])) {
				if (is_string($parameters['secure'])) {
					$this->secure = ($parameters['secure'] === 'true');
				} else {
					$this->secure = (bool)$parameters['secure'];
				}
			} else {
				$this->secure = false;
			}
			if ($this->secure === true) {
				// inject mock for testing
				$this->certManager = \OC::$server->getCertificateManager();
			}
			$this->root = $parameters['root'] ?? '/';
			$this->root = '/' . ltrim($this->root, '/');
			$this->root = rtrim($this->root, '/') . '/';
		} else {
			throw new \Exception('Invalid webdav storage configuration');
		}
		$this->logger = Server::get(LoggerInterface::class);
		$this->eventLogger = Server::get(IEventLogger::class);
		// This timeout value will be used for the download and upload of files
		$this->timeout = Server::get(IConfig::class)->getSystemValueInt('davstorage.request_timeout', IClient::DEFAULT_REQUEST_TIMEOUT);
		$this->mimeTypeDetector = \OC::$server->getMimeTypeDetector();
	}

	protected function init(): void {
		if ($this->ready) {
			return;
		}
		$this->ready = true;

		$settings = [
			'baseUri' => $this->createBaseUri(),
			'userName' => $this->user,
			'password' => $this->password,
		];
		if ($this->authType !== null) {
			$settings['authType'] = $this->authType;
		}

		$proxy = Server::get(IConfig::class)->getSystemValueString('proxy', '');
		if ($proxy !== '') {
			$settings['proxy'] = $proxy;
		}

		$this->client = new Client($settings);
		$this->client->setThrowExceptions(true);

		if ($this->secure === true) {
			$certPath = $this->certManager->getAbsoluteBundlePath();
			if (file_exists($certPath)) {
				$this->certPath = $certPath;
			}
			if ($this->certPath) {
				$this->client->addCurlSetting(CURLOPT_CAINFO, $this->certPath);
			}
		}

		$lastRequestStart = 0;
		$this->client->on('beforeRequest', function (RequestInterface $request) use (&$lastRequestStart) {
			$this->logger->debug('sending dav ' . $request->getMethod() . ' request to external storage: ' . $request->getAbsoluteUrl(), ['app' => 'dav']);
			$lastRequestStart = microtime(true);
			$this->eventLogger->start('fs:storage:dav:request', 'Sending dav request to external storage');
		});
		$this->client->on('afterRequest', function (RequestInterface $request) use (&$lastRequestStart) {
			$elapsed = microtime(true) - $lastRequestStart;
			$this->logger->debug('dav ' . $request->getMethod() . ' request to external storage: ' . $request->getAbsoluteUrl() . ' took ' . round($elapsed * 1000, 1) . 'ms', ['app' => 'dav']);
			$this->eventLogger->end('fs:storage:dav:request');
		});
	}

	/**
	 * Clear the stat cache
	 */
	public function clearStatCache(): void {
		$this->statCache->clear();
	}

	public function getId(): string {
		return 'webdav::' . $this->user . '@' . $this->host . '/' . $this->root;
	}

	public function createBaseUri(): string {
		$baseUri = 'http';
		if ($this->secure) {
			$baseUri .= 's';
		}
		$baseUri .= '://' . $this->host . $this->root;
		return $baseUri;
	}

	public function mkdir(string $path): bool {
		$this->init();
		$path = $this->cleanPath($path);
		$result = $this->simpleResponse('MKCOL', $path, null, 201);
		if ($result) {
			$this->statCache->set($path, true);
		}
		return $result;
	}

	public function rmdir(string $path): bool {
		$this->init();
		$path = $this->cleanPath($path);
		// FIXME: some WebDAV impl return 403 when trying to DELETE
		// a non-empty folder
		$result = $this->simpleResponse('DELETE', $path . '/', null, 204);
		$this->statCache->clear($path . '/');
		$this->statCache->remove($path);
		return $result;
	}

	public function opendir(string $path) {
		$this->init();
		$path = $this->cleanPath($path);
		try {
			$content = $this->getDirectoryContent($path);
			$files = [];
			foreach ($content as $child) {
				$files[] = $child['name'];
			}
			return IteratorDirectory::wrap($files);
		} catch (\Exception $e) {
			$this->convertException($e, $path);
		}
		return false;
	}

	/**
	 * Propfind call with cache handling.
	 *
	 * First checks if information is cached.
	 * If not, request it from the server then store to cache.
	 *
	 * @param string $path path to propfind
	 *
	 * @return array|false propfind response or false if the entry was not found
	 *
	 * @throws ClientHttpException
	 */
	protected function propfind(string $path): array|false {
		$path = $this->cleanPath($path);
		$cachedResponse = $this->statCache->get($path);
		// we either don't know it, or we know it exists but need more details
		if (is_null($cachedResponse) || $cachedResponse === true) {
			$this->init();
			$response = false;
			try {
				$response = $this->client->propFind(
					$this->encodePath($path),
					self::PROPFIND_PROPS
				);
				$this->statCache->set($path, $response);
			} catch (ClientHttpException $e) {
				if ($e->getHttpStatus() === 404 || $e->getHttpStatus() === 405) {
					$this->statCache->clear($path . '/');
					$this->statCache->set($path, false);
				} else {
					$this->convertException($e, $path);
				}
			} catch (\Exception $e) {
				$this->convertException($e, $path);
			}
		} else {
			$response = $cachedResponse;
		}
		return $response;
	}

	public function filetype(string $path): string|false {
		try {
			$response = $this->propfind($path);
			if ($response === false) {
				return false;
			}
			$responseType = [];
			if (isset($response['{DAV:}resourcetype'])) {
				/** @var ResourceType[] $response */
				$responseType = $response['{DAV:}resourcetype']->getValue();
			}
			return (count($responseType) > 0 && $responseType[0] == '{DAV:}collection') ? 'dir' : 'file';
		} catch (\Exception $e) {
			$this->convertException($e, $path);
		}
		return false;
	}

	public function file_exists(string $path): bool {
		try {
			$path = $this->cleanPath($path);
			$cachedState = $this->statCache->get($path);
			if ($cachedState === false) {
				// we know the file doesn't exist
				return false;
			} elseif (!is_null($cachedState)) {
				return true;
			}
			// need to get from server
			return ($this->propfind($path) !== false);
		} catch (\Exception $e) {
			$this->convertException($e, $path);
		}
		return false;
	}

	public function unlink(string $path): bool {
		$this->init();
		$path = $this->cleanPath($path);
		$result = $this->simpleResponse('DELETE', $path, null, 204);
		$this->statCache->clear($path . '/');
		$this->statCache->remove($path);
		return $result;
	}

	public function fopen(string $path, string $mode) {
		$this->init();
		$path = $this->cleanPath($path);
		switch ($mode) {
			case 'r':
			case 'rb':
				try {
					$response = $this->httpClientService
						->newClient()
						->get($this->createBaseUri() . $this->encodePath($path), [
							'auth' => [$this->user, $this->password],
							'stream' => true,
							// set download timeout for users with slow connections or large files
							'timeout' => $this->timeout
						]);
				} catch (\GuzzleHttp\Exception\ClientException $e) {
					if ($e->getResponse() instanceof ResponseInterface
						&& $e->getResponse()->getStatusCode() === 404) {
						return false;
					} else {
						throw $e;
					}
				}

				if ($response->getStatusCode() !== Http::STATUS_OK) {
					if ($response->getStatusCode() === Http::STATUS_LOCKED) {
						throw new \OCP\Lock\LockedException($path);
					} else {
						$this->logger->error('Guzzle get returned status code ' . $response->getStatusCode(), ['app' => 'webdav client']);
					}
				}

				return $response->getBody();
			case 'w':
			case 'wb':
			case 'a':
			case 'ab':
			case 'r+':
			case 'w+':
			case 'wb+':
			case 'a+':
			case 'x':
			case 'x+':
			case 'c':
			case 'c+':
				//emulate these
				$tempManager = \OC::$server->getTempManager();
				if (strrpos($path, '.') !== false) {
					$ext = substr($path, strrpos($path, '.'));
				} else {
					$ext = '';
				}
				if ($this->file_exists($path)) {
					if (!$this->isUpdatable($path)) {
						return false;
					}
					if ($mode === 'w' || $mode === 'w+') {
						$tmpFile = $tempManager->getTemporaryFile($ext);
					} else {
						$tmpFile = $this->getCachedFile($path);
					}
				} else {
					if (!$this->isCreatable(dirname($path))) {
						return false;
					}
					$tmpFile = $tempManager->getTemporaryFile($ext);
				}
				$handle = fopen($tmpFile, $mode);
				return CallbackWrapper::wrap($handle, null, null, function () use ($path, $tmpFile) {
					$this->writeBack($tmpFile, $path);
				});
		}
	}

	public function writeBack(string $tmpFile, string $path): void {
		$this->uploadFile($tmpFile, $path);
		unlink($tmpFile);
	}

	public function free_space(string $path): int|float|false {
		$this->init();
		$path = $this->cleanPath($path);
		try {
			$response = $this->propfind($path);
			if ($response === false) {
				return FileInfo::SPACE_UNKNOWN;
			}
			if (isset($response['{DAV:}quota-available-bytes'])) {
				return Util::numericToNumber($response['{DAV:}quota-available-bytes']);
			} else {
				return FileInfo::SPACE_UNKNOWN;
			}
		} catch (\Exception $e) {
			return FileInfo::SPACE_UNKNOWN;
		}
	}

	public function touch(string $path, ?int $mtime = null): bool {
		$this->init();
		if (is_null($mtime)) {
			$mtime = time();
		}
		$path = $this->cleanPath($path);

		// if file exists, update the mtime, else create a new empty file
		if ($this->file_exists($path)) {
			try {
				$this->statCache->remove($path);
				$this->client->proppatch($this->encodePath($path), ['{DAV:}lastmodified' => $mtime]);
				// non-owncloud clients might not have accepted the property, need to recheck it
				$response = $this->client->propfind($this->encodePath($path), ['{DAV:}getlastmodified'], 0);
				if (isset($response['{DAV:}getlastmodified'])) {
					$remoteMtime = strtotime($response['{DAV:}getlastmodified']);
					if ($remoteMtime !== $mtime) {
						// server has not accepted the mtime
						return false;
					}
				}
			} catch (ClientHttpException $e) {
				if ($e->getHttpStatus() === 501) {
					return false;
				}
				$this->convertException($e, $path);
				return false;
			} catch (\Exception $e) {
				$this->convertException($e, $path);
				return false;
			}
		} else {
			$this->file_put_contents($path, '');
		}
		return true;
	}

	public function file_put_contents(string $path, mixed $data): int|float|false {
		$path = $this->cleanPath($path);
		$result = parent::file_put_contents($path, $data);
		$this->statCache->remove($path);
		return $result;
	}

	protected function uploadFile(string $path, string $target): void {
		$this->init();

		// invalidate
		$target = $this->cleanPath($target);
		$this->statCache->remove($target);
		$source = fopen($path, 'r');

		$this->httpClientService
			->newClient()
			->put($this->createBaseUri() . $this->encodePath($target), [
				'body' => $source,
				'auth' => [$this->user, $this->password],
				// set upload timeout for users with slow connections or large files
				'timeout' => $this->timeout
			]);

		$this->removeCachedFile($target);
	}

	public function rename(string $source, string $target): bool {
		$this->init();
		$source = $this->cleanPath($source);
		$target = $this->cleanPath($target);
		try {
			// overwrite directory ?
			if ($this->is_dir($target)) {
				// needs trailing slash in destination
				$target = rtrim($target, '/') . '/';
			}
			$this->client->request(
				'MOVE',
				$this->encodePath($source),
				null,
				[
					'Destination' => $this->createBaseUri() . $this->encodePath($target),
				]
			);
			$this->statCache->clear($source . '/');
			$this->statCache->clear($target . '/');
			$this->statCache->set($source, false);
			$this->statCache->set($target, true);
			$this->removeCachedFile($source);
			$this->removeCachedFile($target);
			return true;
		} catch (\Exception $e) {
			$this->convertException($e);
		}
		return false;
	}

	public function copy(string $source, string $target): bool {
		$this->init();
		$source = $this->cleanPath($source);
		$target = $this->cleanPath($target);
		try {
			// overwrite directory ?
			if ($this->is_dir($target)) {
				// needs trailing slash in destination
				$target = rtrim($target, '/') . '/';
			}
			$this->client->request(
				'COPY',
				$this->encodePath($source),
				null,
				[
					'Destination' => $this->createBaseUri() . $this->encodePath($target),
				]
			);
			$this->statCache->clear($target . '/');
			$this->statCache->set($target, true);
			$this->removeCachedFile($target);
			return true;
		} catch (\Exception $e) {
			$this->convertException($e);
		}
		return false;
	}

	public function getMetaData(string $path): ?array {
		if (Filesystem::isFileBlacklisted($path)) {
			throw new ForbiddenException('Invalid path: ' . $path, false);
		}
		$response = $this->propfind($path);
		if (!$response) {
			return null;
		} else {
			return $this->getMetaFromPropfind($path, $response);
		}
	}
	private function getMetaFromPropfind(string $path, array $response): array {
		if (isset($response['{DAV:}getetag'])) {
			$etag = trim($response['{DAV:}getetag'], '"');
			if (strlen($etag) > 40) {
				$etag = md5($etag);
			}
		} else {
			$etag = parent::getETag($path);
		}

		$responseType = [];
		if (isset($response['{DAV:}resourcetype'])) {
			/** @var ResourceType[] $response */
			$responseType = $response['{DAV:}resourcetype']->getValue();
		}
		$type = (count($responseType) > 0 && $responseType[0] == '{DAV:}collection') ? 'dir' : 'file';
		if ($type === 'dir') {
			$mimeType = 'httpd/unix-directory';
		} elseif (isset($response['{DAV:}getcontenttype'])) {
			$mimeType = $response['{DAV:}getcontenttype'];
		} else {
			$mimeType = $this->mimeTypeDetector->detectPath($path);
		}

		if (isset($response['{http://owncloud.org/ns}permissions'])) {
			$permissions = $this->parsePermissions($response['{http://owncloud.org/ns}permissions']);
		} elseif ($type === 'dir') {
			$permissions = Constants::PERMISSION_ALL;
		} else {
			$permissions = Constants::PERMISSION_ALL - Constants::PERMISSION_CREATE;
		}

		$mtime = isset($response['{DAV:}getlastmodified']) ? strtotime($response['{DAV:}getlastmodified']) : null;

		if ($type === 'dir') {
			$size = -1;
		} else {
			$size = Util::numericToNumber($response['{DAV:}getcontentlength'] ?? 0);
		}

		return [
			'name' => basename($path),
			'mtime' => $mtime,
			'storage_mtime' => $mtime,
			'size' => $size,
			'permissions' => $permissions,
			'etag' => $etag,
			'mimetype' => $mimeType,
		];
	}

	public function stat(string $path): array|false {
		$meta = $this->getMetaData($path);
		return $meta ?: false;

	}

	public function getMimeType(string $path): string|false {
		$meta = $this->getMetaData($path);
		return $meta ? $meta['mimetype'] : false;
	}

	public function cleanPath(string $path): string {
		if ($path === '') {
			return $path;
		}
		$path = Filesystem::normalizePath($path);
		// remove leading slash
		return substr($path, 1);
	}

	/**
	 * URL encodes the given path but keeps the slashes
	 *
	 * @param string $path to encode
	 * @return string encoded path
	 */
	protected function encodePath(string $path): string {
		// slashes need to stay
		return str_replace('%2F', '/', rawurlencode($path));
	}

	/**
	 * @return bool
	 * @throws StorageInvalidException
	 * @throws StorageNotAvailableException
	 */
	protected function simpleResponse(string $method, string $path, ?string $body, int $expected): bool {
		$path = $this->cleanPath($path);
		try {
			$response = $this->client->request($method, $this->encodePath($path), $body);
			return $response['statusCode'] == $expected;
		} catch (ClientHttpException $e) {
			if ($e->getHttpStatus() === 404 && $method === 'DELETE') {
				$this->statCache->clear($path . '/');
				$this->statCache->set($path, false);
				return false;
			}

			$this->convertException($e, $path);
		} catch (\Exception $e) {
			$this->convertException($e, $path);
		}
		return false;
	}

	/**
	 * check if curl is installed
	 */
	public static function checkDependencies(): bool {
		return true;
	}

	public function isUpdatable(string $path): bool {
		return (bool)($this->getPermissions($path) & Constants::PERMISSION_UPDATE);
	}

	public function isCreatable(string $path): bool {
		return (bool)($this->getPermissions($path) & Constants::PERMISSION_CREATE);
	}

	public function isSharable(string $path): bool {
		return (bool)($this->getPermissions($path) & Constants::PERMISSION_SHARE);
	}

	public function isDeletable(string $path): bool {
		return (bool)($this->getPermissions($path) & Constants::PERMISSION_DELETE);
	}

	public function getPermissions(string $path): int {
		$stat = $this->getMetaData($path);
		return $stat ? $stat['permissions'] : 0;
	}

	public function getETag(string $path): string|false {
		$meta = $this->getMetaData($path);
		return $meta ? $meta['etag'] : false;
	}

	protected function parsePermissions(string $permissionsString): int {
		$permissions = Constants::PERMISSION_READ;
		if (str_contains($permissionsString, 'R')) {
			$permissions |= Constants::PERMISSION_SHARE;
		}
		if (str_contains($permissionsString, 'D')) {
			$permissions |= Constants::PERMISSION_DELETE;
		}
		if (str_contains($permissionsString, 'W')) {
			$permissions |= Constants::PERMISSION_UPDATE;
		}
		if (str_contains($permissionsString, 'CK')) {
			$permissions |= Constants::PERMISSION_CREATE;
			$permissions |= Constants::PERMISSION_UPDATE;
		}
		return $permissions;
	}

	public function hasUpdated(string $path, int $time): bool {
		$this->init();
		$path = $this->cleanPath($path);
		try {
			// force refresh for $path
			$this->statCache->remove($path);
			$response = $this->propfind($path);
			if ($response === false) {
				if ($path === '') {
					// if root is gone it means the storage is not available
					throw new StorageNotAvailableException('root is gone');
				}
				return false;
			}
			if (isset($response['{DAV:}getetag'])) {
				$cachedData = $this->getCache()->get($path);
				$etag = trim($response['{DAV:}getetag'], '"');
				if (($cachedData === false) || (!empty($etag) && ($cachedData['etag'] !== $etag))) {
					return true;
				} elseif (isset($response['{http://open-collaboration-services.org/ns}share-permissions'])) {
					$sharePermissions = (int)$response['{http://open-collaboration-services.org/ns}share-permissions'];
					return $sharePermissions !== $cachedData['permissions'];
				} elseif (isset($response['{http://owncloud.org/ns}permissions'])) {
					$permissions = $this->parsePermissions($response['{http://owncloud.org/ns}permissions']);
					return $permissions !== $cachedData['permissions'];
				} else {
					return false;
				}
			} elseif (isset($response['{DAV:}getlastmodified'])) {
				$remoteMtime = strtotime($response['{DAV:}getlastmodified']);
				return $remoteMtime > $time;
			} else {
				// neither `getetag` nor `getlastmodified` is set
				return false;
			}
		} catch (ClientHttpException $e) {
			if ($e->getHttpStatus() === 405) {
				if ($path === '') {
					// if root is gone it means the storage is not available
					throw new StorageNotAvailableException(get_class($e) . ': ' . $e->getMessage());
				}
				return false;
			}
			$this->convertException($e, $path);
			return false;
		} catch (\Exception $e) {
			$this->convertException($e, $path);
			return false;
		}
	}

	/**
	 * Interpret the given exception and decide whether it is due to an
	 * unavailable storage, invalid storage or other.
	 * This will either throw StorageInvalidException, StorageNotAvailableException
	 * or do nothing.
	 *
	 * @param Exception $e sabre exception
	 * @param string $path optional path from the operation
	 *
	 * @throws StorageInvalidException if the storage is invalid, for example
	 *                                 when the authentication expired or is invalid
	 * @throws StorageNotAvailableException if the storage is not available,
	 *                                      which might be temporary
	 * @throws ForbiddenException if the action is not allowed
	 */
	protected function convertException(Exception $e, string $path = ''): void {
		$this->logger->debug($e->getMessage(), ['app' => 'files_external', 'exception' => $e]);
		if ($e instanceof ClientHttpException) {
			if ($e->getHttpStatus() === Http::STATUS_LOCKED) {
				throw new \OCP\Lock\LockedException($path);
			}
			if ($e->getHttpStatus() === Http::STATUS_UNAUTHORIZED) {
				// either password was changed or was invalid all along
				throw new StorageInvalidException(get_class($e) . ': ' . $e->getMessage());
			} elseif ($e->getHttpStatus() === Http::STATUS_METHOD_NOT_ALLOWED) {
				// ignore exception for MethodNotAllowed, false will be returned
				return;
			} elseif ($e->getHttpStatus() === Http::STATUS_FORBIDDEN) {
				// The operation is forbidden. Fail somewhat gracefully
				throw new ForbiddenException(get_class($e) . ':' . $e->getMessage(), false);
			}
			throw new StorageNotAvailableException(get_class($e) . ': ' . $e->getMessage());
		} elseif ($e instanceof ClientException) {
			// connection timeout or refused, server could be temporarily down
			throw new StorageNotAvailableException(get_class($e) . ': ' . $e->getMessage());
		} elseif ($e instanceof \InvalidArgumentException) {
			// parse error because the server returned HTML instead of XML,
			// possibly temporarily down
			throw new StorageNotAvailableException(get_class($e) . ': ' . $e->getMessage());
		} elseif (($e instanceof StorageNotAvailableException) || ($e instanceof StorageInvalidException)) {
			// rethrow
			throw $e;
		}

		// TODO: only log for now, but in the future need to wrap/rethrow exception
	}

	public function getDirectoryContent(string $directory): \Traversable {
		$this->init();
		$directory = $this->cleanPath($directory);
		try {
			$responses = $this->client->propFind(
				$this->encodePath($directory),
				self::PROPFIND_PROPS,
				1
			);

			array_shift($responses); //the first entry is the current directory
			if (!$this->statCache->hasKey($directory)) {
				$this->statCache->set($directory, true);
			}

			foreach ($responses as $file => $response) {
				$file = rawurldecode($file);
				$file = substr($file, strlen($this->root));
				$file = $this->cleanPath($file);
				$this->statCache->set($file, $response);
				yield $this->getMetaFromPropfind($file, $response);
			}
		} catch (\Exception $e) {
			$this->convertException($e, $directory);
		}
	}
}
