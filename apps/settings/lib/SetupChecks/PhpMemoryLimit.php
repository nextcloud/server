<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\SetupChecks;

use OC\MemoryInfo;
use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;
use OCP\Util;

class PhpMemoryLimit implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private MemoryInfo $memoryInfo,
	) {
	}

	public function getCategory(): string {
		return 'php';
	}

	public function getName(): string {
		return $this->l10n->t('PHP memory limit');
	}

	public function run(): SetupResult {
		if ($this->memoryInfo->isMemoryLimitSufficient()) {
			return SetupResult::success(Util::humanFileSize($this->memoryInfo->getMemoryLimit()));
		} else {
			return SetupResult::error($this->l10n->t('The PHP memory limit is below the recommended value of %s. Some features or apps - including the Updater - may not function properly.', Util::humanFileSize(MemoryInfo::RECOMMENDED_MEMORY_LIMIT)));
		}
	}
}
