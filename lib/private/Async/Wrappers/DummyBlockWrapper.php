<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Async\Wrappers;

use OC\Async\ABlockWrapper;
use OC\Async\Model\Block;
use OCP\Async\Enum\BlockActivity;

class DummyBlockWrapper extends ABlockWrapper {
	public function session(array $metadata): void {
	}
	public function init(): void {
	}

	public function activity(BlockActivity $activity, string $line = ''): void {
	}

	public function end(string $line = ''): void {
	}
}
