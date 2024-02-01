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

use OC\Lock\DBLockingProvider;
use OC\Lock\NoopLockingProvider;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Lock\ILockingProvider;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class FileLocking implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private ILockingProvider $lockingProvider,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('File locking');
	}

	public function getCategory(): string {
		return 'system';
	}

	protected function hasWorkingFileLocking(): bool {
		return !($this->lockingProvider instanceof NoopLockingProvider);
	}

	protected function hasDBFileLocking(): bool {
		return ($this->lockingProvider instanceof DBLockingProvider);
	}

	public function run(): SetupResult {
		if (!$this->hasWorkingFileLocking()) {
			return SetupResult::warning(
				$this->l10n->t('Transactional file locking is disabled, this might lead to issues with race conditions. Enable "filelocking.enabled" in config.php to avoid these problems.'),
				$this->urlGenerator->linkToDocs('admin-transactional-locking')
			);
		}

		if ($this->hasDBFileLocking()) {
			return SetupResult::info(
				$this->l10n->t('The database is used for transactional file locking. To enhance performance, please configure memcache, if available.'),
				$this->urlGenerator->linkToDocs('admin-transactional-locking')
			);
		}

		return SetupResult::success();
	}
}
