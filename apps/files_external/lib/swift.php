<?php

/**
 * ownCloud
 *
 * @author Christian Berendt
 * @copyright 2013 Christian Berendt berendt@b1-systems.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OC\Files\Storage;

use Guzzle\Http\Exception\ClientErrorResponseException;
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
	private function getContainerName($path) {
		$path = trim(trim($this->root, '/') . "/" . $path, '/.');
		return str_replace('/', '\\', $path);
	}

	/**
	 * @param string $path
	 */
	private function doesObjectExist($path) {
		try {
			$this->getContainer()->getPartialObject($path);
			return true;
		} catch (ClientErrorResponseException $e) {
			\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
			return false;
		}
	}

	public function __construct($params) {
		if ((empty($params['key']) and empty($params['password']))
			or empty($params['user']) or empty($params['bucket'])
			or empty($params['region'])
		) {
			throw new \Exception("API Key or password, Username, Bucket and Region have to be configured.");
		}

		$this->id = 'swift::' . $params['user'] . md5($params['bucket']);
		$this->bucket = $params['bucket'];

		if (empty($params['url'])) {
			$params['url'] = 'https://identity.api.rackspacecloud.com/v2.0/';
		}

		if (empty($params['service_name'])) {
			$params['service_name'] = 'cloudFiles';
		}

		$this->params = $params;
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
			if ($file === '.' || $file === '..') {
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

			\OC\Files\Stream\Dir::register('swift' . $path, $files);
			return opendir('fakedir://swift' . $path);
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
			$object = $this->getContainer()->getPartialObject($path);
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
		} catch (ClientErrorResponseException $e) {
			\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
			return false;
		}

		return true;
	}

	public function fopen($path, $mode) {
		$path = $this->normalizePath($path);

		switch ($mode) {
			case 'r':
			case 'rb':
				$tmpFile = \OC_Helper::tmpFile();
				self::$tmpFiles[$tmpFile] = $path;
				try {
					$object = $this->getContainer()->getObject($path);
				} catch (ClientErrorResponseException $e) {
					\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
					return false;
				} catch (Exception\ObjectNotFoundException $e) {
					\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
					return false;
				}
				try {
					$objectContent = $object->getContent();
					$objectContent->rewind();
					$stream = $objectContent->getStream();
					file_put_contents($tmpFile, $stream);
				} catch (Exceptions\IOError $e) {
					\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
					return false;
				}
				return fopen($tmpFile, 'r');
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
				$tmpFile = \OC_Helper::tmpFile($ext);
				\OC\Files\Stream\Close::registerCallback($tmpFile, array($this, 'writeBack'));
				if ($this->file_exists($path)) {
					$source = $this->fopen($path, 'r');
					file_put_contents($tmpFile, $source);
				}
				self::$tmpFiles[$tmpFile] = $path;

				return fopen('close://' . $tmpFile, $mode);
		}
	}

	public function getMimeType($path) {
		$path = $this->normalizePath($path);

		if ($this->is_dir($path)) {
			return 'httpd/unix-directory';
		} else if ($this->file_exists($path)) {
			$object = $this->getContainer()->getPartialObject($path);
			return $object->getContentType();
		}
		return false;
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

			$object = $this->getContainer()->getPartialObject($path);
			$object->saveMetadata($metadata);
			return true;
		} else {
			$mimeType = \OC_Helper::getMimetypeDetector()->detectPath($path);
			$customHeaders = array('content-type' => $mimeType);
			$metadataHeaders = DataObject::stockHeaders($metadata);
			$allHeaders = $customHeaders + $metadataHeaders;
			$this->getContainer()->uploadObject($path, '', $allHeaders);
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
				$source = $this->getContainer()->getPartialObject($path1);
				$source->copy($this->bucket . '/' . $path2);
			} catch (ClientErrorResponseException $e) {
				\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
				return false;
			}

		} else if ($fileType === 'dir') {

			// make way
			$this->unlink($path2);

			try {
				$source = $this->getContainer()->getPartialObject($path1 . '/');
				$source->copy($this->bucket . '/' . $path2 . '/');
			} catch (ClientErrorResponseException $e) {
				\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
				return false;
			}

			$dh = $this->opendir($path1);
			while ($file = readdir($dh)) {
				if ($file === '.' || $file === '..') {
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

			// make way
			$this->unlink($path2);

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

		$this->connection = $this->anchor->objectStoreService($this->params['service_name'], $this->params['region']);

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
		if (function_exists('curl_init')) {
			return true;
		} else {
			return array('curl');
		}
	}

}
