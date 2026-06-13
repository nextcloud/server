<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Http\Attribute;

use Attribute;

/**
 * Attribute for controller methods that can also be accessed by other websites.
 * See https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS for an explanation of the functionality and the security implications.
 * See https://docs.nextcloud.com/server/latest/developer_manual/digging_deeper/rest_apis.html on how to implement it in your controller.
 *
 * @since 27.0.0
 */
#[Attribute]
class CORS {
}
