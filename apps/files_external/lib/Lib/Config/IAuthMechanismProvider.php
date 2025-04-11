<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Config;

use OCA\Files_External\Lib\Auth\AuthMechanism;

/**
 * Provider of external storage auth mechanisms
 * @since 9.1.0
 */
interface IAuthMechanismProvider {

	/**
	 * @since 9.1.0
	 * @return AuthMechanism[]
	 */
	public function getAuthMechanisms();
}
