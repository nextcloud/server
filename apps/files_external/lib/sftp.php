<?php
/**
 * Copyright (c) 2012 Henrik Kjölhede <hkjolhede@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Files\Storage;

/**
* Uses phpseclib's Net_SFTP class and the Net_SFTP_Stream stream wrapper to
* provide access to SFTP servers.
*/
class SFTP extends \OC\Files\Storage\Common {
	private $host;
	private $user;
	private $password;
	private $root;

	/**
	* @var \Net_SFTP
	*/
	private $client;

	private static $tempFiles = array();

	public function __construct($params) {
		// The sftp:// scheme has to be manually registered via inclusion of
		// the 'Net/SFTP/Stream.php' file which registers the Net_SFTP_Stream
		// stream wrapper as a side effect.
		// A slightly better way to register the stream wrapper is available
		// since phpseclib 0.3.7 in the form of a static call to
		// Net_SFTP_Stream::register() which will trigger autoloading if
		// necessary.
		// TODO: Call Net_SFTP_Stream::register() instead when phpseclib is
		//       updated to 0.3.7 or higher.
		require_once 'Net/SFTP/Stream.php';

		$this->host = $params['host'];
		$proto = strpos($this->host, '://');
		if ($proto != false) {
			$this->host = substr($this->host, $proto+3);
		}
		$this->user = $params['user'];
		$this->password = $params['password'];
		$this->root
			= isset($params['root']) ? $this->cleanPath($params['root']) : '/';

		if ($this->root[0] != '/') {
			 $this->root = '/' . $this->root;
		}

		if (substr($this->root, -1, 1) != '/') {
			$this->root .= '/';
		}
	}

	/**
	 * Returns the connection.
	 *
	 * @return \Net_SFTP connected client instance
	 * @throws \Exception when the connection failed
	 */
	public function getConnection() {
		if (!is_null($this->client)) {
			return $this->client;
		}

		$hostKeys = $this->readHostKeys();
		$this->client = new \Net_SFTP($this->host);

		// The SSH Host Key MUST be verified before login().
		$currentHostKey = $this->client->getServerPublicHostKey();
		if (array_key_exists($this->host, $hostKeys)) {
			if ($hostKeys[$this->host] != $currentHostKey) {
				throw new \Exception('Host public key does not match known key');
			}
		} else {
			$hostKeys[$this->host] = $currentHostKey;
			$this->writeHostKeys($hostKeys);
		}

		if (!$this->client->login($this->user, $this->password)) {
			throw new \Exception('Login failed');
		}
		return $this->client;
	}

	public function test() {
		if (
			!isset($this->host)
			|| !isset($this->user)
			|| !isset($this->password)
		) {
			return false;
		}
		return $this->getConnection()->nlist() !== false;
	}

	public function getId(){
		return 'sftp::' . $this->user . '@' . $this->host . '/' . $this->root;
	}

	/**
	 * @param string $path
	 */
	private function absPath($path) {
		return $this->root . $this->cleanPath($path);
	}

	private function hostKeysPath() {
		try {
			$storage_view = \OCP\Files::getStorage('files_external');
			if ($storage_view) {
				return \OC::$server->getConfig()->getSystemValue('datadirectory') .
					$storage_view->getAbsolutePath('') .
					'ssh_hostKeys';
			}
		} catch (\Exception $e) {
		}
		return false;
	}

	private function writeHostKeys($keys) {
		try {
			$keyPath = $this->hostKeysPath();
			if ($keyPath && file_exists($keyPath)) {
				$fp = fopen($keyPath, 'w');
				foreach ($keys as $host => $key) {
					fwrite($fp, $host . '::' . $key . "\n");
				}
				fclose($fp);
				return true;
			}
		} catch (\Exception $e) {
		}
		return false;
	}

	private function readHostKeys() {
		try {
			$keyPath = $this->hostKeysPath();
			if (file_exists($keyPath)) {
				$hosts = array();
				$keys = array();
				$lines = file($keyPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
				if ($lines) {
					foreach ($lines as $line) {
						$hostKeyArray = explode("::", $line, 2);
						if (count($hostKeyArray) == 2) {
							$hosts[] = $hostKeyArray[0];
							$keys[] = $hostKeyArray[1];
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
			return $this->getConnection()->mkdir($this->absPath($path));
		} catch (\Exception $e) {
			return false;
		}
	}

	public function rmdir($path) {
		try {
			return $this->getConnection()->delete($this->absPath($path), true);
		} catch (\Exception $e) {
			return false;
		}
	}

	public function opendir($path) {
		try {
			$list = $this->getConnection()->nlist($this->absPath($path));
			if ($list === false) {
				return false;
			}

			$id = md5('sftp:' . $path);
			$dirStream = array();
			foreach($list as $file) {
				if ($file != '.' && $file != '..') {
					$dirStream[] = $file;
				}
			}
			\OC\Files\Stream\Dir::register($id, $dirStream);
			return opendir('fakedir://' . $id);
		} catch(\Exception $e) {
			return false;
		}
	}

	public function filetype($path) {
		try {
			$stat = $this->getConnection()->stat($this->absPath($path));
			if ($stat['type'] == NET_SFTP_TYPE_REGULAR) {
				return 'file';
			}

			if ($stat['type'] == NET_SFTP_TYPE_DIRECTORY) {
				return 'dir';
			}
		} catch (\Exeption $e) {

		}
		return false;
	}

	public function file_exists($path) {
		try {
			return $this->getConnection()->stat($this->absPath($path)) !== false;
		} catch (\Exception $e) {
			return false;
		}
	}

	public function unlink($path) {
		try {
			return $this->getConnection()->delete($this->absPath($path), true);
		} catch (\Exception $e) {
			return false;
		}
	}

	public function fopen($path, $mode) {
		try {
			$absPath = $this->absPath($path);
			switch($mode) {
				case 'r':
				case 'rb':
					if ( !$this->file_exists($path)) {
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
					$context = stream_context_create(array('sftp' => array('session' => $this->getConnection())));
					return fopen($this->constructUrl($path), $mode, false, $context);
			}
		} catch (\Exception $e) {
		}
		return false;
	}

	public function touch($path, $mtime=null) {
		try {
			if (!is_null($mtime)) {
				return false;
			}
			if (!$this->file_exists($path)) {
				$this->getConnection()->put($this->absPath($path), '');
			} else {
				return false;
			}
		} catch (\Exception $e) {
			return false;
		}
		return true;
	}

	public function getFile($path, $target) {
		$this->getConnection()->get($path, $target);
	}

	public function uploadFile($path, $target) {
		$this->getConnection()->put($target, $path, NET_SFTP_LOCAL_FILE);
	}

	public function rename($source, $target) {
		try {
			if (!$this->is_dir($target) && $this->file_exists($target)) {
				$this->unlink($target);
			}
			return $this->getConnection()->rename(
				$this->absPath($source),
				$this->absPath($target)
			);
		} catch (\Exception $e) {
			return false;
		}
	}

	public function stat($path) {
		try {
			$stat = $this->getConnection()->stat($this->absPath($path));

			$mtime = $stat ? $stat['mtime'] : -1;
			$size = $stat ? $stat['size'] : 0;

			return array('mtime' => $mtime, 'size' => $size, 'ctime' => -1);
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * @param string $path
	 */
	public function constructUrl($path) {
		// Do not pass the password here. We want to use the Net_SFTP object
		// supplied via stream context or fail. We only supply username and
		// hostname because this might show up in logs (they are not used).
		$url = 'sftp://'.$this->user.'@'.$this->host.$this->root.$path;
		return $url;
	}
}
