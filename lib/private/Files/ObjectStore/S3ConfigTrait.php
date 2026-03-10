<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024-2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\ObjectStore;

/**
 * Shared configuration parameters, used by both S3 connection and object logic, to keep them in sync.
 *
 * S3ConnectionTrait primarily uses the `$params` property as a container for all connection
 * configuration, which may include raw config from user input, normalized and merged values,
 * and details for advanced connection handling (hostname, region, credentials, etc.).
 *
 * S3ObjectTrait and related object-level code primarily use the dedicated, typed properties
 * defined below (e.g., $bucket, $uploadPartSize, $timeout) for object-specific features.
 *
 * @todo: There is overlap between `$params` and the individual properties. It is currently
 * unclear whether this distinction is the result of deliberate separation of concerns
 * (connection-wide vs object-specific settings), or if it is technical debt.
 *
 * @todo: Some of the default values assigned below are currently - generally unnecessarily -
 * overridden elsewhere.
 */
trait S3ConfigTrait {
	 // S3 connection configuration parameters.
	protected array $params = [];

	/* 
	 * Overlap between the above array and the individual properties is currently expected
	 * (see above). Most object related operations use the individual properties below.
	 */

	// Bucket name
	protected string $bucket = '';

	// Max concurrent multipart uploads
	protected int $concurrency = 5;

	// Server connection timeout in seconds (not total request timeout).
	protected float $connectTimeout = 5;

	// Total request timeout in seconds
	protected int $timeout = 15;

	// Proxy URL string or false if not in use.
	protected string|false $proxy = false;

	// Storage class for new objects
	protected string $storageClass = 'STANDARD';

	// Multipart upload part size in bytes (int|float for 32-bit compatibility).
	protected int|float $uploadPartSize = 524288000; // 500 MiB default

	// Maximum allowed PUT size in bytes (int|float for 32-bit compatibility).
	private int|float $putSizeLimit = 104857600; // 100 MiB default

	// Maximum allowed COPY size in bytes (int|float for 32-bit compatibility).
	private int|float $copySizeLimit = 5242880000; // ~5 GiB default

	// Should multipart copy be used when copying large objects?
	private bool $useMultipartCopy = true;

	// Max retry attempts for S3 API calls.
	protected int $retriesMaxAttempts = 5;
}
