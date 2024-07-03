<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Diagnostics;

/**
 * Interface IQuery
 *
 * @since 8.0.0
 * @since 30.0.0 The IQueryLogger no longer extends `\Doctrine\DBAL\Logging\SQLLogger`.
 *               Due to more underlying changes the types and parameter content could not be kept consistent.
 */
interface IQuery {
	/**
	 * @since 8.0.0
	 */
	public function getSql(): string;

	/**
	 * @since 8.0.0
	 */
	public function getParams(): ?array;

	/**
	 * @since 30.0.0
	 */
	public function getTypes(): ?array;

	/**
	 * @since 8.0.0
	 */
	public function getDuration(): float;

	/**
	 * @since 11.0.0
	 */
	public function getStartTime(): float;

	/**
	 * @since 11.0.0
	 */
	public function getStacktrace(): array;

	/**
	 * @since 12.0.0
	 * @since 30.0.0 Documented return type was fixed to actual type float
	 * @deprecated 30.0.0  Use {@see self::getStartTime()} instead
	 */
	public function getStart(): float;
}
