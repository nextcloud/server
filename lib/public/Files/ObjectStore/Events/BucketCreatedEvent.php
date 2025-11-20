<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Files\ObjectStore\Events;

use OCP\EventDispatcher\Event;

/**
 * @since 29.0.16
 */
class BucketCreatedEvent extends Event {

	/**
	 * @param string $bucket
	 * @param string $endpoint
	 * @param string $region
	 * @param string $version
	 *
	 * @since 29.0.16
	 */
	public function __construct(
		private readonly string $bucket,
		private readonly string $endpoint,
		private readonly string $region,
		private readonly string $version = 'latest',
	) {
		parent::__construct();
	}

	/**
	 * @since 29.0.16
	 */
	public function getBucket(): string {
		return $this->bucket;
	}

	/**
	 * @since 29.0.16
	 */
	public function getEndpoint(): string {
		return $this->endpoint;
	}

	/**
	 * @since 29.0.16
	 */
	public function getRegion(): string {
		return $this->region;
	}

	/**
	 * @since 29.0.16
	 */
	public function getVersion(): string {
		return $this->version;
	}
}
