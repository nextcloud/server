<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
			return SetupResult::error($this->l10n->t('The PHP memory limit is below the recommended value of %s.', Util::humanFileSize(MemoryInfo::RECOMMENDED_MEMORY_LIMIT)));
		}
	}
}
