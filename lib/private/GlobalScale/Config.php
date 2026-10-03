<?php

declare(strict_types=1);

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

	#[Override]
	public function isPrimary(): bool {
		return $this->isGlobalScaleEnabled()
			&& ($this->config->getSystemValueString('gss.mode', 'slave') === 'master'
				|| $this->config->getSystemValueString('gss.mode', 'slave') === 'primary');
	}

	#[Override]
	public function isSecondary(): bool {
		return $this->isGlobalScaleEnabled()
			&& ($this->config->getSystemValueString('gss.mode', 'slave') === 'slave'
				|| $this->config->getSystemValueString('gss.mode', 'slave') === 'secondary');
	}

	#[Override]
	public function isPrimaryAdmin(string $userId): bool {
		return in_array($userId, $this->config->getSystemValue('gss.master.admin', []), true)
			|| in_array($userId, $this->config->getSystemValue('gss.primary.admin', []), true);
	}
}
