<?php
/**
 * Copyright (c) 2012 Henrik Kjölhede <hkjolhede@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Files\Storage;

set_include_path(get_include_path() . PATH_SEPARATOR .
	\OC_App::getAppPath('files_external') . '/3rdparty/phpseclib/phpseclib');
require 'Net/SFTP.php';

class SFTP extends \OC\Files\Storage\Common {
	private $host;
	private $user;
	private $password;
	private $root;

	private $client;

	private static $tempFiles = array();

	public function __construct($params) {
		$this->host = $params['host'];
		$proto = strpos($this->host, '://');
		if ($proto != false) {
			$this->host = substr($this->host, $proto+3);
		}
		$this->user = $params['user'];
		$this->password = $params['password'];
		$this->root = isset($params['root']) ? $this->cleanPath($params['root']) : '/';
		if ($this->root[0] != '/') $this->root = '/' . $this->root;
		if (substr($this->root, -1, 1) != '/') $this->root .= '/';

		$host_keys = $this->read_host_keys();

		$this->client = new \Net_SFTP($this->host);
		if (!$this->client->login($this->user, $this->password)) {
			throw new \Exception('Login failed');
		}

		$current_host_key = $this->client->getServerPublicHostKey();

		if (array_key_exists($this->host, $host_keys)) {
			if ($host_keys[$this->host] != $current_host_key) {
				throw new \Exception('Host public key does not match known key');
			}
		} else {
			$host_keys[$this->host] = $current_host_key;
			$this->write_host_keys($host_keys);
		}

		if(!$this->file_exists('')){
			$this->mkdir('');
		}
	}

	public function test() {
		if (!isset($params['host']) || !isset($params['user']) || !isset($params['password'])) {
			throw new \Exception("Required parameters not set");
		}
	}

	public function getId(){
		return 'sftp::' . $this->user . '@' . $this->host . '/' . $this->root;
	}

	private function abs_path($path) {
		return $this->root . $this->cleanPath($path);
	}

	private function host_keys_path() {
		try {
			$storage_view = \OCP\Files::getStorage('files_external');
			if ($storage_view) {
				return \OCP\Config::getSystemValue('datadirectory') .
					$storage_view->getAbsolutePath('') .
					'ssh_host_keys';
			}
		} catch (\Exception $e) {
		}
		return false;
	}

	private function write_host_keys($keys) {
		try {
			$key_path = $this->host_keys_path();
			$fp = fopen($key_path, 'w');
			foreach ($keys as $host => $key) {
				fwrite($fp, $host . '::' . $key . "\n");
			}
			fclose($fp);
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	private function read_host_keys() {
		try {
			$key_path = $this->host_keys_path();
			if (file_exists($key_path)) {
				$hosts = array();
				$keys = array();
				$lines = file($key_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
				if ($lines) {
					foreach ($lines as $line) {
						$host_key_arr = explode("::", $line, 2);
						if (count($host_key_arr) == 2) {
							$hosts[] = $host_key_arr[0];
							$keys[] = $host_key_arr[1];
						}
					}
					return array_combine($hosts, $keys);
				}
			}
		} catch (\Exception $e) {
		}
		return array();
	}

	public function mkdir($path) {
		try {
			return $this->client->mkdir($this->abs_path($path));
		} catch (\Exception $e) {
			return false;
		}
	}

	public function rmdir($path) {
		try {
			return $this->client->delete($this->abs_path($path), true);
		} catch (\Exception $e) {
			return false;
		}
	}

	public function opendir($path) {
		try {
			$list = $this->client->nlist($this->abs_path($path));

			$id = md5('sftp:' . $path);
			$dir_stream = array();
			foreach($list as $file) {
				if ($file != '.' && $file != '..') {
					$dir_stream[] = $file;
				}
			}
			\OC\Files\Stream\Dir::register($id, $dir_stream);
			return opendir('fakedir://' . $id);
		} catch(\Exception $e) {
			return false;
		}
	}

	public function filetype($path) {
		try {
			$stat = $this->client->stat($this->abs_path($path));
			if ($stat['type'] == NET_SFTP_TYPE_REGULAR) return 'file';
			if ($stat['type'] == NET_SFTP_TYPE_DIRECTORY) return 'dir';
		} catch (\Exeption $e) {
		}
		return false;
	}

	public function isReadable($path) {
		return true;
	}

	public function isUpdatable($path) {
		return true;
	}

	public function file_exists($path) {
		try {
			return $this->client->stat($this->abs_path($path)) === false ? false : true;
		} catch (\Exception $e) {
			return false;
		}
	}

	public function unlink($path) {
		try {
			return $this->client->delete($this->abs_path($path), true);
		} catch (\Exception $e) {
			return false;
		}
	}

	public function fopen($path, $mode) {
		try {
			$abs_path = $this->abs_path($path);
			switch($mode) {
				case 'r':
				case 'rb':
					if ( !$this->file_exists($path)) return false;
					if (strrpos($path, '.')!==false) {
						$ext=substr($path, strrpos($path, '.'));
					} else {
						$ext='';
					}
					$tmp = \OC_Helper::tmpFile($ext);
					$this->getFile($abs_path, $tmp);
					return fopen($tmp, $mode);

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
					if (strrpos($path, '.')!==false) {
						$ext=substr($path, strrpos($path, '.'));
					} else {
						$ext='';
					}
					$tmpFile=\OC_Helper::tmpFile($ext);
					\OC\Files\Stream\Close::registerCallback($tmpFile, array($this, 'writeBack'));
					if ($this->file_exists($path)) {
						$this->getFile($abs_path, $tmpFile);
					}
					self::$tempFiles[$tmpFile]=$abs_path;
					return fopen('close://'.$tmpFile, $mode);
			}
		} catch (\Exception $e) {
		}
		return false;
	}

	public function writeBack($tmpFile) {
		if (array_key_exists($tmpFile, self::$tempFiles)) {
			$this->uploadFile($tmpFile, self::$tempFiles[$tmpFile]);
			unlink($tmpFile);
			unset(self::$tempFiles[$tmpFile]);
		}
	}

	public function touch($path, $mtime=null) {
		try {
			if (!is_null($mtime)) return false;
			if (!$this->file_exists($path)) {
				$this->client->put($this->abs_path($path), '');
			} else {
				return false;
			}
		} catch (\Exception $e) {
			return false;
		}
		return true;
	}

	public function getFile($path, $target) {
		$this->client->get($path, $target);
	}

	public function uploadFile($path, $target) {
		$this->client->put($target, $path, NET_SFTP_LOCAL_FILE);
	}

	public function rename($source, $target) {
		try {
			return $this->client->rename($this->abs_path($source), $this->abs_path($target));
		} catch (\Exception $e) {
			return false;
		}
	}

	public function stat($path) {
		try {
			$stat = $this->client->stat($this->abs_path($path));

			$mtime = $stat ? $stat['mtime'] : -1;
			$size = $stat ? $stat['size'] : 0;

			return array('mtime' => $mtime, 'size' => $size, 'ctime' => -1);
		} catch (\Exception $e) {
			return false;
		}

	}
}
