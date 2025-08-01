<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Remote\Api;

use OCP\Remote\ICredentials;
use OCP\Remote\IInstance;

/**
 * @since 13.0.0
 * @deprecated 23.0.0
 */
interface IApiFactory {
	/**
	 * @param IInstance $instance
	 * @param ICredentials $credentials
	 * @return IApiCollection
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getApiCollection(IInstance $instance, ICredentials $credentials);
}
