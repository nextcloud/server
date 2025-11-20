<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\GlobalScale;

use OCP\IConfig;
use Override;

class Config implements \OCP\GlobalScale\IConfig {
	public function __construct(
		private readonly IConfig $config,
	) {
	}

	#[Override]
	public function isGlobalScaleEnabled(): bool {
		return $this->config->getSystemValueBool('gs.enabled', false);
	}

	#[Override]
	public function onlyInternalFederation(): bool {
		// if global scale is disabled federation works always globally
		$gsEnabled = $this->isGlobalScaleEnabled();
		if ($gsEnabled === false) {
			return false;
		}

		$enabled = $this->config->getSystemValueString('gs.federation', 'internal');

		return $enabled === 'internal';
	}
}
