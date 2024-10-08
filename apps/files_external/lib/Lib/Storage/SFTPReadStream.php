<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Lib\Storage;

use Icewind\Streams\File;
use phpseclib\Net\SSH2;

class SFTPReadStream implements File {
	/** @var resource */
	public $context;

	/** @var \phpseclib\Net\SFTP */
	private $sftp;

	/** @var string */
	private $handle;

	/** @var int */
	private $internalPosition = 0;

	/** @var int */
	private $readPosition = 0;

	/** @var bool */
	private $eof = false;

	private $buffer = '';
	private bool $pendingRead = false;
	private int $size = 0;

	public static function register($protocol = 'sftpread') {
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
		if (isset($context['session']) and $context['session'] instanceof \phpseclib\Net\SFTP) {
			$this->sftp = $context['session'];
		} else {
			throw new \BadMethodCallException('Invalid context, session not set');
		}
		if (isset($context['size'])) {
			$this->size = $context['size'];
		}
		return $context;
	}

	public function stream_open($path, $mode, $options, &$opened_path) {
		[, $path] = explode('://', $path);
		$path = '/' . ltrim($path);
		$path = str_replace('//', '/', $path);

		$this->loadContext('sftp');

		if (!($this->sftp->bitmap & SSH2::MASK_LOGIN)) {
			return false;
		}

		$remote_file = $this->sftp->_realpath($path);
		if ($remote_file === false) {
			return false;
		}

		$packet = pack('Na*N2', strlen($remote_file), $remote_file, NET_SFTP_OPEN_READ, 0);
		if (!$this->sftp->_send_sftp_packet(NET_SFTP_OPEN, $packet)) {
			return false;
		}

		$response = $this->sftp->_get_sftp_packet();
		switch ($this->sftp->packet_type) {
			case NET_SFTP_HANDLE:
				$this->handle = substr($response, 4);
				break;
			case NET_SFTP_STATUS: // presumably SSH_FX_NO_SUCH_FILE or SSH_FX_PERMISSION_DENIED
				$this->sftp->_logError($response);
				return false;
			default:
				user_error('Expected SSH_FXP_HANDLE or SSH_FXP_STATUS');
				return false;
		}

		$this->request_chunk(256 * 1024);

		return true;
	}

	public function stream_seek($offset, $whence = SEEK_SET) {
		switch ($whence) {
			case SEEK_SET:
				$this->seekTo($offset);
				break;
			case SEEK_CUR:
				$this->seekTo($this->readPosition + $offset);
				break;
			case SEEK_END:
				$this->seekTo($this->size + $offset);
				break;
		}
		return true;
	}

	private function seekTo(int $offset): void {
		$this->internalPosition = $offset;
		$this->readPosition = $offset;
		$this->buffer = '';
		$this->request_chunk(256 * 1024);
	}

	public function stream_tell() {
		return $this->readPosition;
	}

	public function stream_read($count) {
		if (!$this->eof && strlen($this->buffer) < $count) {
			$chunk = $this->read_chunk();
			$this->buffer .= $chunk;
			if (!$this->eof) {
				$this->request_chunk(256 * 1024);
			}
		}

		$data = substr($this->buffer, 0, $count);
		$this->buffer = substr($this->buffer, $count);
		$this->readPosition += strlen($data);

		return $data;
	}

	private function request_chunk(int $size) {
		if ($this->pendingRead) {
			$this->sftp->_get_sftp_packet();
		}

		$packet = pack('Na*N3', strlen($this->handle), $this->handle, $this->internalPosition / 4294967296, $this->internalPosition, $size);
		$this->pendingRead = true;
		return $this->sftp->_send_sftp_packet(NET_SFTP_READ, $packet);
	}

	private function read_chunk() {
		$this->pendingRead = false;
		$response = $this->sftp->_get_sftp_packet();

		switch ($this->sftp->packet_type) {
			case NET_SFTP_DATA:
				$temp = substr($response, 4);
				$len = strlen($temp);
				$this->internalPosition += $len;
				return $temp;
			case NET_SFTP_STATUS:
				[1 => $status] = unpack('N', substr($response, 0, 4));
				if ($status == NET_SFTP_STATUS_EOF) {
					$this->eof = true;
				}
				return '';
			default:
				return '';
		}
	}

	public function stream_write($data) {
		return false;
	}

	public function stream_set_option($option, $arg1, $arg2) {
		return false;
	}

	public function stream_truncate($size) {
		return false;
	}

	public function stream_stat() {
		return false;
	}

	public function stream_lock($operation) {
		return false;
	}

	public function stream_flush() {
		return false;
	}

	public function stream_eof() {
		return $this->eof;
	}

	public function stream_close() {
		// we still have a read request incoming that needs to be handled before we can close
		if ($this->pendingRead) {
			$this->sftp->_get_sftp_packet();
		}
		if (!$this->sftp->_close_handle($this->handle)) {
			return false;
		}
		return true;
	}
}
