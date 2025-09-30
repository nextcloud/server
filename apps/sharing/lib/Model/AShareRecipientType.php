<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Model;

use OCP\IUser;

abstract class AShareRecipientType {
	/**
	 * @return list<string>
	 */
	abstract public function searchRecipients(string $query, int $limit, int $offset): array;

	abstract public function validateRecipient(IUser $creator, string $recipient): bool;

	/**
	 * @return list<string>
	 */
	abstract public function getRecipientValues(IUser $currentUser): array;
}
