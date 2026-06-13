<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\Http\Client;

/**
 * Interface IClientService
 *
 * @since 8.1.0
 */
interface IClientService {
	/**
	 * @param ?callable $handler Handler that overrides the default CurlHandler
	 * @return IClient
	 * @since 8.1.0
	 * @since 35.0.0 Added $handler optional param
	 */
	public function newClient(?callable $handler = null): IClient;
}
