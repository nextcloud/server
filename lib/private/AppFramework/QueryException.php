<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework;

/**
 * Private implementation of the `Psr\Container\ContainerExceptionInterface`
 *
 * Extends the deprecated public `OCP\AppFramework\QueryException` so apps
 * still catching it keep working.
 *
 * You should not catch this class directly but the `ContainerExceptionInterface`.
 */
class QueryException extends \OCP\AppFramework\QueryException {
}
