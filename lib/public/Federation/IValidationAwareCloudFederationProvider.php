<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Federation;

use OCP\Federation\Exceptions\BadRequestException;
use OCP\Federation\Exceptions\ProviderCouldNotAddShareException;

/**
 * Optional extension of {@see ICloudFederationProvider} for providers that
 * can validate an incoming share envelope without producing side effects.
 *
 * @since 34.0.0
 */
interface IValidationAwareCloudFederationProvider extends ICloudFederationProvider {
	/**
	 * Validate the share envelope. Implementations MUST NOT produce
	 * persistent side effects from this method.
	 *
	 * @throws BadRequestException If the envelope is structurally invalid.
	 * @throws ProviderCouldNotAddShareException For other rejections; the
	 *     exception's code is used as the HTTP status.
	 *
	 * @since 34.0.0
	 */
	public function validateShare(ICloudFederationShare $share): void;
}
