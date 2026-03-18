<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Connector\Sabre;

/**
 * Class to use in combination with ByteCounterFilter to keep track of how much
 * has been read from a stream.
 *
 * @see ByteCounterFilter
 */
class StreamByteCounter {
	public float|int $bytes = 0;
}
