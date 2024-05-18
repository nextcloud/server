<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Robin Appelman <robin@icewind.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Files_External\Lib\Storage;

use Icewind\Streams\File;
use phpseclib\Net\SSH2;

class SFTPWriteStream implements File {
	/** @var resource */
	public $context;

	/** @var \phpseclib\Net\SFTP */
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

	public static function register($protocol = 'sftpwrite') {
		if (in_array($protocol, stream_get_wrappers(), true)) {
			return false;
		}
		return stream_wrapper_register($protocol, get_called_class());
	}

	/**
	 * Load the source from the stream context and return the context options
	 *
	 * @param string $name
	 * @throws \BadMethodCallException
	 */
	protected function loadContext($name) {
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

		$packet = pack('Na*N2', strlen($remote_file), $remote_file, NET_SFTP_OPEN_WRITE | NET_SFTP_OPEN_CREATE | NET_SFTP_OPEN_TRUNCATE, 0);
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

		return true;
	}

	public function stream_seek($offset, $whence = SEEK_SET) {
		return false;
	}

	public function stream_tell() {
		return $this->writePosition;
	}

	public function stream_read($count) {
		return false;
	}

	public function stream_write($data) {
		$written = strlen($data);
		$this->writePosition += $written;

		$this->buffer .= $data;

		if (strlen($this->buffer) > 64 * 1024) {
			if (!$this->stream_flush()) {
				return false;
			}
		}

		return $written;
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
		$size = strlen($this->buffer);
		$packet = pack('Na*N3a*', strlen($this->handle), $this->handle, $this->internalPosition / 4294967296, $this->internalPosition, $size, $this->buffer);
		if (!$this->sftp->_send_sftp_packet(NET_SFTP_WRITE, $packet)) {
			return false;
		}
		$this->internalPosition += $size;
		$this->buffer = '';

		return $this->sftp->_read_put_responses(1);
	}

	public function stream_eof() {
		return $this->eof;
	}

	public function stream_close() {
		$this->stream_flush();
		if (!$this->sftp->_close_handle($this->handle)) {
			return false;
		}
		return true;
	}
}
