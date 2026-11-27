<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Import;

use OCP\Import\IImportScope;

class ImportScope implements IImportScope {
	private int $depth = 0;

	#[\Override]
	public function run(callable $callback): mixed {
		$this->depth++;
		try {
			return $callback();
		} finally {
			$this->depth--;
		}
	}

	#[\Override]
	public function isActive(): bool {
		return $this->depth > 0;
	}
}
