<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCP\TaskProcessing\Exception;

/**
 * Exception thrown during processing of a task
 * by a synchronous provider
 * @since 30.0.0
 */
class ProcessingException extends \RuntimeException {
}
