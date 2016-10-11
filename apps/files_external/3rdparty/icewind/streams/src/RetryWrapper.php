<?php
/**
 * Copyright (c) 2016 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Streams;

/**
 * Wrapper that retries reads/writes to remote streams that dont deliver/recieve all requested data at once
 */
class RetryWrapper extends Wrapper {

	/**
	 * Wraps a stream with the provided callbacks
	 *
	 * @param resource $source
	 * @return resource
	 */
	public static function wrap($source) {
		$context = stream_context_create(array(
			'retry' => array(
				'source' => $source
			)
		));
		return Wrapper::wrapSource($source, $context, 'retry', '\Icewind\Streams\RetryWrapper');
	}

	protected function open() {
		$this->loadContext('retry');
		return true;
	}

	public function dir_opendir($path, $options) {
		return false;
	}

	public function stream_open($path, $mode, $options, &$opened_path) {
		return $this->open();
	}

	public function stream_read($count) {
		$result = parent::stream_read($count);

		$bytesReceived = strlen($result);
		while (strlen($result) > 0 && $bytesReceived < $count && !$this->stream_eof()) {
			$result .= parent::stream_read($count - $bytesReceived);
			$bytesReceived = strlen($result);
		}

		return $result;
	}

	public function stream_write($data) {
		$bytesToSend = strlen($data);
		$bytesWritten = parent::stream_write($data);
		$result = $bytesWritten;

		while ($bytesWritten > 0 && $result < $bytesToSend && !$this->stream_eof()) {
			$dataLeft = substr($data, $result);
			$bytesWritten = parent::stream_write($dataLeft);
			$result += $bytesWritten;
		}

		return $result;
	}
}
