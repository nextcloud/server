<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Storage;

use OC\Files\Cache\LocalRootScanner;

class LocalRootStorage extends Local {
	public function getScanner($path = '', $storage = null) {
		if (!$storage) {
			$storage = $this;
		}
		if (!isset($storage->scanner)) {
			$storage->scanner = new LocalRootScanner($storage);
		}
		return $storage->scanner;
	}
}
