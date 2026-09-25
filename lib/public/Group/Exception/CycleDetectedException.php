<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Group\Exception;

/**
 * Thrown when adding a nested-group edge would close a cycle in the
 * group hierarchy.
 *
 * @since 34.0.0
 */
class CycleDetectedException extends \InvalidArgumentException {
}
