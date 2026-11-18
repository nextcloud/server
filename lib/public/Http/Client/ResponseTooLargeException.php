<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Http\Client;

use OCP\AppFramework\Attribute\Catchable;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Thrown when the response body is larger than the "max_body_size" request option allows
 *
 * @see IClient
 * @since 36.0.0
 */
#[Catchable(since: '36.0.0')]
class ResponseTooLargeException extends \RuntimeException implements ClientExceptionInterface {
}
