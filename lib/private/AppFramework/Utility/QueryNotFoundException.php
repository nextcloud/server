<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework\Utility;

use OCP\AppFramework\QueryException;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Private implementation of the `Psr\Container\NotFoundExceptionInterface`
 *
 * QueryNotFoundException is a simple wrapper over the `QueryException`
 * to fulfill the PSR Container interface.
 *
 * You should not catch this class directly but the `NotFoundExceptionInterface`.
 */
class QueryNotFoundException extends QueryException implements NotFoundExceptionInterface {
}
