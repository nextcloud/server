<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Lib\Storage;

use Icewind\SMB\System;
use OCP\IBinaryFinder;

/**
 * Bridge the NC and SMB binary finding logic
 */
class SystemBridge extends System {
	public function __construct(
		private IBinaryFinder $binaryFinder,
	) {
	}

	protected function getBinaryPath(string $binary): ?string {
		$path = $this->binaryFinder->findBinaryPath($binary);
		return $path !== false ? $path : null;
	}
}
