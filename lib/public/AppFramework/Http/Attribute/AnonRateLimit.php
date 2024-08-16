<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Http\Attribute;

use Attribute;

/**
 * Attribute for controller methods that want to limit the times a not logged-in
 * guest can call the endpoint in a given time period.
 *
 * @since 27.0.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
class AnonRateLimit extends ARateLimit {
}
