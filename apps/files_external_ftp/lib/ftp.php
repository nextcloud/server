<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
namespace OCA\Files_External_FTP;

use League\Flysystem\FileNotFoundException;
use OC\Files\Storage\Flysystem;
use OC\Files\Storage\PolyFill\CopyDirectory;

class FTP extends Flysystem {
	use CopyDirectory;

	private $host;
	private $password;
	private $username;
	private $secure;
	private $port;

	/**
	 * @var \League\Flysystem\Adapter\FTP
	 */
	private $adapter;

	public function __construct($params) {
		if (isset($params['host']) && isset($params['username']) && isset($params['password'])) {
			$this->host = $params['host'];
			$this->username = $params['username'];
			$this->password = $params['password'];
			if (isset($params['secure'])) {
				if (is_string($params['secure'])) {
					$this->secure = ($params['secure'] === 'true');
				} else {
					$this->secure = (bool)$params['secure'];
				}
			} else {
				$this->secure = false;
			}
			$this->root = isset($params['root']) ? $params['root'] : '/';
			$this->port = isset($params['port']) ? $params['port'] : 21;

			$this->adapter = new Adapter([
				'host' => $params['host'],
				'username' => $params['username'],
				'password' => $params['password'],
				'port' => $this->port,
				'ssl' => $this->secure
			]);
			$this->buildFlySystem($this->adapter);
		} else {
			throw new \Exception('Creating \OCA\Files_External_FTP\FTP storage failed');
		}
	}

	public function getId() {
		return 'ftp::' . $this->username . '@' . $this->host . '/' . $this->root;
	}

	public function disconnect() {
		$this->adapter->disconnect();
	}

	public function __destruct() {
		$this->disconnect();
	}

	public static function checkDependencies() {
		if (function_exists('ftp_login')) {
			return (true);
		} else {
			return ['ftp'];
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function filemtime($path) {
		if ($this->is_dir($path)) {
			$connection = $this->flysystem->getAdapter()->getConnection();
			$listing = ftp_rawlist($connection, '-lna ' . $this->buildPath($path));
			$metadata = $this->flysystem->getAdapter()->normalizeObject($listing[0], '');
			return $metadata['timestamp'];
		} else {
			return parent::filemtime($path);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function rmdir($path) {
		try {
			$result = @$this->flysystem->deleteDir($this->buildPath($path));
			// recursive rmdir support depends on the ftp server
			if ($result) {
				return $result;
			} else {
				return $this->recursiveRmDir($path);
			}
		} catch (FileNotFoundException $e) {
			return false;
		}
	}

	/**
	 * @param string $path
	 * @return bool
	 */
	private function recursiveRmDir($path) {
		$contents = $this->flysystem->listContents($this->buildPath($path));
		$result = true;
		foreach ($contents as $content) {
			if ($content['type'] === 'dir') {
				$result = $result && $this->recursiveRmDir($path . '/' . $content['basename']);
			} else {
				$result = $result && $this->flysystem->delete($this->buildPath($path . '/' . $content['basename']));
			}
		}
		$result = $result && @$this->flysystem->deleteDir($this->buildPath($path));

		return $result;
	}

	/**
	 * {@inheritdoc}
	 */
	public function stat($path) {
		$info = $this->flysystem->getWithMetadata($this->buildPath($path), ['size']);
		return [
			'mtime' => $this->filemtime($path),
			'size' => $info['size']
		];
	}
}
