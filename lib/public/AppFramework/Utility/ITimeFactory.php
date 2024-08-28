<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\AppFramework\Utility;

use Psr\Clock\ClockInterface;

/**
 * Use this to get a timestamp or DateTime object in code to remain testable
 *
 * @since 8.0.0
 * @since 27.0.0 Extends the \Psr\Clock\ClockInterface interface
 * @ref https://www.php-fig.org/psr/psr-20/#21-clockinterface
 */

interface ITimeFactory extends ClockInterface {
	/**
	 * @return int the result of a call to time()
	 * @since 8.0.0
	 */
	public function getTime(): int;

	/**
	 * @param string $time
	 * @param \DateTimeZone|null $timezone
	 * @return \DateTime
	 * @since 15.0.0
	 */
	public function getDateTime(string $time = 'now', ?\DateTimeZone $timezone = null): \DateTime;

	/**
	 * @param \DateTimeZone $timezone
	 * @return static
	 * @since 26.0.0
	 */
	public function withTimeZone(\DateTimeZone $timezone): static;

	/**
	 * @param string|null $timezone
	 * @return \DateTimeZone Requested timezone if provided, UTC otherwise
	 * @throws \Exception
	 * @since 29.0.0
	 */
	public function getTimeZone(?string $timezone = null): \DateTimeZone;
}
