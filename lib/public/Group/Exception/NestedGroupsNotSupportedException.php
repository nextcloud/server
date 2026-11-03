<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Group\Exception;

/**
 * Thrown when a nested-group operation is requested on an instance without
 * any backend supporting nesting.
 *
 * @since 34.0.0
 */
class NestedGroupsNotSupportedException extends \RuntimeException {
}
