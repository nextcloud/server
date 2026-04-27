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
 * @since 33.0.0
 */
#[Consumable(since: '33.0.0')]
class BucketCreatedEvent extends Event {

	/**
	 * @since 33.0.0
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
	 * @since 33.0.0
	 */
	public function getBucket(): string {
		return $this->bucket;
	}

	/**
	 * @since 33.0.0
	 */
	public function getEndpoint(): string {
		return $this->endpoint;
	}

	/**
	 * @since 33.0.0
	 */
	public function getRegion(): string {
		return $this->region;
	}

	/**
	 * @since 33.0.0
	 */
	public function getVersion(): string {
		return $this->version;
	}
}
