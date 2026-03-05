<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\AppFramework;

use Exception;
use Psr\Container\ContainerExceptionInterface;

/**
 * Class QueryException
 *
 * The class extends `ContainerExceptionInterface` since 20.0.0
 *
 * @since 8.1.0
 * @deprecated 20.0.0 catch \Psr\Container\ContainerExceptionInterface
 */
class QueryException extends Exception implements ContainerExceptionInterface {
}
