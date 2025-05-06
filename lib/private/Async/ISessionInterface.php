<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Async;

use OC\Async\Model\BlockInterface;
use OCP\Async\Enum\BlockStatus;

interface ISessionInterface {
	public function getAll(): array;
	public function byToken(string $token): ?BlockInterface;
	public function byId(string $id): ?BlockInterface;
	public function getGlobalStatus(): BlockStatus;

}
