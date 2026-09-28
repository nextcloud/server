<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\App\AppStore\Version;

class Version {
	/**
	 * @param string $minVersion
	 * @param string $maxVersion
	 */
	public function __construct(
		private string $minVersion,
		private string $maxVersion,
	) {
	}

	/**
	 * @return string
	 */
	public function getMinimumVersion() {
		return $this->minVersion;
	}

	/**
	 * @return string
	 */
	public function getMaximumVersion() {
		return $this->maxVersion;
	}
}
