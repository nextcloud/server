<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Http\Attribute;

use Attribute;

/**
 * Attribute for controller methods which should be reachable during an ongoing two-factor challenge.
 *
 * This attribute should be used carefully and usually only makes sense together with both the
 * {@see PublicPage} and {@see NoCSRFRequired} annotations.
 *
 * @since 32.0.0
 */
#[Attribute]
class NoTwoFactorRequired {
}
