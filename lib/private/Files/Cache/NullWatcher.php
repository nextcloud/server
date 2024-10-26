<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Cache;

class NullWatcher extends Watcher {
	private $policy;

	public function __construct() {
	}

	public function setPolicy($policy) {
		$this->policy = $policy;
	}

	public function getPolicy() {
		return $this->policy;
	}

	public function checkUpdate($path, $cachedEntry = null) {
		return false;
	}

	public function update($path, $cachedData) {
	}

	public function needsUpdate($path, $cachedData) {
		return false;
	}

	public function cleanFolder($path) {
	}
}
