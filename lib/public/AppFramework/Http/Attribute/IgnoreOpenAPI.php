<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Http\Attribute;

use Attribute;

/**
 * Attribute for controller methods that should be ignored when generating OpenAPI documentation
 *
 * @since 28.0.0
 * @deprecated 28.0.0 Use {@see OpenAPI} with {@see OpenAPI::SCOPE_IGNORE} instead: `#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]`
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class IgnoreOpenAPI {
}
