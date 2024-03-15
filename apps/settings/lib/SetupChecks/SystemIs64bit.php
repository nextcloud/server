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

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class SystemIs64bit implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('Architecture');
	}

	public function getCategory(): string {
		return 'system';
	}

	protected function is64bit(): bool {
		if (PHP_INT_SIZE < 8) {
			return false;
		} else {
			return true;
		}
	}

	public function run(): SetupResult {
		if ($this->is64bit()) {
			return SetupResult::success($this->l10n->t('64-bit'));
		} else {
			return SetupResult::warning(
				$this->l10n->t('It seems like you are running a 32-bit PHP version. Nextcloud needs 64-bit to run well. Please upgrade your OS and PHP to 64-bit!'),
				$this->urlGenerator->linkToDocs('admin-system-requirements')
			);
		}
	}
}
