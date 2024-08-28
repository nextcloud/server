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
	 * @return IClient
	 * @since 8.1.0
	 */
	public function newClient(): IClient;
}
