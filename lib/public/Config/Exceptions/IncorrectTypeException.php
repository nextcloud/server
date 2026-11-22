<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Config\Exceptions;

use Exception;
use OCP\AppFramework\Attribute\Throwable;

#[Throwable(since: '32.0.0')]
class IncorrectTypeException extends Exception {
}
