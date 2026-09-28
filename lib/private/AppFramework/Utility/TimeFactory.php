<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\AppFramework\Utility;

use OCP\AppFramework\Utility\ITimeFactory;

/**
 * Use this to get a timestamp or DateTime object in code to remain testable
 *
 * @since 8.0.0
 * @since 27.0.0 Implements the \Psr\Clock\ClockInterface interface
 * @ref https://www.php-fig.org/psr/psr-20/#21-clockinterface
 */
class TimeFactory implements ITimeFactory {
	protected \DateTimeZone $timezone;

	public function __construct() {
		$this->timezone = new \DateTimeZone('UTC');
	}

	/**
	 * @return int the result of a call to time()
	 * @since 8.0.0
	 * @deprecated 26.0.0 {@see ITimeFactory::now()}
	 */
	public function getTime(): int {
		return time();
	}

	/**
	 * @param string $time
	 * @param \DateTimeZone $timezone
	 * @return \DateTime
	 * @since 15.0.0
	 * @deprecated 26.0.0 {@see ITimeFactory::now()}
	 */
	public function getDateTime(string $time = 'now', ?\DateTimeZone $timezone = null): \DateTime {
		return new \DateTime($time, $timezone);
	}

	public function now(): \DateTimeImmutable {
		return new \DateTimeImmutable('now', $this->timezone);
	}
	public function withTimeZone(\DateTimeZone $timezone): static {
		$clone = clone $this;
		$clone->timezone = $timezone;

		return $clone;
	}

	public function getTimeZone(?string $timezone = null): \DateTimeZone {
		if ($timezone !== null) {
			return new \DateTimeZone($timezone);
		}
		return $this->timezone;
	}
}
