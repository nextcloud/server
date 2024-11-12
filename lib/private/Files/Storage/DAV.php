<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Carlos Cerrillo <ccerrillo@gmail.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Philipp Kapfer <philipp.kapfer@gmx.at>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tigran Mkrtchyan <tigran.mkrtchyan@desy.de>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
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
use OCP\Http\Client\IClientService;
use OCP\ICertificateManager;
use OCP\IConfig;
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
	 * @param array $params
	 * @throws \Exception
	 */
	public function __construct($params) {
		$this->statCache = new ArrayCache();
		$this->httpClientService = \OC::$server->getHTTPClientService();
		if (isset($params['host']) && isset($params['user']) && isset($params['password'])) {
			$host = $params['host'];
			//remove leading http[s], will be generated in createBaseUri()
			if (str_starts_with($host, "https://")) {
				$host = substr($host, 8);
			} elseif (str_starts_with($host, "http://")) {
				$host = substr($host, 7);
			}
			$this->host = $host;
			$this->user = $params['user'];
			$this->password = $params['password'];
			if (isset($params['authType'])) {
				$this->authType = $params['authType'];
			}
			if (isset($params['secure'])) {
				if (is_string($params['secure'])) {
					$this->secure = ($params['secure'] === 'true');
				} else {
					$this->secure = (bool)$params['secure'];
				}
			} else {
				$this->secure = false;
			}
			if ($this->secure === true) {
				// inject mock for testing
				$this->certManager = \OC::$server->getCertificateManager();
			}
			$this->root = $params['root'] ?? '/';
			$this->root = '/' . ltrim($this->root, '/');
			$this->root = rtrim($this->root, '/') . '/';
		} else {
			throw new \Exception('Invalid webdav storage configuration');
		}
		$this->logger = \OC::$server->get(LoggerInterface::class);
		$this->eventLogger = \OC::$server->get(IEventLogger::class);
		// This timeout value will be used for the download and upload of files
		$this->timeout = \OC::$server->get(IConfig::class)->getSystemValueInt('davstorage.request_timeout', 30);
		$this->mimeTypeDetector = \OC::$server->getMimeTypeDetector();
	}

	protected function init() {
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

		$proxy = \OC::$server->getConfig()->getSystemValueString('proxy', '');
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
			$this->logger->debug("sending dav " . $request->getMethod() .  " request to external storage: " . $request->getAbsoluteUrl(), ['app' => 'dav']);
			$lastRequestStart = microtime(true);
			$this->eventLogger->start('fs:storage:dav:request', "Sending dav request to external storage");
		});
		$this->client->on('afterRequest', function (RequestInterface $request) use (&$lastRequestStart) {
			$elapsed = microtime(true) - $lastRequestStart;
			$this->logger->debug("dav " . $request->getMethod() .  " request to external storage: " . $request->getAbsoluteUrl() . " took " . round($elapsed * 1000, 1) . "ms", ['app' => 'dav']);
			$this->eventLogger->end('fs:storage:dav:request');
		});
	}

	/**
	 * Clear the stat cache
	 */
	public function clearStatCache() {
		$this->statCache->clear();
	}

	/** {@inheritdoc} */
	public function getId() {
		return 'webdav::' . $this->user . '@' . $this->host . '/' . $this->root;
	}

	/** {@inheritdoc} */
	public function createBaseUri() {
		$baseUri = 'http';
		if ($this->secure) {
			$baseUri .= 's';
		}
		$baseUri .= '://' . $this->host . $this->root;
		return $baseUri;
	}

	/** {@inheritdoc} */
	public function mkdir($path) {
		$this->init();
		$path = $this->cleanPath($path);
		$result = $this->simpleResponse('MKCOL', $path, null, 201);
		if ($result) {
			$this->statCache->set($path, true);
		}
		return $result;
	}

	/** {@inheritdoc} */
	public function rmdir($path) {
		$this->init();
		$path = $this->cleanPath($path);
		// FIXME: some WebDAV impl return 403 when trying to DELETE
		// a non-empty folder
		$result = $this->simpleResponse('DELETE', $path . '/', null, 204);
		$this->statCache->clear($path . '/');
		$this->statCache->remove($path);
		return $result;
	}

	/** {@inheritdoc} */
	public function opendir($path) {
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
	 * @return array|boolean propfind response or false if the entry was not found
	 *
	 * @throws ClientHttpException
	 */
	protected function propfind($path) {
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

	/** {@inheritdoc} */
	public function filetype($path) {
		try {
			$response = $this->propfind($path);
			if ($response === false) {
				return false;
			}
			$responseType = [];
			if (isset($response["{DAV:}resourcetype"])) {
				/** @var ResourceType[] $response */
				$responseType = $response["{DAV:}resourcetype"]->getValue();
			}
			return (count($responseType) > 0 and $responseType[0] == "{DAV:}collection") ? 'dir' : 'file';
		} catch (\Exception $e) {
			$this->convertException($e, $path);
		}
		return false;
	}

	/** {@inheritdoc} */
	public function file_exists($path) {
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

	/** {@inheritdoc} */
	public function unlink($path) {
		$this->init();
		$path = $this->cleanPath($path);
		$result = $this->simpleResponse('DELETE', $path, null, 204);
		$this->statCache->clear($path . '/');
		$this->statCache->remove($path);
		return $result;
	}

	/** {@inheritdoc} */
	public function fopen($path, $mode) {
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
						\OC::$server->get(LoggerInterface::class)->error('Guzzle get returned status code ' . $response->getStatusCode(), ['app' => 'webdav client']);
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
					if ($mode === 'w' or $mode === 'w+') {
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

	/**
	 * @param string $tmpFile
	 */
	public function writeBack($tmpFile, $path) {
		$this->uploadFile($tmpFile, $path);
		unlink($tmpFile);
	}

	/** {@inheritdoc} */
	public function free_space($path) {
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

	/** {@inheritdoc} */
	public function touch($path, $mtime = null) {
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
				if ($response === false) {
					return false;
				}
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

	/**
	 * @param string $path
	 * @param mixed $data
	 * @return int|float|false
	 */
	public function file_put_contents($path, $data) {
		$path = $this->cleanPath($path);
		$result = parent::file_put_contents($path, $data);
		$this->statCache->remove($path);
		return $result;
	}

	/**
	 * @param string $path
	 * @param string $target
	 */
	protected function uploadFile($path, $target) {
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

	/** {@inheritdoc} */
	public function rename($source, $target) {
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

	/** {@inheritdoc} */
	public function copy($source, $target) {
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

	public function getMetaData($path) {
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
		if (isset($response["{DAV:}resourcetype"])) {
			/** @var ResourceType[] $response */
			$responseType = $response["{DAV:}resourcetype"]->getValue();
		}
		$type = (count($responseType) > 0 and $responseType[0] == "{DAV:}collection") ? 'dir' : 'file';
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

	/** {@inheritdoc} */
	public function stat($path) {
		$meta = $this->getMetaData($path);
		if (!$meta) {
			return false;
		} else {
			return $meta;
		}
	}

	/** {@inheritdoc} */
	public function getMimeType($path) {
		$meta = $this->getMetaData($path);
		if ($meta) {
			return $meta['mimetype'];
		} else {
			return false;
		}
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public function cleanPath($path) {
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
	protected function encodePath($path) {
		// slashes need to stay
		return str_replace('%2F', '/', rawurlencode($path));
	}

	/**
	 * @param string $method
	 * @param string $path
	 * @param string|resource|null $body
	 * @param int $expected
	 * @return bool
	 * @throws StorageInvalidException
	 * @throws StorageNotAvailableException
	 */
	protected function simpleResponse($method, $path, $body, $expected) {
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
	public static function checkDependencies() {
		return true;
	}

	/** {@inheritdoc} */
	public function isUpdatable($path) {
		return (bool)($this->getPermissions($path) & Constants::PERMISSION_UPDATE);
	}

	/** {@inheritdoc} */
	public function isCreatable($path) {
		return (bool)($this->getPermissions($path) & Constants::PERMISSION_CREATE);
	}

	/** {@inheritdoc} */
	public function isSharable($path) {
		return (bool)($this->getPermissions($path) & Constants::PERMISSION_SHARE);
	}

	/** {@inheritdoc} */
	public function isDeletable($path) {
		return (bool)($this->getPermissions($path) & Constants::PERMISSION_DELETE);
	}

	/** {@inheritdoc} */
	public function getPermissions($path) {
		$stat = $this->getMetaData($path);
		if ($stat) {
			return $stat['permissions'];
		} else {
			return 0;
		}
	}

	/** {@inheritdoc} */
	public function getETag($path) {
		$meta = $this->getMetaData($path);
		if ($meta) {
			return $meta['etag'];
		} else {
			return null;
		}
	}

	/**
	 * @param string $permissionsString
	 * @return int
	 */
	protected function parsePermissions($permissionsString) {
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

	/**
	 * check if a file or folder has been updated since $time
	 *
	 * @param string $path
	 * @param int $time
	 * @throws \OCP\Files\StorageNotAvailableException
	 * @return bool
	 */
	public function hasUpdated($path, $time) {
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
	 * when the authentication expired or is invalid
	 * @throws StorageNotAvailableException if the storage is not available,
	 * which might be temporary
	 * @throws ForbiddenException if the action is not allowed
	 */
	protected function convertException(Exception $e, $path = '') {
		\OC::$server->get(LoggerInterface::class)->debug($e->getMessage(), ['app' => 'files_external', 'exception' => $e]);
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

	public function getDirectoryContent($directory): \Traversable {
		$this->init();
		$directory = $this->cleanPath($directory);
		try {
			$responses = $this->client->propFind(
				$this->encodePath($directory),
				self::PROPFIND_PROPS,
				1
			);
			if ($responses === false) {
				return;
			}

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
