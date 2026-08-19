<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\ObjectStore;

/**
 * Shared configuration between ConnectionTrait and ObjectTrait to ensure both to be in sync
 */
trait S3ConfigTrait {
	protected array $params;

	protected string $bucket;

	/** Maximum number of concurrent multipart uploads */
	protected int $concurrency;

	/** Timeout, in seconds, for the connection to S3 server, not for the
	 *  request. */
	protected float $connectTimeout;

	protected int $timeout;

	protected string|false $proxy;

	protected string $storageClass;

	/** @var int Part size in bytes (float is added for 32bit support) */
	protected int|float $uploadPartSize;

	/** @var int Limit on PUT in bytes (float is added for 32bit support) */
	private int|float $putSizeLimit;

	/** @var int Limit on COPY in bytes (float is added for 32bit support) */
	private int|float $copySizeLimit;

	private bool $useMultipartCopy = true;

	protected int $retriesMaxAttempts;

	/**
	 * Conditional write mode: true (force on), false (off, the default) or 'auto'
	 * (probe the bucket once and cache the result). Opt-in to preserve existing
	 * behaviour on upgrade.
	 */
	protected bool|string $conditionalWrites = false;
}
