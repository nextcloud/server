<?php
/**
 * SPDX-FileCopyrightText: 2019 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Icewind\Streams;

/**
 * Wrapper that calculates the hash on the stream on write
 *
 * The stream and hash should be passed in when wrapping the stream.
 * On close the callback will be called with the calculated checksum.
 *
 * For supported hashes see: http://php.net/manual/en/function.hash-algos.php
 */
class WriteHashWrapper extends HashWrapper {
	public function stream_write($data) {
		$this->updateHash($data);
		return parent::stream_write($data);
	}
}
