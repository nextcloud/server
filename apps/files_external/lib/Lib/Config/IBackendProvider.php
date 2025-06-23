<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Config;

use OCA\Files_External\Lib\Backend\Backend;

/**
 * Provider of external storage backends
 * @since 9.1.0
 */
interface IBackendProvider {

	/**
	 * @since 9.1.0
	 * @return Backend[]
	 */
	public function getBackends();
}
