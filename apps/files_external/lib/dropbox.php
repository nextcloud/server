<?php

/**
* ownCloud
*
* @author Michael Gapczynski
* @copyright 2012 Michael Gapczynski mtgap@owncloud.com
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

require_once 'Dropbox/autoload.php';

class Dropbox extends \OC\Files\Storage\Common {

	private $dropbox;
	private $root;
	private $id;
	private $metaData = array();

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
			$oauth = new \Dropbox_OAuth_Curl($params['app_key'], $params['app_secret']);
			$oauth->setToken($params['token'], $params['token_secret']);
			$this->dropbox = new \Dropbox_API($oauth, 'dropbox');
			$this->mkdir('');
		} else {
			throw new \Exception('Creating \OC\Files\Storage\Dropbox storage failed');
		}
	}

	private function getMetaData($path, $list = false) {
		$path = $this->root.$path;
		if ( ! $list && isset($this->metaData[$path])) {
			return $this->metaData[$path];
		} else {
			if ($list) {
				try {
					$response = $this->dropbox->getMetaData($path);
				} catch (\Exception $exception) {
					\OCP\Util::writeLog('files_external', $exception->getMessage(), \OCP\Util::ERROR);
					return false;
				}
				if ($response && isset($response['contents'])) {
					$contents = $response['contents'];
					// Cache folder's contents
					foreach ($contents as $file) {
						$this->metaData[$path.'/'.basename($file['path'])] = $file;
					}
					unset($response['contents']);
					$this->metaData[$path] = $response;
				}
				$this->metaData[$path] = $response;
				// Return contents of folder only
				return $contents;
			} else {
				try {
					$response = $this->dropbox->getMetaData($path, 'false');
					$this->metaData[$path] = $response;
					return $response;
				} catch (\Exception $exception) {
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
		$contents = $this->getMetaData($path, true);
		if ($contents) {
			$files = array();
			foreach ($contents as $file) {
				$files[] = basename($file['path']);
			}
			\OC\Files\Stream\Dir::register('dropbox'.$path, $files);
			return opendir('fakedir://dropbox'.$path);
		}
		return false;
	}

	public function stat($path) {
		$metaData = $this->getMetaData($path);
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
			$metaData = $this->getMetaData($path);
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

	public function isReadable($path) {
		return $this->file_exists($path);
	}

	public function isUpdatable($path) {
		return $this->file_exists($path);
	}

	public function file_exists($path) {
		if ($path == '' || $path == '/') {
			return true;
		}
		if ($this->getMetaData($path)) {
			return true;
		}
		return false;
	}

	public function unlink($path) {
		$path = $this->root.$path;
		try {
			$this->dropbox->delete($path);
			return true;
		} catch (\Exception $exception) {
			\OCP\Util::writeLog('files_external', $exception->getMessage(), \OCP\Util::ERROR);
			return false;
		}
	}

	public function rename($path1, $path2) {
		$path1 = $this->root.$path1;
		$path2 = $this->root.$path2;
		try {
			$this->dropbox->move($path1, $path2);
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
				$tmpFile = \OC_Helper::tmpFile();
				try {
					$data = $this->dropbox->getFile($path);
					file_put_contents($tmpFile, $data);
					return fopen($tmpFile, 'r');
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
				$tmpFile = \OC_Helper::tmpFile($ext);
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
			} catch (\Exception $exception) {
				\OCP\Util::writeLog('files_external', $exception->getMessage(), \OCP\Util::ERROR);
			}
		}
	}

	public function getMimeType($path) {
		if ($this->filetype($path) == 'dir') {
			return 'httpd/unix-directory';
		} else {
			$metaData = $this->getMetaData($path);
			if ($metaData) {
				return $metaData['mime_type'];
			}
		}
		return false;
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
		return false;
	}

}
