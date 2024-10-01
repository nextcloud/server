<?php
/**
 * SPDX-FileCopyrightText: 2019 Roeland Jago Douma <roeland@famdouma.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Icewind\Streams;

/**
 * Wrapper that calculates the hash on the stream on read
 *
 * The stream and hash should be passed in when wrapping the stream.
 * On close the callback will be called with the calculated checksum.
 *
 * For supported hashes see: http://php.net/manual/en/function.hash-algos.php
 */
class ReadHashWrapper extends HashWrapper {
	public function stream_read($count) {
		$data = parent::stream_read($count);
		$this->updateHash($data);
		return $data;
	}
}
