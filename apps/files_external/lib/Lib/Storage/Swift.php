<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Storage;

use GuzzleHttp\Psr7\Uri;
use Icewind\Streams\CallbackWrapper;
use Icewind\Streams\IteratorDirectory;
use OC\Files\Filesystem;
use OC\Files\ObjectStore\SwiftFactory;
use OC\Files\Storage\Common;
use OCP\Cache\CappedMemoryCache;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\StorageAuthException;
use OCP\Files\StorageBadConfigException;
use OCP\Files\StorageNotAvailableException;
use OCP\ICache;
use OpenStack\Common\Error\BadResponseError;
use OpenStack\ObjectStore\v1\Models\Container;
use OpenStack\ObjectStore\v1\Models\StorageObject;
use Psr\Log\LoggerInterface;

class Swift extends Common {
	/** @var SwiftFactory */
	private $connectionFactory;
	/**
	 * @var Container
	 */
	private $container;
	/**
	 * @var string
	 */
	private $bucket;
	/**
	 * Connection parameters
	 *
	 * @var array
	 */
	private $params;

	/** @var string */
	private $id;

	/** @var \OC\Files\ObjectStore\Swift */
	private $objectStore;

	/** @var IMimeTypeDetector */
	private $mimeDetector;

	/**
	 * Key value cache mapping path to data object. Maps path to
	 * \OpenCloud\OpenStack\ObjectStorage\Resource\DataObject for existing
	 * paths and path to false for not existing paths.
	 *
	 * @var ICache
	 */
	private $objectCache;

	private function normalizePath(string $path): string {
		$path = trim($path, '/');

		if (!$path) {
			$path = '.';
		}

		$path = str_replace('#', '%23', $path);

		return $path;
	}

	public const SUBCONTAINER_FILE = '.subcontainers';

	/**
	 * Fetches an object from the API.
	 * If the object is cached already or a
	 * failed "doesn't exist" response was cached,
	 * that one will be returned.
	 *
	 * @return StorageObject|false object
	 *                             or false if the object did not exist
	 * @throws StorageAuthException
	 * @throws StorageNotAvailableException
	 */
	private function fetchObject(string $path): StorageObject|false {
		$cached = $this->objectCache->get($path);
		if ($cached !== null) {
			// might be "false" if object did not exist from last check
			return $cached;
		}
		try {
			$object = $this->getContainer()->getObject($path);
			$object->retrieve();
			$this->objectCache->set($path, $object);
			return $object;
		} catch (BadResponseError $e) {
			// Expected response is "404 Not Found", so only log if it isn't
			if ($e->getResponse()->getStatusCode() !== 404) {
				\OC::$server->get(LoggerInterface::class)->error($e->getMessage(), [
					'exception' => $e,
					'app' => 'files_external',
				]);
			}
			$this->objectCache->set($path, false);
			return false;
		}
	}

	/**
	 * Returns whether the given path exists.
	 *
	 * @return bool true if the object exist, false otherwise
	 * @throws StorageAuthException
	 * @throws StorageNotAvailableException
	 */
	private function doesObjectExist(string $path): bool {
		return $this->fetchObject($path) !== false;
	}

	public function __construct(array $parameters) {
		if ((empty($parameters['key']) and empty($parameters['password']))
			or (empty($parameters['user']) && empty($parameters['userid'])) or empty($parameters['bucket'])
			or empty($parameters['region'])
		) {
			throw new StorageBadConfigException('API Key or password, Login, Bucket and Region have to be configured.');
		}

		$user = $parameters['user'];
		$this->id = 'swift::' . $user . md5($parameters['bucket']);

		$bucketUrl = new Uri($parameters['bucket']);
		if ($bucketUrl->getHost()) {
			$parameters['bucket'] = basename($bucketUrl->getPath());
			$parameters['endpoint_url'] = (string)$bucketUrl->withPath(dirname($bucketUrl->getPath()));
		}

		if (empty($parameters['url'])) {
			$parameters['url'] = 'https://identity.api.rackspacecloud.com/v2.0/';
		}

		if (empty($parameters['service_name'])) {
			$parameters['service_name'] = 'cloudFiles';
		}

		$parameters['autocreate'] = true;

		if (isset($parameters['domain'])) {
			$parameters['user'] = [
				'name' => $parameters['user'],
				'password' => $parameters['password'],
				'domain' => [
					'name' => $parameters['domain'],
				]
			];
		}

		$this->params = $parameters;
		// FIXME: private class...
		$this->objectCache = new CappedMemoryCache();
		$this->connectionFactory = new SwiftFactory(
			\OC::$server->getMemCacheFactory()->createDistributed('swift/'),
			$this->params,
			\OC::$server->get(LoggerInterface::class)
		);
		$this->objectStore = new \OC\Files\ObjectStore\Swift($this->params, $this->connectionFactory);
		$this->bucket = $parameters['bucket'];
		$this->mimeDetector = \OC::$server->get(IMimeTypeDetector::class);
	}

	public function mkdir(string $path): bool {
		$path = $this->normalizePath($path);

		if ($this->is_dir($path)) {
			return false;
		}

		if ($path !== '.') {
			$path .= '/';
		}

		try {
			$this->getContainer()->createObject([
				'name' => $path,
				'content' => '',
				'headers' => ['content-type' => 'httpd/unix-directory']
			]);
			// invalidate so that the next access gets the real object
			// with all properties
			$this->objectCache->remove($path);
		} catch (BadResponseError $e) {
			\OC::$server->get(LoggerInterface::class)->error($e->getMessage(), [
				'exception' => $e,
				'app' => 'files_external',
			]);
			return false;
		}

		return true;
	}

	public function file_exists(string $path): bool {
		$path = $this->normalizePath($path);

		if ($path !== '.' && $this->is_dir($path)) {
			$path .= '/';
		}

		return $this->doesObjectExist($path);
	}

	public function rmdir(string $path): bool {
		$path = $this->normalizePath($path);

		if (!$this->is_dir($path) || !$this->isDeletable($path)) {
			return false;
		}

		$dh = $this->opendir($path);
		while (($file = readdir($dh)) !== false) {
			if (Filesystem::isIgnoredDir($file)) {
				continue;
			}

			if ($this->is_dir($path . '/' . $file)) {
				$this->rmdir($path . '/' . $file);
			} else {
				$this->unlink($path . '/' . $file);
			}
		}

		try {
			$this->objectStore->deleteObject($path . '/');
			$this->objectCache->remove($path . '/');
		} catch (BadResponseError $e) {
			\OC::$server->get(LoggerInterface::class)->error($e->getMessage(), [
				'exception' => $e,
				'app' => 'files_external',
			]);
			return false;
		}

		return true;
	}

	public function opendir(string $path) {
		$path = $this->normalizePath($path);

		if ($path === '.') {
			$path = '';
		} else {
			$path .= '/';
		}

		//		$path = str_replace('%23', '#', $path); // the prefix is sent as a query param, so revert the encoding of #

		try {
			$files = [];
			$objects = $this->getContainer()->listObjects([
				'prefix' => $path,
				'delimiter' => '/'
			]);

			/** @var StorageObject $object */
			foreach ($objects as $object) {
				$file = basename($object->name);
				if ($file !== basename($path) && $file !== '.') {
					$files[] = $file;
				}
			}

			return IteratorDirectory::wrap($files);
		} catch (\Exception $e) {
			\OC::$server->get(LoggerInterface::class)->error($e->getMessage(), [
				'exception' => $e,
				'app' => 'files_external',
			]);
			return false;
		}
	}

	public function stat(string $path): array|false {
		$path = $this->normalizePath($path);

		if ($path === '.') {
			$path = '';
		} elseif ($this->is_dir($path)) {
			$path .= '/';
		}

		try {
			$object = $this->fetchObject($path);
			if (!$object) {
				return false;
			}
		} catch (BadResponseError $e) {
			\OC::$server->get(LoggerInterface::class)->error($e->getMessage(), [
				'exception' => $e,
				'app' => 'files_external',
			]);
			return false;
		}

		$dateTime = $object->lastModified ? \DateTime::createFromFormat(\DateTime::RFC1123, $object->lastModified) : false;
		$mtime = $dateTime ? $dateTime->getTimestamp() : null;
		$objectMetadata = $object->getMetadata();
		if (isset($objectMetadata['timestamp'])) {
			$mtime = $objectMetadata['timestamp'];
		}

		if (!empty($mtime)) {
			$mtime = floor($mtime);
		}

		$stat = [];
		$stat['size'] = (int)$object->contentLength;
		$stat['mtime'] = $mtime;
		$stat['atime'] = time();
		return $stat;
	}

	public function filetype(string $path) {
		$path = $this->normalizePath($path);

		if ($path !== '.' && $this->doesObjectExist($path)) {
			return 'file';
		}

		if ($path !== '.') {
			$path .= '/';
		}

		if ($this->doesObjectExist($path)) {
			return 'dir';
		}
	}

	public function unlink(string $path): bool {
		$path = $this->normalizePath($path);

		if ($this->is_dir($path)) {
			return $this->rmdir($path);
		}

		try {
			$this->objectStore->deleteObject($path);
			$this->objectCache->remove($path);
			$this->objectCache->remove($path . '/');
		} catch (BadResponseError $e) {
			if ($e->getResponse()->getStatusCode() !== 404) {
				\OC::$server->get(LoggerInterface::class)->error($e->getMessage(), [
					'exception' => $e,
					'app' => 'files_external',
				]);
				throw $e;
			}
		}

		return true;
	}

	public function fopen(string $path, string $mode) {
		$path = $this->normalizePath($path);

		switch ($mode) {
			case 'a':
			case 'ab':
			case 'a+':
				return false;
			case 'r':
			case 'rb':
				try {
					return $this->objectStore->readObject($path);
				} catch (BadResponseError $e) {
					\OC::$server->get(LoggerInterface::class)->error($e->getMessage(), [
						'exception' => $e,
						'app' => 'files_external',
					]);
					return false;
				}
			case 'w':
			case 'wb':
			case 'r+':
			case 'w+':
			case 'wb+':
			case 'x':
			case 'x+':
			case 'c':
			case 'c+':
				if (strrpos($path, '.') !== false) {
					$ext = substr($path, strrpos($path, '.'));
				} else {
					$ext = '';
				}
				$tmpFile = \OC::$server->getTempManager()->getTemporaryFile($ext);
				// Fetch existing file if required
				if ($mode[0] !== 'w' && $this->file_exists($path)) {
					if ($mode[0] === 'x') {
						// File cannot already exist
						return false;
					}
					$source = $this->fopen($path, 'r');
					file_put_contents($tmpFile, $source);
				}
				$handle = fopen($tmpFile, $mode);
				return CallbackWrapper::wrap($handle, null, null, function () use ($path, $tmpFile): void {
					$this->writeBack($tmpFile, $path);
				});
		}
	}

	public function touch(string $path, ?int $mtime = null): bool {
		$path = $this->normalizePath($path);
		if (is_null($mtime)) {
			$mtime = time();
		}
		$metadata = ['timestamp' => (string)$mtime];
		if ($this->file_exists($path)) {
			if ($this->is_dir($path) && $path !== '.') {
				$path .= '/';
			}

			$object = $this->fetchObject($path);
			if ($object->mergeMetadata($metadata)) {
				// invalidate target object to force repopulation on fetch
				$this->objectCache->remove($path);
			}
			return true;
		} else {
			$mimeType = $this->mimeDetector->detectPath($path);
			$this->getContainer()->createObject([
				'name' => $path,
				'content' => '',
				'headers' => ['content-type' => 'httpd/unix-directory']
			]);
			// invalidate target object to force repopulation on fetch
			$this->objectCache->remove($path);
			return true;
		}
	}

	public function copy(string $source, string $target): bool {
		$source = $this->normalizePath($source);
		$target = $this->normalizePath($target);

		$fileType = $this->filetype($source);
		if ($fileType) {
			// make way
			$this->unlink($target);
		}

		if ($fileType === 'file') {
			try {
				$sourceObject = $this->fetchObject($source);
				$sourceObject->copy([
					'destination' => $this->bucket . '/' . $target
				]);
				// invalidate target object to force repopulation on fetch
				$this->objectCache->remove($target);
				$this->objectCache->remove($target . '/');
			} catch (BadResponseError $e) {
				\OC::$server->get(LoggerInterface::class)->error($e->getMessage(), [
					'exception' => $e,
					'app' => 'files_external',
				]);
				return false;
			}
		} elseif ($fileType === 'dir') {
			try {
				$sourceObject = $this->fetchObject($source . '/');
				$sourceObject->copy([
					'destination' => $this->bucket . '/' . $target . '/'
				]);
				// invalidate target object to force repopulation on fetch
				$this->objectCache->remove($target);
				$this->objectCache->remove($target . '/');
			} catch (BadResponseError $e) {
				\OC::$server->get(LoggerInterface::class)->error($e->getMessage(), [
					'exception' => $e,
					'app' => 'files_external',
				]);
				return false;
			}

			$dh = $this->opendir($source);
			while (($file = readdir($dh)) !== false) {
				if (Filesystem::isIgnoredDir($file)) {
					continue;
				}

				$source = $source . '/' . $file;
				$target = $target . '/' . $file;
				$this->copy($source, $target);
			}
		} else {
			//file does not exist
			return false;
		}

		return true;
	}

	public function rename(string $source, string $target): bool {
		$source = $this->normalizePath($source);
		$target = $this->normalizePath($target);

		$fileType = $this->filetype($source);

		if ($fileType === 'dir' || $fileType === 'file') {
			// copy
			if ($this->copy($source, $target) === false) {
				return false;
			}

			// cleanup
			if ($this->unlink($source) === false) {
				throw new \Exception('failed to remove original');
				$this->unlink($target);
				return false;
			}

			return true;
		}

		return false;
	}

	public function getId(): string {
		return $this->id;
	}

	/**
	 * Returns the initialized object store container.
	 *
	 * @return Container
	 * @throws StorageAuthException
	 * @throws StorageNotAvailableException
	 */
	public function getContainer(): Container {
		if (is_null($this->container)) {
			$this->container = $this->connectionFactory->getContainer();

			if (!$this->file_exists('.')) {
				$this->mkdir('.');
			}
		}
		return $this->container;
	}

	public function writeBack(string $tmpFile, string $path): void {
		$fileData = fopen($tmpFile, 'r');
		$this->objectStore->writeObject($path, $fileData, $this->mimeDetector->detectPath($path));
		// invalidate target object to force repopulation on fetch
		$this->objectCache->remove($path);
		unlink($tmpFile);
	}

	public function hasUpdated(string $path, int $time): bool {
		if ($this->is_file($path)) {
			return parent::hasUpdated($path, $time);
		}
		$path = $this->normalizePath($path);
		$dh = $this->opendir($path);
		$content = [];
		while (($file = readdir($dh)) !== false) {
			$content[] = $file;
		}
		if ($path === '.') {
			$path = '';
		}
		$cachedContent = $this->getCache()->getFolderContents($path);
		$cachedNames = array_map(function ($content) {
			return $content['name'];
		}, $cachedContent);
		sort($cachedNames);
		sort($content);
		return $cachedNames !== $content;
	}

	/**
	 * check if curl is installed
	 */
	public static function checkDependencies(): bool {
		return true;
	}
}
