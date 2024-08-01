<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Notification;

/**
 * Thrown when {@see \OCP\Notification\IManager::notify()} is called with a notification
 * that does not have all required fields set:
 *
 * - app
 * - user
 * - dateTime
 * - objectType
 * - objectId
 * - subject
 *
 * @since 30.0.0
 */
class IncompleteNotificationException extends \InvalidArgumentException {
}
