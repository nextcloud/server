<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Files\ObjectStore\Events;

use OCP\AppFramework\Attribute\Consumable;
use OCP\EventDispatcher\Event;

/**
 * @since 32.0.3
 */
#[Consumable(since: '32.0.3')]
class BucketCreatedEvent extends Event {

	/**
	 * @since 32.0.3
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
	 * @since 32.0.3
	 */
	public function getBucket(): string {
		return $this->bucket;
	}

	/**
	 * @since 32.0.3
	 */
	public function getEndpoint(): string {
		return $this->endpoint;
	}

	/**
	 * @since 32.0.3
	 */
	public function getRegion(): string {
		return $this->region;
	}

	/**
	 * @since 32.0.3
	 */
	public function getVersion(): string {
		return $this->version;
	}
}
