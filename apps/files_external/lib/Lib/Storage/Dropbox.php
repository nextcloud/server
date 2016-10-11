<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Philipp Kapfer <philipp.kapfer@gmx.at>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_External\Lib\Storage;

use GuzzleHttp\Exception\RequestException;
use Icewind\Streams\IteratorDirectory;
use Icewind\Streams\RetryWrapper;
use OCP\Files\StorageNotAvailableException;

require_once __DIR__ . '/../../../3rdparty/Dropbox/autoload.php';

class Dropbox extends \OC\Files\Storage\Common {

	private $dropbox;
	private $root;
	private $id;
	private $metaData = array();
	private $oauth;

	private static $tempFiles = array();

	public function __construct($params) {
		if (isset($params['configured']) && $params['configured'] == 'true'
			&& isset($params['app_key'])
			&& isset($params['app_secret'])
			&& isset($params['token'])
			&& isset($params['token_secret'])
		) {
			$this->root = isset($params['root']) ? $params['root'] : '';
			$this->id = 'dropbox::'.$params['app_key'] . $params['token']. '/' . $this->root;
			$this->oauth = new \Dropbox_OAuth_Curl($params['app_key'], $params['app_secret']);
			$this->oauth->setToken($params['token'], $params['token_secret']);
			// note: Dropbox_API connection is lazy
			$this->dropbox = new \Dropbox_API($this->oauth, 'auto');
		} else {
			throw new \Exception('Creating Dropbox storage failed');
		}
	}

	/**
	 * @param string $path
	 */
	private function deleteMetaData($path) {
		$path = ltrim($this->root.$path, '/');
		if (isset($this->metaData[$path])) {
			unset($this->metaData[$path]);
			return true;
		}
		return false;
	}

	private function setMetaData($path, $metaData) {
		$this->metaData[ltrim($path, '/')] = $metaData;
	}

	/**
	 * Returns the path's metadata
	 * @param string $path path for which to return the metadata
	 * @param bool $list if true, also return the directory's contents
	 * @return mixed directory contents if $list is true, file metadata if $list is
	 * false, null if the file doesn't exist or "false" if the operation failed
	 */
	private function getDropBoxMetaData($path, $list = false) {
		$path = ltrim($this->root.$path, '/');
		if ( ! $list && isset($this->metaData[$path])) {
			return $this->metaData[$path];
		} else {
			if ($list) {
				try {
					$response = $this->dropbox->getMetaData($path);
				} catch (\Dropbox_Exception_Forbidden $e) {
					throw new StorageNotAvailableException('Dropbox API rate limit exceeded', StorageNotAvailableException::STATUS_ERROR, $e);
				} catch (\Exception $exception) {
					\OCP\Util::writeLog('files_external', $exception->getMessage(), \OCP\Util::ERROR);
					return false;
				}
				$contents = array();
				if ($response && isset($response['contents'])) {
					// Cache folder's contents
					foreach ($response['contents'] as $file) {
						if (!isset($file['is_deleted']) || !$file['is_deleted']) {
							$this->setMetaData($path.'/'.basename($file['path']), $file);
							$contents[] = $file;
						}
					}
					unset($response['contents']);
				}
				if (!isset($response['is_deleted']) || !$response['is_deleted']) {
					$this->setMetaData($path, $response);
				}
				// Return contents of folder only
				return $contents;
			} else {
				try {
					$requestPath = $path;
					if ($path === '.') {
						$requestPath = '';
					}

					$response = $this->dropbox->getMetaData($requestPath, 'false');
					if (!isset($response['is_deleted']) || !$response['is_deleted']) {
						$this->setMetaData($path, $response);
						return $response;
					}
					return null;
				} catch (\Dropbox_Exception_Forbidden $e) {
					throw new StorageNotAvailableException('Dropbox API rate limit exceeded', StorageNotAvailableException::STATUS_ERROR, $e);
				} catch (\Exception $exception) {
					if ($exception instanceof \Dropbox_Exception_NotFound) {
						// don't log, might be a file_exist check
						return false;
					}
					\OCP\Util::writeLog('files_external', $exception->getMessage(), \OCP\Util::ERROR);
					return false;
				}
			}
		}
	}

	public function getId(){
		return $this->id;
	}

	public function mkdir($path) {
		$path = $this->root.$path;
		try {
			$this->dropbox->createFolder($path);
			return true;
		} catch (\Exception $exception) {
			\OCP\Util::writeLog('files_external', $exception->getMessage(), \OCP\Util::ERROR);
			return false;
		}
	}

	public function rmdir($path) {
		return $this->unlink($path);
	}

	public function opendir($path) {
		$contents = $this->getDropBoxMetaData($path, true);
		if ($contents !== false) {
			$files = array();
			foreach ($contents as $file) {
				$files[] = basename($file['path']);
			}
			return IteratorDirectory::wrap($files);
		}
		return false;
	}

	public function stat($path) {
		$metaData = $this->getDropBoxMetaData($path);
		if ($metaData) {
			$stat['size'] = $metaData['bytes'];
			$stat['atime'] = time();
			$stat['mtime'] = (isset($metaData['modified'])) ? strtotime($metaData['modified']) : time();
			return $stat;
		}
		return false;
	}

	public function filetype($path) {
		if ($path == '' || $path == '/') {
			return 'dir';
		} else {
			$metaData = $this->getDropBoxMetaData($path);
			if ($metaData) {
				if ($metaData['is_dir'] == 'true') {
					return 'dir';
				} else {
					return 'file';
				}
			}
		}
		return false;
	}

	public function file_exists($path) {
		if ($path == '' || $path == '/') {
			return true;
		}
		if ($this->getDropBoxMetaData($path)) {
			return true;
		}
		return false;
	}

	public function unlink($path) {
		try {
			$this->dropbox->delete($this->root.$path);
			$this->deleteMetaData($path);
			return true;
		} catch (\Exception $exception) {
			\OCP\Util::writeLog('files_external', $exception->getMessage(), \OCP\Util::ERROR);
			return false;
		}
	}

	public function rename($path1, $path2) {
		try {
			// overwrite if target file exists and is not a directory
			$destMetaData = $this->getDropBoxMetaData($path2);
			if (isset($destMetaData) && $destMetaData !== false && !$destMetaData['is_dir']) {
				$this->unlink($path2);
			}
			$this->dropbox->move($this->root.$path1, $this->root.$path2);
			$this->deleteMetaData($path1);
			return true;
		} catch (\Exception $exception) {
			\OCP\Util::writeLog('files_external', $exception->getMessage(), \OCP\Util::ERROR);
			return false;
		}
	}

	public function copy($path1, $path2) {
		$path1 = $this->root.$path1;
		$path2 = $this->root.$path2;
		try {
			$this->dropbox->copy($path1, $path2);
			return true;
		} catch (\Exception $exception) {
			\OCP\Util::writeLog('files_external', $exception->getMessage(), \OCP\Util::ERROR);
			return false;
		}
	}

	public function fopen($path, $mode) {
		$path = $this->root.$path;
		switch ($mode) {
			case 'r':
			case 'rb':
				try {
					// slashes need to stay
					$encodedPath = str_replace('%2F', '/', rawurlencode(trim($path, '/')));
					$downloadUrl = 'https://api-content.dropbox.com/1/files/auto/' . $encodedPath;
					$headers = $this->oauth->getOAuthHeader($downloadUrl, [], 'GET');

					$client = \OC::$server->getHTTPClientService()->newClient();
					try {
						$response = $client->get($downloadUrl, [
							'headers' => $headers,
							'stream' => true,
						]);
					} catch (RequestException $e) {
						if (!is_null($e->getResponse())) {
							if ($e->getResponse()->getStatusCode() === 404) {
								return false;
							} else {
								throw $e;
							}
						} else {
							throw $e;
						}
					}

					$handle = $response->getBody();
					return RetryWrapper::wrap($handle);
				} catch (\Exception $exception) {
					\OCP\Util::writeLog('files_external', $exception->getMessage(), \OCP\Util::ERROR);
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
				if ($this->file_exists($path)) {
					$source = $this->fopen($path, 'r');
					file_put_contents($tmpFile, $source);
				}
				self::$tempFiles[$tmpFile] = $path;
				return fopen('close://'.$tmpFile, $mode);
		}
		return false;
	}

	public function writeBack($tmpFile) {
		if (isset(self::$tempFiles[$tmpFile])) {
			$handle = fopen($tmpFile, 'r');
			try {
				$this->dropbox->putFile(self::$tempFiles[$tmpFile], $handle);
				unlink($tmpFile);
				$this->deleteMetaData(self::$tempFiles[$tmpFile]);
			} catch (\Exception $exception) {
				\OCP\Util::writeLog('files_external', $exception->getMessage(), \OCP\Util::ERROR);
			}
		}
	}

	public function free_space($path) {
		try {
			$info = $this->dropbox->getAccountInfo();
			return $info['quota_info']['quota'] - $info['quota_info']['normal'];
		} catch (\Exception $exception) {
			\OCP\Util::writeLog('files_external', $exception->getMessage(), \OCP\Util::ERROR);
			return false;
		}
	}

	public function touch($path, $mtime = null) {
		if ($this->file_exists($path)) {
			return false;
		} else {
			$this->file_put_contents($path, '');
		}
		return true;
	}

	/**
	 * check if curl is installed
	 */
	public static function checkDependencies() {
		return true;
	}

}
