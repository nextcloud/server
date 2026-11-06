<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use Icewind\Streams\Wrapper;

/**
 * Stream that behaves like php://input: it can neither seek nor report its size.
 */
class NonSeekableStream extends Wrapper {
	public static function wrap($source) {
		$context = stream_context_create([
			'nonseek' => [
				'source' => $source,
			],
		]);
		return Wrapper::wrapSource($source, $context, 'nonseek', self::class);
	}

	#[\Override]
	public function dir_opendir($path, $options) {
		return false;
	}

	#[\Override]
	public function stream_open($path, $mode, $options, &$opened_path) {
		$this->loadContext('nonseek');
		return true;
	}

	#[\Override]
	public function stream_seek($offset, $whence = SEEK_SET) {
		return false;
	}

	#[\Override]
	public function stream_stat() {
		return false;
	}
}
