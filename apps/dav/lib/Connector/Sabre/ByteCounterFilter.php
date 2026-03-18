<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Connector\Sabre;

/**
 * A stream filter to track how many bytes have been streamed from a stream.
 */
class ByteCounterFilter extends \php_user_filter {
	public string $filtername = 'ByteCounter';

	public function filter($in, $out, &$consumed, bool $closing): int {
		$counter = $this->params['counter'] ?? null;

		while ($bucket = stream_bucket_make_writeable($in)) {
			$length = $bucket->datalen;
			$consumed += $length;
			if ($counter instanceof StreamByteCounter) {
				$counter->bytes += $length;
			}
			stream_bucket_append($out, $bucket);
		}

		return PSFS_PASS_ON;
	}
}
