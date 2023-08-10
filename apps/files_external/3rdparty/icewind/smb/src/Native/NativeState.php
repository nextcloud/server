<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB\Native;

use Icewind\SMB\Exception\AlreadyExistsException;
use Icewind\SMB\Exception\ConnectionException;
use Icewind\SMB\Exception\ConnectionRefusedException;
use Icewind\SMB\Exception\ConnectionResetException;
use Icewind\SMB\Exception\Exception;
use Icewind\SMB\Exception\FileInUseException;
use Icewind\SMB\Exception\ForbiddenException;
use Icewind\SMB\Exception\HostDownException;
use Icewind\SMB\Exception\InvalidArgumentException;
use Icewind\SMB\Exception\InvalidTypeException;
use Icewind\SMB\Exception\ConnectionAbortedException;
use Icewind\SMB\Exception\NoRouteToHostException;
use Icewind\SMB\Exception\NotEmptyException;
use Icewind\SMB\Exception\NotFoundException;
use Icewind\SMB\Exception\OutOfSpaceException;
use Icewind\SMB\Exception\TimedOutException;
use Icewind\SMB\IAuth;
use Icewind\SMB\IOptions;

/**
 * Low level wrapper for libsmbclient-php with error handling
 */
class NativeState {
	/** @var resource|null */
	protected $state = null;

	/** @var bool */
	protected $connected = false;

	/**
	 * sync the garbage collection cycle
	 * __deconstruct() of KerberosAuth should not called too soon
	 *
	 * @var IAuth|null $auth
	 */
	protected $auth = null;

	// see error.h
	const EXCEPTION_MAP = [
		1   => ForbiddenException::class,
		2   => NotFoundException::class,
		13  => ForbiddenException::class,
		16  => FileInUseException::class,
		17  => AlreadyExistsException::class,
		20  => InvalidTypeException::class,
		21  => InvalidTypeException::class,
		22  => InvalidArgumentException::class,
		28  => OutOfSpaceException::class,
		39  => NotEmptyException::class,
		103 => ConnectionAbortedException::class,
		104 => ConnectionResetException::class,
		110 => TimedOutException::class,
		111 => ConnectionRefusedException::class,
		112 => HostDownException::class,
		113 => NoRouteToHostException::class
	];

	protected function handleError(?string $path): void {
		if (!$this->state) {
			return;
		}
		$error = smbclient_state_errno($this->state);
		if ($error === 0) {
			return;
		}
		throw Exception::fromMap(self::EXCEPTION_MAP, $error, $path);
	}

	/**
	 * @param mixed $result
	 * @param string|null $uri
	 * @throws Exception
	 */
	protected function testResult($result, ?string $uri): void {
		if ($result === false or $result === null) {
			// smb://host/share/path
			if (is_string($uri) && count(explode('/', $uri, 5)) > 4) {
				list(, , , , $path) = explode('/', $uri, 5);
				$path = '/' . $path;
			} else {
				$path = $uri;
			}
			$this->handleError($path);
		}
	}

	/**
	 * @param IAuth $auth
	 * @param IOptions $options
	 * @return bool
	 */
	public function init(IAuth $auth, IOptions $options) {
		if ($this->connected) {
			return true;
		}
		/** @var resource $state */
		$state = smbclient_state_new();
		$this->state = $state;
		/** @psalm-suppress UnusedFunctionCall */
		smbclient_option_set($this->state, SMBCLIENT_OPT_AUTO_ANONYMOUS_LOGIN, false);
		/** @psalm-suppress UnusedFunctionCall */
		smbclient_option_set($this->state, SMBCLIENT_OPT_TIMEOUT, $options->getTimeout() * 1000);

		if (function_exists('smbclient_client_protocols')) {
			smbclient_client_protocols($this->state, $options->getMinProtocol(), $options->getMaxProtocol());
		}

		$auth->setExtraSmbClientOptions($this->state);

		// sync the garbage collection cycle
		// __deconstruct() of KerberosAuth should not caled too soon
		$this->auth = $auth;

		$result = @smbclient_state_init($this->state, $auth->getWorkgroup(), $auth->getUsername(), $auth->getPassword());

		$this->testResult($result, '');
		$this->connected = true;
		return $result;
	}

	/**
	 * @param string $uri
	 * @return resource
	 */
	public function opendir(string $uri) {
		if (!$this->state) {
			throw new ConnectionException("Not connected");
		}
		/** @var resource $result */
		$result = @smbclient_opendir($this->state, $uri);

		$this->testResult($result, $uri);
		return $result;
	}

	/**
	 * @param resource $dir
	 * @param string $path
	 * @return array{"type": string, "comment": string, "name": string}|false
	 */
	public function readdir($dir, string $path) {
		if (!$this->state) {
			throw new ConnectionException("Not connected");
		}
		/** @var array{"type": string, "comment": string, "name": string}|false $result */
		$result = @smbclient_readdir($this->state, $dir);

		$this->testResult($result, $path);
		return $result;
	}

	/**
	 * @param resource $dir
	 * @param string $path
	 * @return bool
	 */
	public function closedir($dir, string $path): bool {
		if (!$this->state) {
			throw new ConnectionException("Not connected");
		}
		$result = @smbclient_closedir($this->state, $dir);

		$this->testResult($result, $path);
		return $result;
	}

	/**
	 * @param string $old
	 * @param string $new
	 * @return bool
	 */
	public function rename(string $old, string $new): bool {
		if (!$this->state) {
			throw new ConnectionException("Not connected");
		}
		$result = @smbclient_rename($this->state, $old, $this->state, $new);

		$this->testResult($result, $new);
		return $result;
	}

	/**
	 * @param string $uri
	 * @return bool
	 */
	public function unlink(string $uri): bool {
		if (!$this->state) {
			throw new ConnectionException("Not connected");
		}
		$result = @smbclient_unlink($this->state, $uri);

		$this->testResult($result, $uri);
		return $result;
	}

	/**
	 * @param string $uri
	 * @param int $mask
	 * @return bool
	 */
	public function mkdir(string $uri, int $mask = 0777): bool {
		if (!$this->state) {
			throw new ConnectionException("Not connected");
		}
		$result = @smbclient_mkdir($this->state, $uri, $mask);

		$this->testResult($result, $uri);
		return $result;
	}

	/**
	 * @param string $uri
	 * @return bool
	 */
	public function rmdir(string $uri): bool {
		if (!$this->state) {
			throw new ConnectionException("Not connected");
		}
		$result = @smbclient_rmdir($this->state, $uri);

		$this->testResult($result, $uri);
		return $result;
	}

	/**
	 * @param string $uri
	 * @return array{"mtime": int, "size": int, "mode": int}
	 */
	public function stat(string $uri): array {
		if (!$this->state) {
			throw new ConnectionException("Not connected");
		}
		/** @var array{"mtime": int, "size": int, "mode": int} $result */
		$result = @smbclient_stat($this->state, $uri);

		$this->testResult($result, $uri);
		return $result;
	}

	/**
	 * @param resource $file
	 * @param string $path
	 * @return array{"mtime": int, "size": int, "mode": int}
	 */
	public function fstat($file, string $path): array {
		if (!$this->state) {
			throw new ConnectionException("Not connected");
		}
		/** @var array{"mtime": int, "size": int, "mode": int} $result */
		$result = @smbclient_fstat($this->state, $file);

		$this->testResult($result, $path);
		return $result;
	}

	/**
	 * @param string $uri
	 * @param string $mode
	 * @param int $mask
	 * @return resource
	 */
	public function open(string $uri, string $mode, int $mask = 0666) {
		if (!$this->state) {
			throw new ConnectionException("Not connected");
		}
		/** @var resource $result */
		$result = @smbclient_open($this->state, $uri, $mode, $mask);

		$this->testResult($result, $uri);
		return $result;
	}

	/**
	 * @param string $uri
	 * @param int $mask
	 * @return resource
	 */
	public function create(string $uri, int $mask = 0666) {
		if (!$this->state) {
			throw new ConnectionException("Not connected");
		}
		/** @var resource $result */
		$result = @smbclient_creat($this->state, $uri, $mask);

		$this->testResult($result, $uri);
		return $result;
	}

	/**
	 * @param resource $file
	 * @param int $bytes
	 * @param string $path
	 * @return string
	 */
	public function read($file, int $bytes, string $path): string {
		if (!$this->state) {
			throw new ConnectionException("Not connected");
		}
		/** @var string $result */
		$result = @smbclient_read($this->state, $file, $bytes);

		$this->testResult($result, $path);
		return $result;
	}

	/**
	 * @param resource $file
	 * @param string $data
	 * @param string $path
	 * @param int|null $length
	 * @return int
	 */
	public function write($file, string $data, string $path, ?int $length = null): int {
		if (!$this->state) {
			throw new ConnectionException("Not connected");
		}
		if ($length) {
			$result = @smbclient_write($this->state, $file, $data, $length);
		} else {
			$result = @smbclient_write($this->state, $file, $data);
		}

		$this->testResult($result, $path);
		if ($result === false) {
			return 0;
		}
		return $result;
	}

	/**
	 * @param resource $file
	 * @param int $offset
	 * @param int $whence SEEK_SET | SEEK_CUR | SEEK_END
	 * @param string|null $path
	 *
	 * @return false|int new file offset as measured from the start of the file on success.
	 */
	public function lseek($file, int $offset, int $whence = SEEK_SET, string $path = null) {
		if (!$this->state) {
			throw new ConnectionException("Not connected");
		}
		// psalm doesn't think int|false == int|false for some reason, so we do a needless annotation to help it out
		/**
		 * @psalm-suppress UnnecessaryVarAnnotation
		 * @var int|false $result
		 */
		$result = @smbclient_lseek($this->state, $file, $offset, $whence);

		$this->testResult($result, $path);
		return $result;
	}

	/**
	 * @param resource $file
	 * @param int $size
	 * @param string $path
	 * @return bool
	 */
	public function ftruncate($file, int $size, string $path): bool {
		if (!$this->state) {
			throw new ConnectionException("Not connected");
		}
		$result = @smbclient_ftruncate($this->state, $file, $size);

		$this->testResult($result, $path);
		return $result;
	}

	/**
	 * @param resource $file
	 * @param string $path
	 * @return bool
	 */
	public function close($file, string $path): bool {
		if (!$this->state) {
			return false;
		}
		$result = @smbclient_close($this->state, $file);

		$this->testResult($result, $path);
		return $result;
	}

	/**
	 * @param string $uri
	 * @param string $key
	 * @return string
	 */
	public function getxattr(string $uri, string $key) {
		if (!$this->state) {
			throw new ConnectionException("Not connected");
		}
		/** @var string $result */
		$result = @smbclient_getxattr($this->state, $uri, $key);

		$this->testResult($result, $uri);
		return $result;
	}

	/**
	 * @param string $uri
	 * @param string $key
	 * @param string $value
	 * @param int $flags
	 * @return bool
	 */
	public function setxattr(string $uri, string $key, string $value, int $flags = 0) {
		if (!$this->state) {
			throw new ConnectionException("Not connected");
		}
		/** @var bool $result */
		$result = @smbclient_setxattr($this->state, $uri, $key, $value, $flags);

		$this->testResult($result, $uri);
		return $result;
	}

	public function __destruct() {
		if ($this->connected && $this->state) {
			if (smbclient_state_free($this->state) === false) {
				throw new Exception("Failed to free smb state");
			}
		}
	}
}
