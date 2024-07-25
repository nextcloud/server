<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author André Gaul <gaul@web-yard.de>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christian Berendt <berendt@b1-systems.de>
 * @author Christopher T. Johnson <ctjctj@gmail.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author enoch <lanxenet@hotmail.com>
 * @author Johan Björk <johanimon@gmail.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Martin Mattel <martin.mattel@diemattels.at>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Philipp Kapfer <philipp.kapfer@gmx.at>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
namespace OCA\Files_External\Lib\Storage;

use Aws\S3\Exception\S3Exception;
use Icewind\Streams\CallbackWrapper;
use Icewind\Streams\CountWrapper;
use Icewind\Streams\IteratorDirectory;
use OC\Files\Cache\CacheEntry;
use OC\Files\ObjectStore\S3ConnectionTrait;
use OC\Files\ObjectStore\S3ObjectTrait;
use OCP\Cache\CappedMemoryCache;
use OCP\Constants;
use OCP\Files\FileInfo;
use OCP\Files\IMimeTypeDetector;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\Server;
use Psr\Log\LoggerInterface;

class AmazonS3 extends \OC\Files\Storage\Common {
	use S3ConnectionTrait;
	use S3ObjectTrait;

	private LoggerInterface $logger;

	public function needsPartFile() {
		return false;
	}

	/** @var CappedMemoryCache<array|false> */
	private CappedMemoryCache $objectCache;

	/** @var CappedMemoryCache<bool> */
	private CappedMemoryCache $directoryCache;

	/** @var CappedMemoryCache<array> */
	private CappedMemoryCache $filesCache;

	private IMimeTypeDetector $mimeDetector;
	private ?bool $versioningEnabled = null;
	private ICache $memCache;

	public function __construct($parameters) {
		parent::__construct($parameters);
		$this->parseParams($parameters);
		$this->id = 'amazon::external::' . md5($this->params['hostname'] . ':' . $this->params['bucket'] . ':' . $this->params['key']);
		$this->objectCache = new CappedMemoryCache();
		$this->directoryCache = new CappedMemoryCache();
		$this->filesCache = new CappedMemoryCache();
		$this->mimeDetector = Server::get(IMimeTypeDetector::class);
		/** @var ICacheFactory $cacheFactory */
		$cacheFactory = Server::get(ICacheFactory::class);
		$this->memCache = $cacheFactory->createLocal('s3-external');
		$this->logger = Server::get(LoggerInterface::class);
	}

	/**
	 * @param string $path
	 * @return string correctly encoded path
	 */
	private function normalizePath($path) {
		$path = trim($path, '/');

		if (!$path) {
			$path = '.';
		}

		return $path;
	}

	private function isRoot($path) {
		return $path === '.';
	}

	private function cleanKey($path) {
		if ($this->isRoot($path)) {
			return '/';
		}
		return $path;
	}

	private function clearCache() {
		$this->objectCache = new CappedMemoryCache();
		$this->directoryCache = new CappedMemoryCache();
		$this->filesCache = new CappedMemoryCache();
	}

	private function invalidateCache($key) {
		unset($this->objectCache[$key]);
		$keys = array_keys($this->objectCache->getData());
		$keyLength = strlen($key);
		foreach ($keys as $existingKey) {
			if (substr($existingKey, 0, $keyLength) === $key) {
				unset($this->objectCache[$existingKey]);
			}
		}
		unset($this->filesCache[$key]);
		$keys = array_keys($this->directoryCache->getData());
		$keyLength = strlen($key);
		foreach ($keys as $existingKey) {
			if (substr($existingKey, 0, $keyLength) === $key) {
				unset($this->directoryCache[$existingKey]);
			}
		}
		unset($this->directoryCache[$key]);
	}

	/**
	 * @return array|false
	 */
	private function headObject(string $key) {
		if (!isset($this->objectCache[$key])) {
			try {
				$this->objectCache[$key] = $this->getConnection()->headObject([
					'Bucket' => $this->bucket,
					'Key' => $key
				])->toArray();
			} catch (S3Exception $e) {
				if ($e->getStatusCode() >= 500) {
					throw $e;
				}
				$this->objectCache[$key] = false;
			}
		}

		if (is_array($this->objectCache[$key]) && !isset($this->objectCache[$key]["Key"])) {
			/** @psalm-suppress InvalidArgument Psalm doesn't understand nested arrays well */
			$this->objectCache[$key]["Key"] = $key;
		}
		return $this->objectCache[$key];
	}

	/**
	 * Return true if directory exists
	 *
	 * There are no folders in s3. A folder like structure could be archived
	 * by prefixing files with the folder name.
	 *
	 * Implementation from flysystem-aws-s3-v3:
	 * https://github.com/thephpleague/flysystem-aws-s3-v3/blob/8241e9cc5b28f981e0d24cdaf9867f14c7498ae4/src/AwsS3Adapter.php#L670-L694
	 *
	 * @param $path
	 * @return bool
	 * @throws \Exception
	 */
	private function doesDirectoryExist($path) {
		if ($path === '.' || $path === '') {
			return true;
		}
		$path = rtrim($path, '/') . '/';

		if (isset($this->directoryCache[$path])) {
			return $this->directoryCache[$path];
		}
		try {
			// Maybe this isn't an actual key, but a prefix.
			// Do a prefix listing of objects to determine.
			$result = $this->getConnection()->listObjectsV2([
				'Bucket' => $this->bucket,
				'Prefix' => $path,
				'MaxKeys' => 1,
			]);

			if (isset($result['Contents'])) {
				$this->directoryCache[$path] = true;
				return true;
			}

			// empty directories have their own object
			$object = $this->headObject($path);

			if ($object) {
				$this->directoryCache[$path] = true;
				return true;
			}
		} catch (S3Exception $e) {
			if ($e->getStatusCode() >= 400 && $e->getStatusCode() < 500) {
				$this->directoryCache[$path] = false;
			}
			throw $e;
		}


		$this->directoryCache[$path] = false;
		return false;
	}

	/**
	 * Remove a file or folder
	 *
	 * @param string $path
	 * @return bool
	 */
	protected function remove($path) {
		// remember fileType to reduce http calls
		$fileType = $this->filetype($path);
		if ($fileType === 'dir') {
			return $this->rmdir($path);
		} elseif ($fileType === 'file') {
			return $this->unlink($path);
		} else {
			return false;
		}
	}

	public function mkdir($path) {
		$path = $this->normalizePath($path);

		if ($this->is_dir($path)) {
			return false;
		}

		try {
			$this->getConnection()->putObject([
				'Bucket' => $this->bucket,
				'Key' => $path . '/',
				'Body' => '',
				'ContentType' => FileInfo::MIMETYPE_FOLDER
			]);
			$this->testTimeout();
		} catch (S3Exception $e) {
			$this->logger->error($e->getMessage(), [
				'app' => 'files_external',
				'exception' => $e,
			]);
			return false;
		}

		$this->invalidateCache($path);

		return true;
	}

	public function file_exists($path) {
		return $this->filetype($path) !== false;
	}


	public function rmdir($path) {
		$path = $this->normalizePath($path);

		if ($this->isRoot($path)) {
			return $this->clearBucket();
		}

		if (!$this->file_exists($path)) {
			return false;
		}

		$this->invalidateCache($path);
		return $this->batchDelete($path);
	}

	protected function clearBucket() {
		$this->clearCache();
		return $this->batchDelete();
	}

	private function batchDelete($path = null) {
		// TODO explore using https://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Aws.S3.BatchDelete.html
		$params = [
			'Bucket' => $this->bucket
		];
		if ($path !== null) {
			$params['Prefix'] = $path . '/';
		}
		try {
			$connection = $this->getConnection();
			// Since there are no real directories on S3, we need
			// to delete all objects prefixed with the path.
			do {
				// instead of the iterator, manually loop over the list ...
				$objects = $connection->listObjects($params);
				// ... so we can delete the files in batches
				if (isset($objects['Contents'])) {
					$connection->deleteObjects([
						'Bucket' => $this->bucket,
						'Delete' => [
							'Objects' => $objects['Contents']
						]
					]);
					$this->testTimeout();
				}
				// we reached the end when the list is no longer truncated
			} while ($objects['IsTruncated']);
			if ($path !== '' && $path !== null) {
				$this->deleteObject($path);
			}
		} catch (S3Exception $e) {
			$this->logger->error($e->getMessage(), [
				'app' => 'files_external',
				'exception' => $e,
			]);
			return false;
		}
		return true;
	}

	public function opendir($path) {
		try {
			$content = iterator_to_array($this->getDirectoryContent($path));
			return IteratorDirectory::wrap(array_map(function (array $item) {
				return $item['name'];
			}, $content));
		} catch (S3Exception $e) {
			return false;
		}
	}

	public function stat($path) {
		$path = $this->normalizePath($path);

		if ($this->is_dir($path)) {
			$stat = $this->getDirectoryMetaData($path);
		} else {
			$object = $this->headObject($path);
			if ($object === false) {
				return false;
			}
			$stat = $this->objectToMetaData($object);
		}
		$stat['atime'] = time();

		return $stat;
	}

	/**
	 * Return content length for object
	 *
	 * When the information is already present (e.g. opendir has been called before)
	 * this value is return. Otherwise a headObject is emitted.
	 *
	 * @param $path
	 * @return int|mixed
	 */
	private function getContentLength($path) {
		if (isset($this->filesCache[$path])) {
			return (int)$this->filesCache[$path]['ContentLength'];
		}

		$result = $this->headObject($path);
		if (isset($result['ContentLength'])) {
			return (int)$result['ContentLength'];
		}

		return 0;
	}

	/**
	 * Return last modified for object
	 *
	 * When the information is already present (e.g. opendir has been called before)
	 * this value is return. Otherwise a headObject is emitted.
	 *
	 * @param $path
	 * @return mixed|string
	 */
	private function getLastModified($path) {
		if (isset($this->filesCache[$path])) {
			return $this->filesCache[$path]['LastModified'];
		}

		$result = $this->headObject($path);
		if (isset($result['LastModified'])) {
			return $result['LastModified'];
		}

		return 'now';
	}

	public function is_dir($path) {
		$path = $this->normalizePath($path);

		if (isset($this->filesCache[$path])) {
			return false;
		}

		try {
			return $this->doesDirectoryExist($path);
		} catch (S3Exception $e) {
			$this->logger->error($e->getMessage(), [
				'app' => 'files_external',
				'exception' => $e,
			]);
			return false;
		}
	}

	public function filetype($path) {
		$path = $this->normalizePath($path);

		if ($this->isRoot($path)) {
			return 'dir';
		}

		try {
			if (isset($this->directoryCache[$path]) && $this->directoryCache[$path]) {
				return 'dir';
			}
			if (isset($this->filesCache[$path]) || $this->headObject($path)) {
				return 'file';
			}
			if ($this->doesDirectoryExist($path)) {
				return 'dir';
			}
		} catch (S3Exception $e) {
			$this->logger->error($e->getMessage(), [
				'app' => 'files_external',
				'exception' => $e,
			]);
			return false;
		}

		return false;
	}

	public function getPermissions($path) {
		$type = $this->filetype($path);
		if (!$type) {
			return 0;
		}
		return $type === 'dir' ? Constants::PERMISSION_ALL : Constants::PERMISSION_ALL - Constants::PERMISSION_CREATE;
	}

	public function unlink($path) {
		$path = $this->normalizePath($path);

		if ($this->is_dir($path)) {
			return $this->rmdir($path);
		}

		try {
			$this->deleteObject($path);
			$this->invalidateCache($path);
		} catch (S3Exception $e) {
			$this->logger->error($e->getMessage(), [
				'app' => 'files_external',
				'exception' => $e,
			]);
			return false;
		}

		return true;
	}

	public function fopen($path, $mode) {
		$path = $this->normalizePath($path);

		switch ($mode) {
			case 'r':
			case 'rb':
				// Don't try to fetch empty files
				$stat = $this->stat($path);
				if (is_array($stat) && isset($stat['size']) && $stat['size'] === 0) {
					return fopen('php://memory', $mode);
				}

				try {
					return $this->readObject($path);
				} catch (\Exception $e) {
					$this->logger->error($e->getMessage(), [
						'app' => 'files_external',
						'exception' => $e,
					]);
					return false;
				}
			case 'w':
			case 'wb':
				$tmpFile = \OC::$server->getTempManager()->getTemporaryFile();

				$handle = fopen($tmpFile, 'w');
				return CallbackWrapper::wrap($handle, null, null, function () use ($path, $tmpFile) {
					$this->writeBack($tmpFile, $path);
				});
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
				if (strrpos($path, '.') !== false) {
					$ext = substr($path, strrpos($path, '.'));
				} else {
					$ext = '';
				}
				$tmpFile = \OC::$server->getTempManager()->getTemporaryFile($ext);
				if ($this->file_exists($path)) {
					$source = $this->readObject($path);
					file_put_contents($tmpFile, $source);
				}

				$handle = fopen($tmpFile, $mode);
				return CallbackWrapper::wrap($handle, null, null, function () use ($path, $tmpFile) {
					$this->writeBack($tmpFile, $path);
				});
		}
		return false;
	}

	public function touch($path, $mtime = null) {
		if (is_null($mtime)) {
			$mtime = time();
		}
		$metadata = [
			'lastmodified' => gmdate(\DateTime::RFC1123, $mtime)
		];

		try {
			if ($this->file_exists($path)) {
				return false;
			}

			$mimeType = $this->mimeDetector->detectPath($path);
			$this->getConnection()->putObject([
				'Bucket' => $this->bucket,
				'Key' => $this->cleanKey($path),
				'Metadata' => $metadata,
				'Body' => '',
				'ContentType' => $mimeType,
				'MetadataDirective' => 'REPLACE',
			]);
			$this->testTimeout();
		} catch (S3Exception $e) {
			$this->logger->error($e->getMessage(), [
				'app' => 'files_external',
				'exception' => $e,
			]);
			return false;
		}

		$this->invalidateCache($path);
		return true;
	}

	public function copy($source, $target, $isFile = null) {
		$source = $this->normalizePath($source);
		$target = $this->normalizePath($target);

		if ($isFile === true || $this->is_file($source)) {
			try {
				$this->copyObject($source, $target, [
					'StorageClass' => $this->storageClass,
				]);
				$this->testTimeout();
			} catch (S3Exception $e) {
				$this->logger->error($e->getMessage(), [
					'app' => 'files_external',
					'exception' => $e,
				]);
				return false;
			}
		} else {
			$this->remove($target);

			try {
				$this->mkdir($target);
				$this->testTimeout();
			} catch (S3Exception $e) {
				$this->logger->error($e->getMessage(), [
					'app' => 'files_external',
					'exception' => $e,
				]);
				return false;
			}

			foreach ($this->getDirectoryContent($source) as $item) {
				$childSource = $source . '/' . $item['name'];
				$childTarget = $target . '/' . $item['name'];
				$this->copy($childSource, $childTarget, $item['mimetype'] !== FileInfo::MIMETYPE_FOLDER);
			}
		}

		$this->invalidateCache($target);

		return true;
	}

	public function rename($source, $target) {
		$source = $this->normalizePath($source);
		$target = $this->normalizePath($target);

		if ($this->is_file($source)) {
			if ($this->copy($source, $target) === false) {
				return false;
			}

			if ($this->unlink($source) === false) {
				$this->unlink($target);
				return false;
			}
		} else {
			if ($this->copy($source, $target) === false) {
				return false;
			}

			if ($this->rmdir($source) === false) {
				$this->rmdir($target);
				return false;
			}
		}

		return true;
	}

	public function test() {
		$this->getConnection()->headBucket([
			'Bucket' => $this->bucket
		]);
		return true;
	}

	public function getId() {
		return $this->id;
	}

	public function writeBack($tmpFile, $path) {
		try {
			$source = fopen($tmpFile, 'r');
			$this->writeObject($path, $source, $this->mimeDetector->detectPath($path));
			$this->invalidateCache($path);

			unlink($tmpFile);
			return true;
		} catch (S3Exception $e) {
			$this->logger->error($e->getMessage(), [
				'app' => 'files_external',
				'exception' => $e,
			]);
			return false;
		}
	}

	/**
	 * check if curl is installed
	 */
	public static function checkDependencies() {
		return true;
	}

	public function getDirectoryContent($directory): \Traversable {
		$path = $this->normalizePath($directory);

		if ($this->isRoot($path)) {
			$path = '';
		} else {
			$path .= '/';
		}

		$results = $this->getConnection()->getPaginator('ListObjectsV2', [
			'Bucket' => $this->bucket,
			'Delimiter' => '/',
			'Prefix' => $path,
		]);

		foreach ($results as $result) {
			// sub folders
			if (is_array($result['CommonPrefixes'])) {
				foreach ($result['CommonPrefixes'] as $prefix) {
					$dir = $this->getDirectoryMetaData($prefix['Prefix']);
					if ($dir) {
						yield $dir;
					}
				}
			}
			if (is_array($result['Contents'])) {
				foreach ($result['Contents'] as $object) {
					$this->objectCache[$object['Key']] = $object;
					if ($object['Key'] !== $path) {
						yield $this->objectToMetaData($object);
					}
				}
			}
		}
	}

	private function objectToMetaData(array $object): array {
		return [
			'name' => basename($object['Key']),
			'mimetype' => $this->mimeDetector->detectPath($object['Key']),
			'mtime' => strtotime($object['LastModified']),
			'storage_mtime' => strtotime($object['LastModified']),
			'etag' => trim($object['ETag'], '"'),
			'permissions' => Constants::PERMISSION_ALL - Constants::PERMISSION_CREATE,
			'size' => (int)($object['Size'] ?? $object['ContentLength']),
		];
	}

	private function getDirectoryMetaData(string $path): ?array {
		$path = trim($path, '/');
		// when versioning is enabled, delete markers are returned as part of CommonPrefixes
		// resulting in "ghost" folders, verify that each folder actually exists
		if ($this->versioningEnabled() && !$this->doesDirectoryExist($path)) {
			return null;
		}
		$cacheEntry = $this->getCache()->get($path);
		if ($cacheEntry instanceof CacheEntry) {
			return $cacheEntry->getData();
		} else {
			return [
				'name' => basename($path),
				'mimetype' => FileInfo::MIMETYPE_FOLDER,
				'mtime' => time(),
				'storage_mtime' => time(),
				'etag' => uniqid(),
				'permissions' => Constants::PERMISSION_ALL,
				'size' => -1,
			];
		}
	}

	public function versioningEnabled(): bool {
		if ($this->versioningEnabled === null) {
			$cached = $this->memCache->get('versioning-enabled::' . $this->getBucket());
			if ($cached === null) {
				$this->versioningEnabled = $this->getVersioningStatusFromBucket();
				$this->memCache->set('versioning-enabled::' . $this->getBucket(), $this->versioningEnabled, 60);
			} else {
				$this->versioningEnabled = $cached;
			}
		}
		return $this->versioningEnabled;
	}

	protected function getVersioningStatusFromBucket(): bool {
		try {
			$result = $this->getConnection()->getBucketVersioning(['Bucket' => $this->getBucket()]);
			return $result->get('Status') === 'Enabled';
		} catch (S3Exception $s3Exception) {
			// This is needed for compatibility with Storj gateway which does not support versioning yet
			if ($s3Exception->getAwsErrorCode() === 'NotImplemented' || $s3Exception->getAwsErrorCode() === 'AccessDenied') {
				return false;
			}
			throw $s3Exception;
		}
	}

	public function hasUpdated($path, $time) {
		// for files we can get the proper mtime
		if ($path !== '' && $object = $this->headObject($path)) {
			$stat = $this->objectToMetaData($object);
			return $stat['mtime'] > $time;
		} else {
			// for directories, the only real option we have is to do a prefix listing and iterate over all objects
			// however, since this is just as expensive as just re-scanning the directory, we can simply return true
			// and have the scanner figure out if anything has actually changed
			return true;
		}
	}

	public function writeStream(string $path, $stream, ?int $size = null): int {
		if ($size === null) {
			$size = 0;
			// track the number of bytes read from the input stream to return as the number of written bytes.
			$stream = CountWrapper::wrap($stream, function (int $writtenSize) use (&$size) {
				$size = $writtenSize;
			});
		}

		if (!is_resource($stream)) {
			throw new \InvalidArgumentException("Invalid stream provided");
		}

		$path = $this->normalizePath($path);
		$this->writeObject($path, $stream, $this->mimeDetector->detectPath($path));
		$this->invalidateCache($path);

		return $size;
	}
}
