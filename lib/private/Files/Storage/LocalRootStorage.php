<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Storage;

use OC\Files\Cache\LocalRootScanner;
use OCP\Files\Cache\IScanner;

class LocalRootStorage extends Local {
	public function getScanner($path = '', $storage = null): IScanner {
		if (!$storage) {
			$storage = $this;
		}
		return $storage->scanner ?? ($storage->scanner = new LocalRootScanner($storage));
	}
}
