<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author hkjolhede <hkjolhede@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lennart Rosam <lennart.rosam@medien-systempartner.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Ross Nicoll <jrn@jrn.me.uk>
 * @author SA <stephen@mthosting.net>
 * @author Senorsen <senorsen.zhang@gmail.com>
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

use Icewind\Streams\CountWrapper;
use Icewind\Streams\IteratorDirectory;
use Icewind\Streams\RetryWrapper;
use OC\Files\Filesystem;
use OC\Files\Storage\Common;
use OCP\Constants;
use OCP\Files\FileInfo;
use OCP\Files\IMimeTypeDetector;
use phpseclib\Net\SFTP\Stream;

/**
 * Uses phpseclib's Net\SFTP class and the Net\SFTP\Stream stream wrapper to
 * provide access to SFTP servers.
 */
class SFTP extends Common {
	private $host;
	private $user;
	private $root;
	private $port = 22;

	private $auth = [];

	/**
	 * @var \phpseclib\Net\SFTP
	 */
	protected $client;
	private IMimeTypeDetector $mimeTypeDetector;

	const COPY_CHUNK_SIZE = 8 * 1024 * 1024;

	/**
	 * @param string $host protocol://server:port
	 * @return array [$server, $port]
	 */
	private function splitHost($host) {
		$input = $host;
		if (!str_contains($host, '://')) {
			// add a protocol to fix parse_url behavior with ipv6
			$host = 'http://' . $host;
		}

		$parsed = parse_url($host);
		if (is_array($parsed) && isset($parsed['port'])) {
			return [$parsed['host'], $parsed['port']];
		} elseif (is_array($parsed)) {
			return [$parsed['host'], 22];
		} else {
			return [$input, 22];
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function __construct($params) {
		// Register sftp://
		Stream::register();

		$parsedHost = $this->splitHost($params['host']);

		$this->host = $parsedHost[0];
		$this->port = $parsedHost[1];

		if (!isset($params['user'])) {
			throw new \UnexpectedValueException('no authentication parameters specified');
		}
		$this->user = $params['user'];

		if (isset($params['public_key_auth'])) {
			$this->auth[] = $params['public_key_auth'];
		}
		if (isset($params['password']) && $params['password'] !== '') {
			$this->auth[] = $params['password'];
		}

		if ($this->auth === []) {
			throw new \UnexpectedValueException('no authentication parameters specified');
		}

		$this->root
			= isset($params['root']) ? $this->cleanPath($params['root']) : '/';

		$this->root = '/' . ltrim($this->root, '/');
		$this->root = rtrim($this->root, '/') . '/';
		$this->mimeTypeDetector = \OC::$server->get(IMimeTypeDetector::class);
	}

	/**
	 * Returns the connection.
	 *
	 * @return \phpseclib\Net\SFTP connected client instance
	 * @throws \Exception when the connection failed
	 */
	public function getConnection() {
		if (!is_null($this->client)) {
			return $this->client;
		}

		$hostKeys = $this->readHostKeys();
		$this->client = new \phpseclib\Net\SFTP($this->host, $this->port);

		// The SSH Host Key MUST be verified before login().
		$currentHostKey = $this->client->getServerPublicHostKey();
		if (array_key_exists($this->host, $hostKeys)) {
			if ($hostKeys[$this->host] !== $currentHostKey) {
				throw new \Exception('Host public key does not match known key');
			}
		} else {
			$hostKeys[$this->host] = $currentHostKey;
			$this->writeHostKeys($hostKeys);
		}

		$login = false;
		foreach ($this->auth as $auth) {
			/** @psalm-suppress TooManyArguments */
			$login = $this->client->login($this->user, $auth);
			if ($login === true) {
				break;
			}
		}

		if ($login === false) {
			throw new \Exception('Login failed');
		}
		return $this->client;
	}

	/**
	 * {@inheritdoc}
	 */
	public function test() {
		if (
			!isset($this->host)
			|| !isset($this->user)
		) {
			return false;
		}
		return $this->getConnection()->nlist() !== false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getId() {
		$id = 'sftp::' . $this->user . '@' . $this->host;
		if ($this->port !== 22) {
			$id .= ':' . $this->port;
		}
		// note: this will double the root slash,
		// we should not change it to keep compatible with
		// old storage ids
		$id .= '/' . $this->root;
		return $id;
	}

	/**
	 * @return string
	 */
	public function getHost() {
		return $this->host;
	}

	/**
	 * @return string
	 */
	public function getRoot() {
		return $this->root;
	}

	/**
	 * @return mixed
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * @param string $path
	 * @return string
	 */
	private function absPath($path) {
		return $this->root . $this->cleanPath($path);
	}

	/**
	 * @return string|false
	 */
	private function hostKeysPath() {
		try {
			$storage_view = \OCP\Files::getStorage('files_external');
			if ($storage_view) {
				return \OC::$server->getConfig()->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data') .
					$storage_view->getAbsolutePath('') .
					'ssh_hostKeys';
			}
		} catch (\Exception $e) {
		}
		return false;
	}

	/**
	 * @param $keys
	 * @return bool
	 */
	protected function writeHostKeys($keys) {
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

	/**
	 * @return array
	 */
	protected function readHostKeys() {
		try {
			$keyPath = $this->hostKeysPath();
			if (file_exists($keyPath)) {
				$hosts = [];
				$keys = [];
				$lines = file($keyPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
				if ($lines) {
					foreach ($lines as $line) {
						$hostKeyArray = explode("::", $line, 2);
						if (count($hostKeyArray) === 2) {
							$hosts[] = $hostKeyArray[0];
							$keys[] = $hostKeyArray[1];
						}
					}
					return array_combine($hosts, $keys);
				}
			}
		} catch (\Exception $e) {
		}
		return [];
	}

	/**
	 * {@inheritdoc}
	 */
	public function mkdir($path) {
		try {
			return $this->getConnection()->mkdir($this->absPath($path));
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function rmdir($path) {
		try {
			$result = $this->getConnection()->delete($this->absPath($path), true);
			// workaround: stray stat cache entry when deleting empty folders
			// see https://github.com/phpseclib/phpseclib/issues/706
			$this->getConnection()->clearStatCache();
			return $result;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function opendir($path) {
		try {
			$list = $this->getConnection()->nlist($this->absPath($path));
			if ($list === false) {
				return false;
			}

			$id = md5('sftp:' . $path);
			$dirStream = [];
			foreach ($list as $file) {
				if ($file !== '.' && $file !== '..') {
					$dirStream[] = $file;
				}
			}
			return IteratorDirectory::wrap($dirStream);
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function filetype($path) {
		try {
			$stat = $this->getConnection()->stat($this->absPath($path));
			if (!is_array($stat) || !array_key_exists('type', $stat)) {
				return false;
			}
			if ((int) $stat['type'] === NET_SFTP_TYPE_REGULAR) {
				return 'file';
			}

			if ((int) $stat['type'] === NET_SFTP_TYPE_DIRECTORY) {
				return 'dir';
			}
		} catch (\Exception $e) {
		}
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function file_exists($path) {
		try {
			return $this->getConnection()->stat($this->absPath($path)) !== false;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function unlink($path) {
		try {
			return $this->getConnection()->delete($this->absPath($path), true);
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function fopen($path, $mode) {
		try {
			$absPath = $this->absPath($path);
			$connection = $this->getConnection();
			switch ($mode) {
				case 'r':
				case 'rb':
					$stat = $this->stat($path);
					if (!$stat) {
						return false;
					}
					SFTPReadStream::register();
					$context = stream_context_create(['sftp' => ['session' => $connection, 'size' => $stat['size']]]);
					$handle = fopen('sftpread://' . trim($absPath, '/'), 'r', false, $context);
					return RetryWrapper::wrap($handle);
				case 'w':
				case 'wb':
					SFTPWriteStream::register();
					// the SFTPWriteStream doesn't go through the "normal" methods so it doesn't clear the stat cache.
					$connection->_remove_from_stat_cache($absPath);
					$context = stream_context_create(['sftp' => ['session' => $connection]]);
					return fopen('sftpwrite://' . trim($absPath, '/'), 'w', false, $context);
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
					$context = stream_context_create(['sftp' => ['session' => $connection]]);
					$handle = fopen($this->constructUrl($path), $mode, false, $context);
					return RetryWrapper::wrap($handle);
			}
		} catch (\Exception $e) {
		}
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function touch($path, $mtime = null) {
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

	/**
	 * @param string $path
	 * @param string $target
	 * @throws \Exception
	 */
	public function getFile($path, $target) {
		$this->getConnection()->get($path, $target);
	}

	/**
	 * {@inheritdoc}
	 */
	public function rename($source, $target) {
		try {
			if ($this->file_exists($target)) {
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

	/**
	 * @return array{mtime: int, size: int, ctime: int}|false
	 */
	public function stat($path) {
		try {
			$stat = $this->getConnection()->stat($this->absPath($path));

			$mtime = $stat ? (int)$stat['mtime'] : -1;
			$size = $stat ? (int)$stat['size'] : 0;

			return ['mtime' => $mtime, 'size' => $size, 'ctime' => -1];
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public function constructUrl($path) {
		// Do not pass the password here. We want to use the Net_SFTP object
		// supplied via stream context or fail. We only supply username and
		// hostname because this might show up in logs (they are not used).
		$url = 'sftp://' . urlencode($this->user) . '@' . $this->host . ':' . $this->port . $this->root . $path;
		return $url;
	}

	public function file_put_contents($path, $data) {
		/** @psalm-suppress InternalMethod */
		$result = $this->getConnection()->put($this->absPath($path), $data);
		if ($result) {
			return strlen($data);
		} else {
			return false;
		}
	}

	public function writeStream(string $path, $stream, int $size = null): int {
		if ($size === null) {
			$stream = CountWrapper::wrap($stream, function (int $writtenSize) use (&$size) {
				$size = $writtenSize;
			});
			if (!$stream) {
				throw new \Exception("Failed to wrap stream");
			}
		}
		/** @psalm-suppress InternalMethod */
		$result = $this->getConnection()->put($this->absPath($path), $stream);
		fclose($stream);
		if ($result) {
			return $size;
		} else {
			throw new \Exception("Failed to write steam to sftp storage");
		}
	}

	public function copy($source, $target) {
		if ($this->is_dir($source) || $this->is_dir($target)) {
			return parent::copy($source, $target);
		} else {
			$absSource = $this->absPath($source);
			$absTarget = $this->absPath($target);

			$connection = $this->getConnection();
			$size = $connection->size($absSource);
			if ($size === false) {
				return false;
			}
			for ($i = 0; $i < $size; $i += self::COPY_CHUNK_SIZE) {
				/** @psalm-suppress InvalidArgument */
				$chunk = $connection->get($absSource, false, $i, self::COPY_CHUNK_SIZE);
				if ($chunk === false) {
					return false;
				}
				/** @psalm-suppress InternalMethod */
				if (!$connection->put($absTarget, $chunk, \phpseclib\Net\SFTP::SOURCE_STRING, $i)) {
					return false;
				}
			}
			return true;
		}
	}

	public function getPermissions($path) {
		$stat = $this->getConnection()->stat($this->absPath($path));
		if (!$stat) {
			return 0;
		}
		if ($stat['type'] === NET_SFTP_TYPE_DIRECTORY) {
			return Constants::PERMISSION_ALL;
		} else {
			return Constants::PERMISSION_ALL - Constants::PERMISSION_CREATE;
		}
	}

	public function getMetaData($path) {
		$stat = $this->getConnection()->stat($this->absPath($path));
		if (!$stat) {
			return null;
		}

		if ($stat['type'] === NET_SFTP_TYPE_DIRECTORY) {
			$stat['permissions'] = Constants::PERMISSION_ALL;
		} else {
			$stat['permissions'] = Constants::PERMISSION_ALL - Constants::PERMISSION_CREATE;
		}

		if ($stat['type'] === NET_SFTP_TYPE_DIRECTORY) {
			$stat['size'] = -1;
			$stat['mimetype'] = FileInfo::MIMETYPE_FOLDER;
		} else {
			$stat['mimetype'] = $this->mimeTypeDetector->detectPath($path);
		}

		$stat['etag'] = $this->getETag($path);
		$stat['storage_mtime'] = $stat['mtime'];
		$stat['name'] = basename($path);

		$keys = ['size', 'mtime', 'mimetype', 'etag', 'storage_mtime', 'permissions', 'name'];
		return array_intersect_key($stat, array_flip($keys));
	}
}
