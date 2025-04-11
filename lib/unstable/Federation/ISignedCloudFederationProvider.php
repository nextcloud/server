<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Federation;

use OCP\Federation\ICloudFederationProvider;

/**
 * Interface ICloudFederationProvider
 *
 * Enable apps to create their own cloud federation provider
 *
 * @experimental 31.0.0
 */
interface ISignedCloudFederationProvider extends ICloudFederationProvider {

	/**
	 * returns federationId in direct relation (as recipient or as author) of a sharedSecret
	 * the federationId must be the one at the remote end
	 *
	 * @param string $sharedSecret
	 * @param array $payload
	 *
	 * @experimental 31.0.0
	 * @return string
	 */
	public function getFederationIdFromSharedSecret(string $sharedSecret, array $payload): string;
}
