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

set_include_path(get_include_path() . PATH_SEPARATOR .
	\OC_App::getAppPath('files_external') . '/3rdparty/php-opencloud/lib');
require_once 'openstack.php';

use \OpenCloud;
use \OpenCloud\Common\Exceptions;

class Swift extends \OC\Files\Storage\Common {

	/**
	 * @var \OpenCloud\ObjectStore
	 */
	private $connection;
	/**
	 * @var \OpenCloud\ObjectStore\Container
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

		return $path;
	}

	const SUBCONTAINER_FILE='.subcontainers';

	/**
	 * translate directory path to container name
	 * @param string $path
	 * @return string
	 */
	private function getContainerName($path) {
		$path=trim(trim($this->root, '/') . "/".$path, '/.');
		return str_replace('/', '\\', $path);
	}

	/**
	 * @param string $path
	 */
	private function doesObjectExist($path) {
		try {
			$object = $this->container->DataObject($path);
			return true;
		} catch (Exceptions\ObjFetchError $e) {
			\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
			return false;
		} catch (Exceptions\HttpError $e) {
			\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
			return false;
		}
	}

	public function __construct($params) {
		if ((!isset($params['key']) and !isset($params['password']))
		 	or !isset($params['user']) or !isset($params['bucket'])
			or !isset($params['region'])) {
			throw new \Exception("API Key or password, Username, Bucket and Region have to be configured.");
		}

		$this->id = 'swift::' . $params['user'] . md5($params['bucket']);
		$this->bucket = $params['bucket'];

		if (!isset($params['url'])) {
			$params['url'] = 'https://identity.api.rackspacecloud.com/v2.0/';
		}

		if (!isset($params['service_name'])) {
			$params['service_name'] = 'cloudFiles';
		}

		$settings = array(
			'username' => $params['user'],
			
		);

		if (isset($params['password'])) {
			$settings['password'] = $params['password'];
		} else if (isset($params['key'])) {
			$settings['apiKey'] = $params['key'];
		}

		if (isset($params['tenant'])) {
			$settings['tenantName'] = $params['tenant'];
		}

		$this->anchor = new \OpenCloud\OpenStack($params['url'], $settings);

		if (isset($params['timeout'])) {
			$this->anchor->setHttpTimeout($params['timeout']);
		}

		$this->connection = $this->anchor->ObjectStore($params['service_name'], $params['region'], 'publicURL');

		try {
			$this->container = $this->connection->Container($this->bucket);
		} catch (Exceptions\ContainerNotFoundError $e) {
			$this->container = $this->connection->Container();
			$this->container->Create(array('name' => $this->bucket));
		}

		if (!$this->file_exists('.')) {
			$this->mkdir('.');
		}
	}

	public function mkdir($path) {
		$path = $this->normalizePath($path);

		if ($this->is_dir($path)) {
			return false;
		}

		if($path !== '.') {
			$path .= '/';
		}

		try {
			$object = $this->container->DataObject();
			$object->Create(array(
				'name' => $path,
				'content_type' => 'httpd/unix-directory'
			));
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

		if (!$this->is_dir($path)) {
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
			$object = $this->container->DataObject($path . '/');
			$object->Delete();
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

		try {
			$files = array();
			$objects = $this->container->ObjectList(array(
				'prefix' => $path,
				'delimiter' => '/'
			));

			while ($object = $objects->Next()) {
				$file = basename($object->Name());
				if ($file !== basename($path)) {
					$files[] = $file;
				}
			}

			\OC\Files\Stream\Dir::register('swift' . $path, $files);
			return opendir('fakedir://swift' . $path);
		} catch (Exception $e) {
			\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
			return false;
		}

	}

	public function stat($path) {
		$path = $this->normalizePath($path);

		if ($this->is_dir($path) && $path != '.') {
			$path .= '/';
		}

		try {
			$object = $this->container->DataObject($path);
		} catch (Exceptions\ObjFetchError $e) {
			\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
			return false;
		}

		$mtime = $object->extra_headers['X-Timestamp'];
		if (isset($object->extra_headers['X-Object-Meta-Timestamp'])) {
			$mtime = $object->extra_headers['X-Object-Meta-Timestamp'];
		}

		if (!empty($mtime)) {
			$mtime = floor($mtime);
		}

		$stat = array();
		$stat['size'] = $object->content_length;
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

		try {
			$object = $this->container->DataObject($path);
			$object->Delete();
		} catch (Exceptions\DeleteError $e) {
			\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
			return false;
		} catch (Exceptions\ObjFetchError $e) {
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
					$object = $this->container->DataObject($path);
				} catch (Exceptions\ObjFetchError $e) {
					\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
					return false;
				}
				try {
					$object->SaveToFilename($tmpFile);
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
			$object = $this->container->DataObject($path);
			return $object->extra_headers["Content-Type"];
		}
		return false;
	}

	public function touch($path, $mtime = null) {
		$path = $this->normalizePath($path);
		if ($this->file_exists($path)) {
			if ($this->is_dir($path) && $path != '.') {
				$path .= '/';
			}

			$object = $this->container->DataObject($path);
			if( is_null($mtime)) {
				$mtime = time();
			}
			$settings = array(
				'name' => $path,
				'extra_headers' => array(
					'X-Object-Meta-Timestamp' => $mtime
				)
			);
			return $object->UpdateMetadata($settings);
		} else {
			$object = $this->container->DataObject();
			if (is_null($mtime)) {
				$mtime = time();
			}
			$settings = array(
				'name' => $path,
				'content_type' => 'text/plain',
				'extra_headers' => array(
					'X-Object-Meta-Timestamp' => $mtime
				)
			);
			return $object->Create($settings);
		}
	}

	public function copy($path1, $path2) {
		$path1 = $this->normalizePath($path1);
		$path2 = $this->normalizePath($path2);

		if ($this->is_file($path1)) {
			try {
				$source = $this->container->DataObject($path1);
				$target = $this->container->DataObject();
				$target->Create(array(
					'name' => $path2,
				));
				$source->Copy($target);
			} catch (Exceptions\ObjectCopyError $e) {
				\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
				return false;
			}
		} else {
			if ($this->file_exists($path2)) {
				return false;
			}

			try {
				$source = $this->container->DataObject($path1 . '/');
				$target = $this->container->DataObject();
				$target->Create(array(
					'name' => $path2 . '/',
				));
				$source->Copy($target);
			} catch (Exceptions\ObjectCopyError $e) {
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
		}

		return true;
	}

	public function rename($path1, $path2) {
		$path1 = $this->normalizePath($path1);
		$path2 = $this->normalizePath($path2);

		if ($this->is_file($path1)) {
			if ($this->copy($path1, $path2) === false) {
				return false;
			}

			if ($this->unlink($path1) === false) {
				$this->unlink($path2);
				return false;
			}
		} else {
			if ($this->file_exists($path2)) {
				return false;
			}

			if ($this->copy($path1, $path2) === false) {
				return false;
			}

			if ($this->rmdir($path1) === false) {
				$this->rmdir($path2);
				return false;
			}
		}

		return true;
	}

	public function getId() {
		return $this->id;
	}

	public function getConnection() {
		return $this->connection;
	}

	public function writeBack($tmpFile) {
		if (!isset(self::$tmpFiles[$tmpFile])) {
			return false;
		}

		$object = $this->container->DataObject();
		$object->Create(array(
			'name' => self::$tmpFiles[$tmpFile],
			'content_type' => \OC_Helper::getMimeType($tmpFile)
		), $tmpFile);
		unlink($tmpFile);
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
