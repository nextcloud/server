<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview;

/**
 * Runs ffmpeg or ffprobe on an ffmpeg-over-ip server
 *
 * The server tunnels all file access of the remote process back over the
 * connection. Only the files passed to run() can be opened, read-only and
 * under their virtual path.
 *
 * @see https://github.com/steelbrain/ffmpeg-over-ip
 */
class FFmpegOverIpClient {
	public const PROGRAM_FFMPEG = 0x01;
	public const PROGRAM_FFPROBE = 0x02;

	/** Code of the exception thrown if the server cannot be reached */
	public const ERROR_CONNECT = 1;

	private const PROTOCOL_VERSION = 0x06;

	private const MSG_COMMAND = 0x01;
	private const MSG_CANCEL = 0x02;
	private const MSG_EXIT_CODE = 0x03;
	private const MSG_ERROR = 0x04;
	private const MSG_PING = 0x05;
	private const MSG_PONG = 0x06;
	private const MSG_STDIN_CLOSE = 0x11;
	private const MSG_STDOUT = 0x12;
	private const MSG_STDERR = 0x13;
	private const MSG_OPEN = 0x20;
	private const MSG_READ = 0x21;
	private const MSG_SEEK = 0x23;
	private const MSG_CLOSE = 0x24;
	private const MSG_FSTAT = 0x25;
	private const MSG_OPEN_OK = 0x40;
	private const MSG_READ_OK = 0x41;
	private const MSG_SEEK_OK = 0x43;
	private const MSG_CLOSE_OK = 0x44;
	private const MSG_FSTAT_OK = 0x45;
	private const MSG_IO_ERROR = 0x4F;

	private const FIO_O_ACCMODE = 0x0003;
	private const FIO_O_CREAT = 0x0040;
	private const FIO_O_TRUNC = 0x0200;
	private const FIO_SEEK_SET = 0;
	private const FIO_SEEK_CUR = 1;
	private const FIO_SEEK_END = 2;
	private const FIO_ENOENT = 2;
	private const FIO_EIO = 5;
	private const FIO_EINVAL = 22;
	private const FIO_EROFS = 30;
	/** S_IFREG | 0644 */
	private const FILE_MODE = 0o100644;

	private const MAX_MESSAGE_SIZE = 1024 * 1024;
	private const MAX_READ_SIZE = 16 * 1024 * 1024;
	private const MAX_STDOUT_SIZE = 64 * 1024 * 1024;
	private const MAX_STDERR_SIZE = 64 * 1024;
	private const WRITE_CHUNK_SIZE = 64 * 1024;

	/** @var array<string, array{stream: resource, size: int}> */
	private array $files = [];
	/** @var array<int, array{path: string, offset: int}> */
	private array $openFiles = [];
	/** @var array<string, int> position of each stream after the last successful read */
	private array $positions = [];
	private float $deadline;
	private int $bytesRead = 0;
	private bool $writing = false;

	/**
	 * @param string $address ``host:port``, ``tcp://host:port``, ``tls://host:port`` or ``unix:/path/to/socket``
	 * @param int $timeout seconds all runs of this client may take together
	 * @param ?string $caFile certificates to verify the server with for ``tls://`` addresses
	 * @param ?int $readLimit bytes the server may read in all runs of this client together
	 */
	public function __construct(
		private string $address,
		private string $secret,
		private int $timeout = 120,
		private ?string $caFile = null,
		private ?int $readLimit = null,
		private int $connectTimeout = 5,
	) {
		$this->deadline = microtime(true) + $timeout;
	}

	/**
	 * @param list<string> $args
	 * @param array<string, array{stream: resource, size: int}> $files seekable streams by the path they are opened with
	 * @return array{exitCode: int, stdout: string, stderr: string}
	 * @throws \RuntimeException if the server is unreachable (with code ERROR_CONNECT), rejects the command or violates the protocol
	 */
	public function run(int $program, array $args, array $files): array {
		$remoteSocket = $this->getRemoteSocket();
		$ssl = ['verify_peer' => true, 'verify_peer_name' => true];
		if ($this->caFile !== null) {
			$ssl['cafile'] = $this->caFile;
		}
		// PHP derives no peer name from IPv6 literals
		if (preg_match('/^(?:tls|ssl):\/\/\[([^\]]+)\]/i', $remoteSocket, $matches) === 1) {
			$ssl['peer_name'] = $matches[1];
		}

		// TLS errors are only reported as warnings
		$errors = [];
		set_error_handler(function (int $errno, string $message) use (&$errors): bool {
			$errors[] = $message;
			return true;
		});
		try {
			$socket = stream_socket_client($remoteSocket, $errno, $errstr, min($this->connectTimeout, $this->getRemainingTime()), STREAM_CLIENT_CONNECT, stream_context_create(['ssl' => $ssl]));
		} finally {
			restore_error_handler();
		}
		if ($socket === false) {
			$reason = implode('; ', array_filter([$errstr, ...$errors]));
			throw new \RuntimeException('Could not connect to ffmpeg-over-ip server ' . $this->address . ': ' . $reason, self::ERROR_CONNECT);
		}

		try {
			return $this->communicate($socket, $program, $args, $files);
		} finally {
			fclose($socket);
		}
	}

	/**
	 * Runs the command on an already established connection
	 *
	 * @param resource $socket
	 * @param list<string> $args
	 * @param array<string, array{stream: resource, size: int}> $files
	 * @return array{exitCode: int, stdout: string, stderr: string}
	 * @throws \RuntimeException
	 */
	public function communicate($socket, int $program, array $args, array $files): array {
		$this->files = $files;
		$this->openFiles = [];
		$this->positions = [];
		$this->writing = false;

		try {
			return $this->exchange($socket, $program, $args);
		} catch (\Throwable $e) {
			$this->cancel($socket);
			throw $e;
		}
	}

	/**
	 * @param resource $socket
	 * @param list<string> $args
	 * @return array{exitCode: int, stdout: string, stderr: string}
	 */
	private function exchange($socket, int $program, array $args): array {
		$stdout = '';
		$stderr = '';

		$this->writeMessage($socket, self::MSG_COMMAND, $this->encodeCommand($program, $args));
		$this->writeMessage($socket, self::MSG_STDIN_CLOSE, '');

		while (true) {
			[$type, $payload] = $this->readMessage($socket);
			switch ($type) {
				case self::MSG_EXIT_CODE:
					$exitCode = $this->unpack('N', $payload, 0, 4);
					return [
						// The exit code is sent as uint32, a negative code wraps around
						'exitCode' => $exitCode > 0x7FFFFFFF ? $exitCode - 0x100000000 : $exitCode,
						'stdout' => $stdout,
						'stderr' => $stderr,
					];
				case self::MSG_ERROR:
					throw new \RuntimeException('ffmpeg-over-ip server error: ' . $payload);
				case self::MSG_STDOUT:
					if (strlen($stdout) + strlen($payload) > self::MAX_STDOUT_SIZE) {
						throw new \RuntimeException('ffmpeg-over-ip output exceeds ' . self::MAX_STDOUT_SIZE . ' bytes');
					}
					$stdout .= $payload;
					break;
				case self::MSG_STDERR:
					$stderr .= substr($payload, 0, max(0, self::MAX_STDERR_SIZE - strlen($stderr)));
					break;
				case self::MSG_PING:
					$this->writeMessage($socket, self::MSG_PONG, $payload);
					break;
				case self::MSG_PONG:
					break;
				default:
					if ($type >= 0x20 && $type <= 0x29) {
						[$responseType, $response] = $this->handleFileRequest($type, $payload);
						$this->writeMessage($socket, $responseType, $response);
					}
			}
		}
	}

	/**
	 * Asks the server to stop the process, which it otherwise keeps running until its keepalive times out
	 *
	 * @param resource $socket
	 */
	private function cancel($socket): void {
		if ($this->writing || !is_resource($socket)) {
			return;
		}
		stream_set_blocking($socket, false);
		@fwrite($socket, pack('CN', self::MSG_CANCEL, 0));
	}

	private function getRemoteSocket(): string {
		$address = rtrim($this->address, '/');
		if (str_starts_with($address, 'unix:')) {
			return 'unix://' . preg_replace('/^unix:(\/\/)?/', '', $address);
		}
		if (preg_match('/^([a-z][a-z0-9+.-]*):\/\//i', $address, $matches) !== 1) {
			return 'tcp://' . $address;
		}
		if (!in_array(strtolower($matches[1]), ['tcp', 'tls', 'ssl'], true)) {
			throw new \RuntimeException('Invalid ffmpeg-over-ip address "' . $this->address . '", expected host:port, tls://host:port or unix:/path/to/socket');
		}
		return $address;
	}

	/**
	 * @throws \RuntimeException if the timeout is exceeded
	 */
	private function getRemainingTime(): float {
		$remaining = $this->deadline - microtime(true);
		if ($remaining <= 0) {
			throw new \RuntimeException('ffmpeg-over-ip server did not finish within ' . $this->timeout . ' seconds');
		}
		return $remaining;
	}

	private function encodeCommand(int $program, array $args): string {
		$encodedArgs = pack('n', count($args));
		foreach ($args as $arg) {
			if (strlen($arg) > 0xFFFF) {
				throw new \RuntimeException('ffmpeg-over-ip argument is too long');
			}
			$encodedArgs .= pack('n', strlen($arg)) . $arg;
		}

		$nonce = random_bytes(16);
		$signature = hash_hmac('sha256', chr(self::PROTOCOL_VERSION) . $nonce . chr($program) . $encodedArgs, $this->secret, true);
		return chr(self::PROTOCOL_VERSION) . $nonce . $signature . chr($program) . $encodedArgs;
	}

	/**
	 * @return array{0: int, 1: string} response type and payload
	 */
	private function handleFileRequest(int $type, string $payload): array {
		$requestId = $this->unpack('n', $payload, 0, 2);
		switch ($type) {
			case self::MSG_OPEN:
				$fileId = $this->unpack('n', $payload, 2, 10);
				$flags = $this->unpack('N', $payload, 4, 10);
				$path = substr($payload, 10);
				if (!isset($this->files[$path])) {
					return $this->ioError($requestId, self::FIO_ENOENT);
				}
				if (($flags & (self::FIO_O_ACCMODE | self::FIO_O_CREAT | self::FIO_O_TRUNC)) !== 0) {
					return $this->ioError($requestId, self::FIO_EROFS);
				}
				if (isset($this->openFiles[$fileId])) {
					return $this->ioError($requestId, self::FIO_EINVAL);
				}
				$this->openFiles[$fileId] = ['path' => $path, 'offset' => 0];
				return [self::MSG_OPEN_OK, pack('nJ', $requestId, $this->files[$path]['size'])];
			case self::MSG_READ:
				$fileId = $this->unpack('n', $payload, 2, 8);
				$length = min($this->unpack('N', $payload, 4, 8), self::MAX_READ_SIZE);
				if (!isset($this->openFiles[$fileId])) {
					return $this->ioError($requestId, self::FIO_EINVAL);
				}
				$file = $this->openFiles[$fileId];
				// A short read is treated as end of file, so anything but the full length is an error
				$expected = max(0, min($length, $this->files[$file['path']]['size'] - $file['offset']));
				if ($this->readLimit !== null && $this->bytesRead + $expected > $this->readLimit) {
					return $this->ioError($requestId, self::FIO_EIO);
				}
				$data = $this->readFile($file['path'], $file['offset'], $expected);
				if ($data === null) {
					return $this->ioError($requestId, self::FIO_EIO);
				}
				$this->bytesRead += $expected;
				$this->openFiles[$fileId]['offset'] += $expected;
				return [self::MSG_READ_OK, pack('n', $requestId) . $data];
			case self::MSG_SEEK:
				$fileId = $this->unpack('n', $payload, 2, 13);
				$offset = $this->unpack('J', $payload, 4, 13);
				$whence = ord($payload[12]);
				if (!isset($this->openFiles[$fileId])) {
					return $this->ioError($requestId, self::FIO_EINVAL);
				}
				$file = $this->openFiles[$fileId];
				$base = match ($whence) {
					self::FIO_SEEK_SET => 0,
					self::FIO_SEEK_CUR => $file['offset'],
					self::FIO_SEEK_END => $this->files[$file['path']]['size'],
					default => null,
				};
				if ($base === null || $offset > PHP_INT_MAX - $base || $base + $offset < 0) {
					return $this->ioError($requestId, self::FIO_EINVAL);
				}
				$this->openFiles[$fileId]['offset'] = $base + $offset;
				return [self::MSG_SEEK_OK, pack('nJ', $requestId, $base + $offset)];
			case self::MSG_FSTAT:
				$fileId = $this->unpack('n', $payload, 2, 4);
				if (!isset($this->openFiles[$fileId])) {
					return $this->ioError($requestId, self::FIO_EINVAL);
				}
				return [self::MSG_FSTAT_OK, pack('nJN', $requestId, $this->files[$this->openFiles[$fileId]['path']]['size'], self::FILE_MODE)];
			case self::MSG_CLOSE:
				$fileId = $this->unpack('n', $payload, 2, 4);
				if (!isset($this->openFiles[$fileId])) {
					return $this->ioError($requestId, self::FIO_EINVAL);
				}
				unset($this->openFiles[$fileId]);
				return [self::MSG_CLOSE_OK, pack('n', $requestId)];
			default:
				// Writing, truncating, deleting, renaming and creating directories
				return $this->ioError($requestId, self::FIO_EROFS);
		}
	}

	/**
	 * Reads exactly $length bytes at $offset of the file registered as $path
	 */
	private function readFile(string $path, int $offset, int $length): ?string {
		if ($length === 0) {
			return '';
		}
		$stream = $this->files[$path]['stream'];
		// ftell() is not reliable after a failed seek, so the position is tracked here
		if (($this->positions[$path] ?? null) !== $offset && fseek($stream, $offset) !== 0) {
			unset($this->positions[$path]);
			return null;
		}
		unset($this->positions[$path]);
		$data = stream_get_contents($stream, $length);
		if ($data === false || strlen($data) !== $length) {
			return null;
		}
		$this->positions[$path] = $offset + $length;
		return $data;
	}

	/**
	 * @return array{0: int, 1: string}
	 */
	private function ioError(int $requestId, int $errno): array {
		return [self::MSG_IO_ERROR, pack('nN', $requestId, $errno)];
	}

	/**
	 * Unpacks a single value from $payload, which must be at least $minLength bytes long
	 */
	private function unpack(string $format, string $payload, int $offset, int $minLength): int {
		if (strlen($payload) < $minLength) {
			throw new \RuntimeException('Invalid message from ffmpeg-over-ip server');
		}
		return unpack($format, $payload, $offset)[1];
	}

	/**
	 * @param resource $socket
	 * @return array{0: int, 1: string} type and payload
	 */
	private function readMessage($socket): array {
		$header = unpack('Ctype/Nlength', $this->read($socket, 5));
		if ($header['length'] > self::MAX_MESSAGE_SIZE) {
			throw new \RuntimeException('Message from ffmpeg-over-ip server is too large');
		}
		return [$header['type'], $this->read($socket, $header['length'])];
	}

	/**
	 * @param resource $socket
	 */
	private function read($socket, int $length): string {
		$data = '';
		while (strlen($data) < $length) {
			$this->applyTimeout($socket);
			$chunk = fread($socket, $length - strlen($data));
			if ($chunk === false || $chunk === '') {
				if (stream_get_meta_data($socket)['timed_out']) {
					throw new \RuntimeException('ffmpeg-over-ip server did not finish within ' . $this->timeout . ' seconds');
				}
				if (feof($socket)) {
					throw new \RuntimeException('ffmpeg-over-ip server closed the connection');
				}
				continue;
			}
			$data .= $chunk;
		}
		return $data;
	}

	/**
	 * @param resource $socket
	 */
	private function writeMessage($socket, int $type, string $payload): void {
		$data = pack('CN', $type, strlen($payload)) . $payload;
		$offset = 0;
		$this->writing = true;
		// A blocking write restarts its timeout after every partial write, so the deadline is enforced with select
		stream_set_blocking($socket, false);
		try {
			while ($offset < strlen($data)) {
				$remaining = $this->getRemainingTime();
				$read = $except = null;
				$write = [$socket];
				$ready = stream_select($read, $write, $except, (int)$remaining, (int)(($remaining - (int)$remaining) * 1000000));
				if ($ready === false) {
					throw new \RuntimeException('Could not write to ffmpeg-over-ip server');
				}
				if ($ready === 0) {
					continue;
				}
				$written = fwrite($socket, substr($data, $offset, self::WRITE_CHUNK_SIZE));
				if ($written === false) {
					throw new \RuntimeException('Could not write to ffmpeg-over-ip server');
				}
				$offset += $written;
			}
		} finally {
			stream_set_blocking($socket, true);
		}
		$this->writing = false;
	}

	/**
	 * @param resource $socket
	 */
	private function applyTimeout($socket): void {
		$remaining = $this->getRemainingTime();
		stream_set_timeout($socket, (int)$remaining, (int)(($remaining - (int)$remaining) * 1000000));
	}
}
