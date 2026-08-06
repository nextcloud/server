<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Lib\Storage;

use Icewind\Streams\File;
use phpseclib3\Net\SSH2;

class SFTPWriteStream implements File {
	use SFTPReflection;

	/** @var resource */
	public $context;

	/** @var \phpseclib3\Net\SFTP */
	private $sftp;

	/** @var string */
	private $handle;

	/** @var int */
	private $internalPosition = 0;

	/** @var int */
	private $writePosition = 0;

	/** @var bool */
	private $eof = false;

	private $buffer = '';

	private string $path;

	/** Payload budget for a single SSH_FXP_WRITE packet */
	private int $packetSize = 0;

	/** Number of SSH_FXP_WRITE packets sent but not yet acknowledged */
	private int $outstanding = 0;

	/** Mirrors phpseclib's NET_SFTP_UPLOAD_QUEUE_SIZE */
	private const int QUEUE_SIZE = 1024;

	/** 2^32, used to split a 64-bit file offset into its high and low 32-bit words */
	private const int UINT32_MODULUS = 2 ** 32;

	/** Default SFTP payload size (32 KiB) used when the negotiated maximum is unavailable */
	private const int DEFAULT_MAX_PACKET_SIZE = 1 << 15;

	/** Bytes reserved in an SSH_FXP_WRITE packet for the handle-length, offset and data-length fields */
	private const int WRITE_PACKET_OVERHEAD = 25;

	/** Length of the 32-bit "string" length prefix preceding the handle in an SSH_FXP_HANDLE response */
	private const int STRING_LENGTH_PREFIX = 4;

	public static function register($protocol = 'sftpwrite') {
		if (in_array($protocol, stream_get_wrappers(), true)) {
			return false;
		}
		return stream_wrapper_register($protocol, get_called_class());
	}

	/**
	 * Load the source from the stream context and return the context options
	 *
	 * @throws \BadMethodCallException
	 */
	protected function loadContext(string $name) {
		$context = stream_context_get_options($this->context);
		if (isset($context[$name])) {
			$context = $context[$name];
		} else {
			throw new \BadMethodCallException('Invalid context, "' . $name . '" options not set');
		}
		if (isset($context['session']) && $context['session'] instanceof \phpseclib3\Net\SFTP) {
			$this->sftp = $context['session'];
		} else {
			throw new \BadMethodCallException('Invalid context, session not set');
		}
		return $context;
	}

	#[\Override]
	public function stream_open($path, $mode, $options, &$opened_path) {
		[, $path] = explode('://', $path);
		$path = '/' . ltrim($path);
		$path = str_replace('//', '/', $path);

		$this->loadContext('sftp');

		if (!($this->getSftpProperty($this->sftp, 'bitmap') & SSH2::MASK_LOGIN)) {
			return false;
		}

		$remote_file = $this->sftp->realpath($path);
		if ($remote_file === false) {
			return false;
		}
		$this->path = $remote_file;

		$packet = pack('Na*N2', strlen($remote_file), $remote_file, NET_SFTP_OPEN_WRITE | NET_SFTP_OPEN_CREATE | NET_SFTP_OPEN_TRUNCATE, 0);
		try {
			$this->invokeSftp($this->sftp, 'send_sftp_packet', [NET_SFTP_OPEN, $packet]);
		} catch (\Throwable) {
			return false;
		}

		$response = $this->invokeSftp($this->sftp, 'get_sftp_packet');
		switch ($this->getSftpProperty($this->sftp, 'packet_type')) {
			case NET_SFTP_HANDLE:
				$this->handle = substr($response, self::STRING_LENGTH_PREFIX);
				break;
			case NET_SFTP_STATUS: // presumably SSH_FX_NO_SUCH_FILE or SSH_FX_PERMISSION_DENIED
				$this->invokeSftp($this->sftp, 'logError', [$response]);
				return false;
			default:
				user_error('Expected SSH_FXP_HANDLE or SSH_FXP_STATUS');
				return false;
		}

		// Size each SSH_FXP_WRITE to the negotiated maximum packet, leaving room
		// for the handle and the packet header (mirrors phpseclib's SFTP::put()).
		$maxPacket = $this->getSftpProperty($this->sftp, 'max_sftp_packet') ?: self::DEFAULT_MAX_PACKET_SIZE;
		$this->packetSize = max(1, $maxPacket - strlen($this->handle) - self::WRITE_PACKET_OVERHEAD);

		return true;
	}

	#[\Override]
	public function stream_seek($offset, $whence = SEEK_SET) {
		return false;
	}

	#[\Override]
	public function stream_tell() {
		return $this->writePosition;
	}

	#[\Override]
	public function stream_read($count) {
		return false;
	}

	#[\Override]
	public function stream_write($data) {
		$written = strlen($data);
		$this->writePosition += $written;

		$this->buffer .= $data;

		// Send full packets as soon as enough data is buffered, without waiting
		// for each acknowledgement, so the upload stays pipelined.
		while (strlen($this->buffer) >= $this->packetSize) {
			if (!$this->sendChunk(substr($this->buffer, 0, $this->packetSize))) {
				return false;
			}
			$this->buffer = substr($this->buffer, $this->packetSize);
		}

		return $written;
	}

	/**
	 * Send a single SSH_FXP_WRITE packet without waiting for its response.
	 *
	 * Acknowledgements are only drained once the queue is full or the stream is
	 * flushed/closed. Blocking on every packet would turn each write into a full
	 * round trip, which is what made the previous implementation slow.
	 */
	private function sendChunk(string $chunk): bool {
		$size = strlen($chunk);
		$packet = pack('Na*N3a*', strlen($this->handle), $this->handle, $this->internalPosition / self::UINT32_MODULUS, $this->internalPosition, $size, $chunk);
		try {
			$this->invokeSftp($this->sftp, 'send_sftp_packet', [NET_SFTP_WRITE, $packet]);
		} catch (\Throwable) {
			return false;
		}
		$this->internalPosition += $size;
		$this->outstanding++;

		if ($this->outstanding >= self::QUEUE_SIZE) {
			return $this->drainResponses();
		}

		return true;
	}

	/**
	 * Read the acknowledgements for all packets sent since the last drain.
	 */
	private function drainResponses(): bool {
		if ($this->outstanding === 0) {
			return true;
		}
		$result = $this->invokeSftp($this->sftp, 'read_put_responses', [$this->outstanding]);
		$this->outstanding = 0;
		return $result;
	}

	#[\Override]
	public function stream_set_option($option, $arg1, $arg2) {
		return false;
	}

	#[\Override]
	public function stream_truncate($size) {
		return false;
	}

	#[\Override]
	public function stream_stat() {
		return false;
	}

	#[\Override]
	public function stream_lock($operation) {
		return false;
	}

	#[\Override]
	public function stream_flush() {
		// Flush the trailing partial packet, then wait for all outstanding writes.
		if ($this->buffer !== '') {
			if (!$this->sendChunk($this->buffer)) {
				return false;
			}
			$this->buffer = '';
		}

		return $this->drainResponses();
	}

	#[\Override]
	public function stream_eof() {
		return $this->eof;
	}

	#[\Override]
	public function stream_close() {
		$this->stream_flush();
		if (!$this->invokeSftp($this->sftp, 'close_handle', [$this->handle])) {
			return false;
		}
		$this->sftp->touch($this->path, time(), time());

		return true;
	}
}
