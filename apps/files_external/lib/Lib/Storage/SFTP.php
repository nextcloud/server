<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Storage;

use Icewind\Streams\CountWrapper;
use Icewind\Streams\IteratorDirectory;
use Icewind\Streams\RetryWrapper;
use OC\Files\Storage\Common;
use OC\Files\View;
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

	public const COPY_CHUNK_SIZE = 8 * 1024 * 1024;

	/**
	 * @param string $host protocol://server:port
	 * @return array [$server, $port]
	 */
	private function splitHost(string $host): array {
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

	public function __construct(array $parameters) {
		// Register sftp://
		Stream::register();

		$parsedHost = $this->splitHost($parameters['host']);

		$this->host = $parsedHost[0];
		$this->port = $parsedHost[1];

		if (!isset($parameters['user'])) {
			throw new \UnexpectedValueException('no authentication parameters specified');
		}
		$this->user = $parameters['user'];

		if (isset($parameters['public_key_auth'])) {
			$this->auth[] = $parameters['public_key_auth'];
		}
		if (isset($parameters['password']) && $parameters['password'] !== '') {
			$this->auth[] = $parameters['password'];
		}

		if ($this->auth === []) {
			throw new \UnexpectedValueException('no authentication parameters specified');
		}

		$this->root
			= isset($parameters['root']) ? $this->cleanPath($parameters['root']) : '/';

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
	public function getConnection(): \phpseclib\Net\SFTP {
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

	public function test(): bool {
		if (
			!isset($this->host)
			|| !isset($this->user)
		) {
			return false;
		}
		return $this->getConnection()->nlist() !== false;
	}

	public function getId(): string {
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

	public function getHost(): string {
		return $this->host;
	}

	public function getRoot(): string {
		return $this->root;
	}

	public function getUser(): string {
		return $this->user;
	}

	private function absPath(string $path): string {
		return $this->root . $this->cleanPath($path);
	}

	private function hostKeysPath(): string|false {
		try {
			$userId = \OC_User::getUser();
			if ($userId === false) {
				return false;
			}

			$view = new View('/' . $userId . '/files_external');

			return $view->getLocalFile('ssh_hostKeys');
		} catch (\Exception $e) {
		}
		return false;
	}

	protected function writeHostKeys(array $keys): bool {
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

	protected function readHostKeys(): array {
		try {
			$keyPath = $this->hostKeysPath();
			if (file_exists($keyPath)) {
				$hosts = [];
				$keys = [];
				$lines = file($keyPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
				if ($lines) {
					foreach ($lines as $line) {
						$hostKeyArray = explode('::', $line, 2);
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

	public function mkdir(string $path): bool {
		try {
			return $this->getConnection()->mkdir($this->absPath($path));
		} catch (\Exception $e) {
			return false;
		}
	}

	public function rmdir(string $path): bool {
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

	public function opendir(string $path) {
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

	public function filetype(string $path): string|false {
		try {
			$stat = $this->getConnection()->stat($this->absPath($path));
			if (!is_array($stat) || !array_key_exists('type', $stat)) {
				return false;
			}
			if ((int)$stat['type'] === NET_SFTP_TYPE_REGULAR) {
				return 'file';
			}

			if ((int)$stat['type'] === NET_SFTP_TYPE_DIRECTORY) {
				return 'dir';
			}
		} catch (\Exception $e) {
		}
		return false;
	}

	public function file_exists(string $path): bool {
		try {
			return $this->getConnection()->stat($this->absPath($path)) !== false;
		} catch (\Exception $e) {
			return false;
		}
	}

	public function unlink(string $path): bool {
		try {
			return $this->getConnection()->delete($this->absPath($path), true);
		} catch (\Exception $e) {
			return false;
		}
	}

	public function fopen(string $path, string $mode) {
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

	public function touch(string $path, ?int $mtime = null): bool {
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
	 * @throws \Exception
	 */
	public function getFile(string $path, string $target): void {
		$this->getConnection()->get($path, $target);
	}

	public function rename(string $source, string $target): bool {
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
	public function stat(string $path): array|false {
		try {
			$stat = $this->getConnection()->stat($this->absPath($path));

			$mtime = isset($stat['mtime']) ? (int)$stat['mtime'] : -1;
			$size = isset($stat['size']) ? (int)$stat['size'] : 0;

			return [
				'mtime' => $mtime,
				'size' => $size,
				'ctime' => -1
			];
		} catch (\Exception $e) {
			return false;
		}
	}

	public function constructUrl(string $path): string {
		// Do not pass the password here. We want to use the Net_SFTP object
		// supplied via stream context or fail. We only supply username and
		// hostname because this might show up in logs (they are not used).
		$url = 'sftp://' . urlencode($this->user) . '@' . $this->host . ':' . $this->port . $this->root . $path;
		return $url;
	}

	public function file_put_contents(string $path, mixed $data): int|float|false {
		/** @psalm-suppress InternalMethod */
		$result = $this->getConnection()->put($this->absPath($path), $data);
		if ($result) {
			return strlen($data);
		} else {
			return false;
		}
	}

	public function writeStream(string $path, $stream, ?int $size = null): int {
		if ($size === null) {
			$stream = CountWrapper::wrap($stream, function (int $writtenSize) use (&$size): void {
				$size = $writtenSize;
			});
			if (!$stream) {
				throw new \Exception('Failed to wrap stream');
			}
		}
		/** @psalm-suppress InternalMethod */
		$result = $this->getConnection()->put($this->absPath($path), $stream);
		fclose($stream);
		if ($result) {
			if ($size === null) {
				throw new \Exception('Failed to get written size from sftp storage wrapper');
			}
			return $size;
		} else {
			throw new \Exception('Failed to write steam to sftp storage');
		}
	}

	public function copy(string $source, string $target): bool {
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

	public function getPermissions(string $path): int {
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

	public function getMetaData(string $path): ?array {
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
