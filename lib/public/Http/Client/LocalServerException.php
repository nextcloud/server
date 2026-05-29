<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Http\Client;

use Psr\Http\Client\ClientExceptionInterface;

/**
 * @since 19.0.0
 */
class LocalServerException extends \RuntimeException implements ClientExceptionInterface {
}
