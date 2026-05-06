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

	#[\Override]
	public function setPolicy($policy) {
		$this->policy = $policy;
	}

	#[\Override]
	public function getPolicy() {
		return $this->policy;
	}

	#[\Override]
	public function checkUpdate($path, $cachedEntry = null) {
		return false;
	}

	#[\Override]
	public function update($path, $cachedData) {
	}

	#[\Override]
	public function needsUpdate($path, $cachedData) {
		return false;
	}

	#[\Override]
	public function cleanFolder($path) {
	}
}
