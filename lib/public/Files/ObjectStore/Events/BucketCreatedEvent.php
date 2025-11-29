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
	private string $bucket;
	private string $endpoint;
	private string $region;
	private string $version;

	/**
	 * @param string $bucket
	 * @param string $endpoint
	 * @param string $region
	 * @param string $version
	 *
	 * @since 29.0.16
	 */
	public function __construct(
		string $bucket,
		string $endpoint,
		string $region,
		string $version = 'latest',
	) {
		parent::__construct();

		$this->bucket = $bucket;
		$this->endpoint = $endpoint;
		$this->region = $region;
		$this->version = $version;
	}

	/**
	 * @return string
	 * @since 29.0.16
	 */
	public function getBucket(): string {
		return $this->bucket;
	}

	/**
	 * @return string
	 * @since 29.0.16
	 */
	public function getEndpoint(): string {
		return $this->endpoint;
	}

	/**
	 * @return string
	 * @since 29.0.16
	 */
	public function getRegion(): string {
		return $this->region;
	}

	/**
	 * @return string
	 * @since 29.0.16
	 */
	public function getVersion(): string {
		return $this->version;
	}
}
