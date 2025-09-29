<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Http\Client;

/**
 * Interface IResponse
 *
 * @since 8.1.0
 */
interface IResponse {
	/**
	 * @return null|resource|string
	 * @since 8.1.0
	 * @sicne 8.2.0 with stream enabled, the function returns null or a resource
	 */
	public function getBody();

	/**
	 * @return int
	 * @since 8.1.0
	 */
	public function getStatusCode(): int;

	/**
	 * @param string $key
	 * @return string
	 * @since 8.1.0
	 */
	public function getHeader(string $key): string;

	/**
	 * @return array
	 * @since 8.1.0
	 */
	public function getHeaders(): array;
}
