<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Benjamin Liles <benliles@arch.tamu.edu>
 * @author Christian Berendt <berendt@b1-systems.de>
 * @author Daniel Tosello <tosello.daniel@gmail.com>
 * @author Felix Moeller <mail@felixmoeller.de>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Martin Mattel <martin.mattel@diemattels.at>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Philipp Kapfer <philipp.kapfer@gmx.at>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tim Dettrick <t.dettrick@uq.edu.au>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_External\Lib\Storage;

use Guzzle\Http\Url;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Icewind\Streams\IteratorDirectory;
use OpenCloud;
use OpenCloud\Common\Exceptions;
use OpenCloud\OpenStack;
use OpenCloud\Rackspace;
use OpenCloud\ObjectStore\Resource\DataObject;
use OpenCloud\ObjectStore\Exception;

class Swift extends \OC\Files\Storage\Common {

	/**
	 * @var \OpenCloud\ObjectStore\Service
	 */
	private $connection;
	/**
	 * @var \OpenCloud\ObjectStore\Resource\Container
	 */
	private $container;
	/**
	 * @var \OpenCloud\OpenStack
	 */
	private $anchor;
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
	/**
	 * @var array
	 */
	private static $tmpFiles = array();

	/**
	 * Key value cache mapping path to data object. Maps path to
	 * \OpenCloud\OpenStack\ObjectStorage\Resource\DataObject for existing
	 * paths and path to false for not existing paths.
	 * @var \OCP\ICache
	 */
	private $objectCache;

	/**
	 * @param string $path
	 */
	private function normalizePath($path) {
		$path = trim($path, '/');

		if (!$path) {
			$path = '.';
		}

		$path = str_replace('#', '%23', $path);

		return $path;
	}

	const SUBCONTAINER_FILE = '.subcontainers';

	/**
	 * translate directory path to container name
	 *
	 * @param string $path
	 * @return string
	 */

	/**
	 * Fetches an object from the API.
	 * If the object is cached already or a
	 * failed "doesn't exist" response was cached,
	 * that one will be returned.
	 *
	 * @param string $path
	 * @return \OpenCloud\OpenStack\ObjectStorage\Resource\DataObject|bool object
	 * or false if the object did not exist
	 */
	private function fetchObject($path) {
		if ($this->objectCache->hasKey($path)) {
			// might be "false" if object did not exist from last check
			return $this->objectCache->get($path);
		}
		try {
			$object = $this->getContainer()->getPartialObject($path);
			$this->objectCache->set($path, $object);
			return $object;
		} catch (ClientErrorResponseException $e) {
			// this exception happens when the object does not exist, which
			// is expected in most cases
			$this->objectCache->set($path, false);
			return false;
		} catch (ClientErrorResponseException $e) {
			// Expected response is "404 Not Found", so only log if it isn't
			if ($e->getResponse()->getStatusCode() !== 404) {
				\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
			}
			return false;
		}
	}

	/**
	 * Returns whether the given path exists.
	 *
	 * @param string $path
	 *
	 * @return bool true if the object exist, false otherwise
	 */
	private function doesObjectExist($path) {
		return $this->fetchObject($path) !== false;
	}

	public function __construct($params) {
		if ((empty($params['key']) and empty($params['password']))
			or empty($params['user']) or empty($params['bucket'])
			or empty($params['region'])
		) {
			throw new \Exception("API Key or password, Username, Bucket and Region have to be configured.");
		}

		$this->id = 'swift::' . $params['user'] . md5($params['bucket']);

		$bucketUrl = Url::factory($params['bucket']);
		if ($bucketUrl->isAbsolute()) {
			$this->bucket = end(($bucketUrl->getPathSegments()));
			$params['endpoint_url'] = $bucketUrl->addPath('..')->normalizePath();
		} else {
			$this->bucket = $params['bucket'];
		}

		if (empty($params['url'])) {
			$params['url'] = 'https://identity.api.rackspacecloud.com/v2.0/';
		}

		if (empty($params['service_name'])) {
			$params['service_name'] = 'cloudFiles';
		}

		$this->params = $params;
		// FIXME: private class...
		$this->objectCache = new \OC\Cache\CappedMemoryCache();
	}

	public function mkdir($path) {
		$path = $this->normalizePath($path);

		if ($this->is_dir($path)) {
			return false;
		}

		if ($path !== '.') {
			$path .= '/';
		}

		try {
			$customHeaders = array('content-type' => 'httpd/unix-directory');
			$metadataHeaders = DataObject::stockHeaders(array());
			$allHeaders = $customHeaders + $metadataHeaders;
			$this->getContainer()->uploadObject($path, '', $allHeaders);
			// invalidate so that the next access gets the real object
			// with all properties
			$this->objectCache->remove($path);
		} catch (Exceptions\CreateUpdateError $e) {
			\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
			return false;
		}

		return true;
	}

	public function file_exists($path) {
		$path = $this->normalizePath($path);

		if ($path !== '.' && $this->is_dir($path)) {
			$path .= '/';
		}

		return $this->doesObjectExist($path);
	}

	public function rmdir($path) {
		$path = $this->normalizePath($path);

		if (!$this->is_dir($path) || !$this->isDeletable($path)) {
			return false;
		}

		$dh = $this->opendir($path);
		while ($file = readdir($dh)) {
			if (\OC\Files\Filesystem::isIgnoredDir($file)) {
				continue;
			}

			if ($this->is_dir($path . '/' . $file)) {
				$this->rmdir($path . '/' . $file);
			} else {
				$this->unlink($path . '/' . $file);
			}
		}

		try {
			$this->getContainer()->dataObject()->setName($path . '/')->delete();
			$this->objectCache->remove($path . '/');
		} catch (Exceptions\DeleteError $e) {
			\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
			return false;
		}

		return true;
	}

	public function opendir($path) {
		$path = $this->normalizePath($path);

		if ($path === '.') {
			$path = '';
		} else {
			$path .= '/';
		}

		$path = str_replace('%23', '#', $path); // the prefix is sent as a query param, so revert the encoding of #

		try {
			$files = array();
			/** @var OpenCloud\Common\Collection $objects */
			$objects = $this->getContainer()->objectList(array(
				'prefix' => $path,
				'delimiter' => '/'
			));

			/** @var OpenCloud\ObjectStore\Resource\DataObject $object */
			foreach ($objects as $object) {
				$file = basename($object->getName());
				if ($file !== basename($path)) {
					$files[] = $file;
				}
			}

			return IteratorDirectory::wrap($files);
		} catch (\Exception $e) {
			\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
			return false;
		}

	}

	public function stat($path) {
		$path = $this->normalizePath($path);

		if ($path === '.') {
			$path = '';
		} else if ($this->is_dir($path)) {
			$path .= '/';
		}

		try {
			/** @var DataObject $object */
			$object = $this->fetchObject($path);
			if (!$object) {
				return false;
			}
		} catch (ClientErrorResponseException $e) {
			\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
			return false;
		}

		$dateTime = \DateTime::createFromFormat(\DateTime::RFC1123, $object->getLastModified());
		if ($dateTime !== false) {
			$mtime = $dateTime->getTimestamp();
		} else {
			$mtime = null;
		}
		$objectMetadata = $object->getMetadata();
		$metaTimestamp = $objectMetadata->getProperty('timestamp');
		if (isset($metaTimestamp)) {
			$mtime = $metaTimestamp;
		}

		if (!empty($mtime)) {
			$mtime = floor($mtime);
		}

		$stat = array();
		$stat['size'] = (int)$object->getContentLength();
		$stat['mtime'] = $mtime;
		$stat['atime'] = time();
		return $stat;
	}

	public function filetype($path) {
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

	public function unlink($path) {
		$path = $this->normalizePath($path);

		if ($this->is_dir($path)) {
			return $this->rmdir($path);
		}

		try {
			$this->getContainer()->dataObject()->setName($path)->delete();
			$this->objectCache->remove($path);
			$this->objectCache->remove($path . '/');
		} catch (ClientErrorResponseException $e) {
			if ($e->getResponse()->getStatusCode() !== 404) {
				\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
			}
			return false;
		}

		return true;
	}

	public function fopen($path, $mode) {
		$path = $this->normalizePath($path);

		switch ($mode) {
			case 'r':
			case 'rb':
				try {
					$c = $this->getContainer();
					$streamFactory = new \Guzzle\Stream\PhpStreamRequestFactory();
					$streamInterface = $streamFactory->fromRequest(
						$c->getClient()
							->get($c->getUrl($path)));
					$streamInterface->rewind();
					$stream = $streamInterface->getStream();
					stream_context_set_option($stream, 'swift','content', $streamInterface);
					if(!strrpos($streamInterface
						->getMetaData('wrapper_data')[0], '404 Not Found')) {
						return $stream;
					}
					return false;
				} catch (\Guzzle\Http\Exception\BadResponseException $e) {
					\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
					return false;
				}
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
				if (strrpos($path, '.') !== false) {
					$ext = substr($path, strrpos($path, '.'));
				} else {
					$ext = '';
				}
				$tmpFile = \OCP\Files::tmpFile($ext);
				\OC\Files\Stream\Close::registerCallback($tmpFile, array($this, 'writeBack'));
				// Fetch existing file if required
				if ($mode[0] !== 'w' && $this->file_exists($path)) {
					if ($mode[0] === 'x') {
						// File cannot already exist
						return false;
					}
					$source = $this->fopen($path, 'r');
					file_put_contents($tmpFile, $source);
					// Seek to end if required
					if ($mode[0] === 'a') {
						fseek($tmpFile, 0, SEEK_END);
					}
				}
				self::$tmpFiles[$tmpFile] = $path;

				return fopen('close://' . $tmpFile, $mode);
		}
	}

	public function touch($path, $mtime = null) {
		$path = $this->normalizePath($path);
		if (is_null($mtime)) {
			$mtime = time();
		}
		$metadata = array('timestamp' => $mtime);
		if ($this->file_exists($path)) {
			if ($this->is_dir($path) && $path != '.') {
				$path .= '/';
			}

			$object = $this->fetchObject($path);
			if ($object->saveMetadata($metadata)) {
				// invalidate target object to force repopulation on fetch
				$this->objectCache->remove($path);
			}
			return true;
		} else {
			$mimeType = \OC::$server->getMimeTypeDetector()->detectPath($path);
			$customHeaders = array('content-type' => $mimeType);
			$metadataHeaders = DataObject::stockHeaders($metadata);
			$allHeaders = $customHeaders + $metadataHeaders;
			$this->getContainer()->uploadObject($path, '', $allHeaders);
			// invalidate target object to force repopulation on fetch
			$this->objectCache->remove($path);
			return true;
		}
	}

	public function copy($path1, $path2) {
		$path1 = $this->normalizePath($path1);
		$path2 = $this->normalizePath($path2);

		$fileType = $this->filetype($path1);
		if ($fileType === 'file') {

			// make way
			$this->unlink($path2);

			try {
				$source = $this->fetchObject($path1);
				$source->copy($this->bucket . '/' . $path2);
				// invalidate target object to force repopulation on fetch
				$this->objectCache->remove($path2);
				$this->objectCache->remove($path2 . '/');
			} catch (ClientErrorResponseException $e) {
				\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
				return false;
			}

		} else if ($fileType === 'dir') {

			// make way
			$this->unlink($path2);

			try {
				$source = $this->fetchObject($path1 . '/');
				$source->copy($this->bucket . '/' . $path2 . '/');
				// invalidate target object to force repopulation on fetch
				$this->objectCache->remove($path2);
				$this->objectCache->remove($path2 . '/');
			} catch (ClientErrorResponseException $e) {
				\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
				return false;
			}

			$dh = $this->opendir($path1);
			while ($file = readdir($dh)) {
				if (\OC\Files\Filesystem::isIgnoredDir($file)) {
					continue;
				}

				$source = $path1 . '/' . $file;
				$target = $path2 . '/' . $file;
				$this->copy($source, $target);
			}

		} else {
			//file does not exist
			return false;
		}

		return true;
	}

	public function rename($path1, $path2) {
		$path1 = $this->normalizePath($path1);
		$path2 = $this->normalizePath($path2);

		$fileType = $this->filetype($path1);

		if ($fileType === 'dir' || $fileType === 'file') {
			// copy
			if ($this->copy($path1, $path2) === false) {
				return false;
			}

			// cleanup
			if ($this->unlink($path1) === false) {
				$this->unlink($path2);
				return false;
			}

			return true;
		}

		return false;
	}

	public function getId() {
		return $this->id;
	}

	/**
	 * Returns the connection
	 *
	 * @return OpenCloud\ObjectStore\Service connected client
	 * @throws \Exception if connection could not be made
	 */
	public function getConnection() {
		if (!is_null($this->connection)) {
			return $this->connection;
		}

		$settings = array(
			'username' => $this->params['user'],
		);

		if (!empty($this->params['password'])) {
			$settings['password'] = $this->params['password'];
		} else if (!empty($this->params['key'])) {
			$settings['apiKey'] = $this->params['key'];
		}

		if (!empty($this->params['tenant'])) {
			$settings['tenantName'] = $this->params['tenant'];
		}

		if (!empty($this->params['timeout'])) {
			$settings['timeout'] = $this->params['timeout'];
		}

		if (isset($settings['apiKey'])) {
			$this->anchor = new Rackspace($this->params['url'], $settings);
		} else {
			$this->anchor = new OpenStack($this->params['url'], $settings);
		}

		$connection = $this->anchor->objectStoreService($this->params['service_name'], $this->params['region']);

		if (!empty($this->params['endpoint_url'])) {
			$endpoint = $connection->getEndpoint();
			$endpoint->setPublicUrl($this->params['endpoint_url']);
			$endpoint->setPrivateUrl($this->params['endpoint_url']);
			$connection->setEndpoint($endpoint);
		}

		$this->connection = $connection;

		return $this->connection;
	}

	/**
	 * Returns the initialized object store container.
	 *
	 * @return OpenCloud\ObjectStore\Resource\Container
	 */
	public function getContainer() {
		if (!is_null($this->container)) {
			return $this->container;
		}

		try {
			$this->container = $this->getConnection()->getContainer($this->bucket);
		} catch (ClientErrorResponseException $e) {
			$this->container = $this->getConnection()->createContainer($this->bucket);
		}

		if (!$this->file_exists('.')) {
			$this->mkdir('.');
		}

		return $this->container;
	}

	public function writeBack($tmpFile) {
		if (!isset(self::$tmpFiles[$tmpFile])) {
			return false;
		}
		$fileData = fopen($tmpFile, 'r');
		$this->getContainer()->uploadObject(self::$tmpFiles[$tmpFile], $fileData);
		// invalidate target object to force repopulation on fetch
		$this->objectCache->remove(self::$tmpFiles[$tmpFile]);
		unlink($tmpFile);
	}

	public function hasUpdated($path, $time) {
		if ($this->is_file($path)) {
			return parent::hasUpdated($path, $time);
		}
		$path = $this->normalizePath($path);
		$dh = $this->opendir($path);
		$content = array();
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
		return $cachedNames != $content;
	}

	/**
	 * check if curl is installed
	 */
	public static function checkDependencies() {
		return true;
	}

}
