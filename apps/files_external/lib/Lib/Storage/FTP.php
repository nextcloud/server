<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Storage;

use Icewind\Streams\CallbackWrapper;
use Icewind\Streams\CountWrapper;
use Icewind\Streams\IteratorDirectory;
use OC\Files\Storage\Common;
use OC\Files\Storage\PolyFill\CopyDirectory;
use OCP\Constants;
use OCP\Files\FileInfo;
use OCP\Files\StorageNotAvailableException;
use Psr\Log\LoggerInterface;

class FTP extends Common {
	use CopyDirectory;

	private $root;
	private $host;
	private $password;
	private $username;
	private $secure;
	private $port;
	private $utf8Mode;

	/** @var FtpConnection|null */
	private $connection;

	public function __construct(array $parameters) {
		if (isset($parameters['host']) && isset($parameters['user']) && isset($parameters['password'])) {
			$this->host = $parameters['host'];
			$this->username = $parameters['user'];
			$this->password = $parameters['password'];
			if (isset($parameters['secure'])) {
				if (is_string($parameters['secure'])) {
					$this->secure = ($parameters['secure'] === 'true');
				} else {
					$this->secure = (bool)$parameters['secure'];
				}
			} else {
				$this->secure = false;
			}
			$this->root = isset($parameters['root']) ? '/' . ltrim($parameters['root']) : '/';
			$this->port = $parameters['port'] ?? 21;
			$this->utf8Mode = isset($parameters['utf8']) && $parameters['utf8'];
		} else {
			throw new \Exception('Creating ' . self::class . ' storage failed, required parameters not set');
		}
	}

	public function __destruct() {
		$this->connection = null;
	}

	protected function getConnection(): FtpConnection {
		if (!$this->connection) {
			try {
				$this->connection = new FtpConnection(
					$this->secure,
					$this->host,
					$this->port,
					$this->username,
					$this->password
				);
			} catch (\Exception $e) {
				throw new StorageNotAvailableException('Failed to create ftp connection', 0, $e);
			}
			if ($this->utf8Mode) {
				if (!$this->connection->setUtf8Mode()) {
					throw new StorageNotAvailableException('Could not set UTF-8 mode');
				}
			}
		}

		return $this->connection;
	}

	public function getId(): string {
		return 'ftp::' . $this->username . '@' . $this->host . '/' . $this->root;
	}

	protected function buildPath(string $path): string {
		return rtrim($this->root . '/' . $path, '/');
	}

	public static function checkDependencies(): array|bool {
		if (function_exists('ftp_login')) {
			return true;
		} else {
			return ['ftp'];
		}
	}

	public function filemtime(string $path): int|false {
		$result = $this->getConnection()->mdtm($this->buildPath($path));

		if ($result === -1) {
			if ($this->is_dir($path)) {
				$list = $this->getConnection()->mlsd($this->buildPath($path));
				if (!$list) {
					\OC::$server->get(LoggerInterface::class)->warning("Unable to get last modified date for ftp folder ($path), failed to list folder contents");
					return time();
				}
				$currentDir = current(array_filter($list, function ($item) {
					return $item['type'] === 'cdir';
				}));
				if ($currentDir) {
					[$modify] = explode('.', $currentDir['modify'] ?? '', 2);
					$time = \DateTime::createFromFormat('YmdHis', $modify);
					if ($time === false) {
						throw new \Exception("Invalid date format for directory: $currentDir");
					}
					return $time->getTimestamp();
				} else {
					\OC::$server->get(LoggerInterface::class)->warning("Unable to get last modified date for ftp folder ($path), folder contents doesn't include current folder");
					return time();
				}
			} else {
				return false;
			}
		} else {
			return $result;
		}
	}

	public function filesize(string $path): false|int|float {
		$result = $this->getConnection()->size($this->buildPath($path));
		if ($result === -1) {
			return false;
		} else {
			return $result;
		}
	}

	public function rmdir(string $path): bool {
		if ($this->is_dir($path)) {
			$result = $this->getConnection()->rmdir($this->buildPath($path));
			// recursive rmdir support depends on the ftp server
			if ($result) {
				return $result;
			} else {
				return $this->recursiveRmDir($path);
			}
		} elseif ($this->is_file($path)) {
			return $this->unlink($path);
		} else {
			return false;
		}
	}

	private function recursiveRmDir(string $path): bool {
		$contents = $this->getDirectoryContent($path);
		$result = true;
		foreach ($contents as $content) {
			if ($content['mimetype'] === FileInfo::MIMETYPE_FOLDER) {
				$result = $result && $this->recursiveRmDir($path . '/' . $content['name']);
			} else {
				$result = $result && $this->getConnection()->delete($this->buildPath($path . '/' . $content['name']));
			}
		}
		$result = $result && $this->getConnection()->rmdir($this->buildPath($path));

		return $result;
	}

	public function test(): bool {
		try {
			return $this->getConnection()->systype() !== false;
		} catch (\Exception $e) {
			return false;
		}
	}

	public function stat(string $path): array|false {
		if (!$this->file_exists($path)) {
			return false;
		}
		return [
			'mtime' => $this->filemtime($path),
			'size' => $this->filesize($path),
		];
	}

	public function file_exists(string $path): bool {
		if ($path === '' || $path === '.' || $path === '/') {
			return true;
		}
		return $this->filetype($path) !== false;
	}

	public function unlink(string $path): bool {
		switch ($this->filetype($path)) {
			case 'dir':
				return $this->rmdir($path);
			case 'file':
				return $this->getConnection()->delete($this->buildPath($path));
			default:
				return false;
		}
	}

	public function opendir(string $path) {
		$files = $this->getConnection()->nlist($this->buildPath($path));
		return IteratorDirectory::wrap($files);
	}

	public function mkdir(string $path): bool {
		if ($this->is_dir($path)) {
			return false;
		}
		return $this->getConnection()->mkdir($this->buildPath($path)) !== false;
	}

	public function is_dir(string $path): bool {
		if ($path === '') {
			return true;
		}
		if ($this->getConnection()->chdir($this->buildPath($path)) === true) {
			$this->getConnection()->chdir('/');
			return true;
		} else {
			return false;
		}
	}

	public function is_file(string $path): bool {
		return $this->filesize($path) !== false;
	}

	public function filetype(string $path): string|false {
		if ($this->is_dir($path)) {
			return 'dir';
		} elseif ($this->is_file($path)) {
			return 'file';
		} else {
			return false;
		}
	}

	public function fopen(string $path, string $mode) {
		$useExisting = true;
		switch ($mode) {
			case 'r':
			case 'rb':
				return $this->readStream($path);
			case 'w':
			case 'w+':
			case 'wb':
			case 'wb+':
				$useExisting = false;
				// no break
			case 'a':
			case 'ab':
			case 'r+':
			case 'a+':
			case 'x':
			case 'x+':
			case 'c':
			case 'c+':
				//emulate these
				if ($useExisting and $this->file_exists($path)) {
					if (!$this->isUpdatable($path)) {
						return false;
					}
					$tmpFile = $this->getCachedFile($path);
				} else {
					if (!$this->isCreatable(dirname($path))) {
						return false;
					}
					$tmpFile = \OC::$server->getTempManager()->getTemporaryFile();
				}
				$source = fopen($tmpFile, $mode);
				return CallbackWrapper::wrap($source, null, null, function () use ($tmpFile, $path): void {
					$this->writeStream($path, fopen($tmpFile, 'r'));
					unlink($tmpFile);
				});
		}
		return false;
	}

	public function writeStream(string $path, $stream, ?int $size = null): int {
		if ($size === null) {
			$stream = CountWrapper::wrap($stream, function ($writtenSize) use (&$size): void {
				$size = $writtenSize;
			});
		}

		$this->getConnection()->fput($this->buildPath($path), $stream);
		fclose($stream);

		return $size;
	}

	public function readStream(string $path) {
		$stream = fopen('php://temp', 'w+');
		$result = $this->getConnection()->fget($stream, $this->buildPath($path));
		rewind($stream);

		if (!$result) {
			fclose($stream);
			return false;
		}
		return $stream;
	}

	public function touch(string $path, ?int $mtime = null): bool {
		if ($this->file_exists($path)) {
			return false;
		} else {
			$this->file_put_contents($path, '');
			return true;
		}
	}

	public function rename(string $source, string $target): bool {
		$this->unlink($target);
		return $this->getConnection()->rename($this->buildPath($source), $this->buildPath($target));
	}

	public function getDirectoryContent(string $directory): \Traversable {
		$files = $this->getConnection()->mlsd($this->buildPath($directory));
		$mimeTypeDetector = \OC::$server->getMimeTypeDetector();

		foreach ($files as $file) {
			$name = $file['name'];
			if ($file['type'] === 'cdir' || $file['type'] === 'pdir') {
				continue;
			}
			$permissions = Constants::PERMISSION_ALL - Constants::PERMISSION_CREATE;
			$isDir = $file['type'] === 'dir';
			if ($isDir) {
				$permissions += Constants::PERMISSION_CREATE;
			}

			$data = [];
			$data['mimetype'] = $isDir ? FileInfo::MIMETYPE_FOLDER : $mimeTypeDetector->detectPath($name);

			// strip fractional seconds
			[$modify] = explode('.', $file['modify'], 2);
			$mtime = \DateTime::createFromFormat('YmdGis', $modify);
			$data['mtime'] = $mtime === false ? time() : $mtime->getTimestamp();
			if ($isDir) {
				$data['size'] = -1; //unknown
			} elseif (isset($file['size'])) {
				$data['size'] = $file['size'];
			} else {
				$data['size'] = $this->filesize($directory . '/' . $name);
			}
			$data['etag'] = uniqid();
			$data['storage_mtime'] = $data['mtime'];
			$data['permissions'] = $permissions;
			$data['name'] = $name;

			yield $data;
		}
	}
}
