<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Http\Client;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;

/**
 * Response sink that refuses data beyond a maximum size, which makes cURL abort the transfer
 */
class SizeLimitedSink implements StreamInterface {
	use StreamDecoratorTrait;

	private int $written = 0;

	public function __construct(
		private StreamInterface $stream,
		private int $maxSize,
	) {
	}

	#[\Override]
	public function write(string $string): int {
		$this->written += strlen($string);
		if ($this->isExceeded()) {
			return 0;
		}
		return $this->stream->write($string);
	}

	public function isExceeded(): bool {
		return $this->written > $this->maxSize;
	}
}
