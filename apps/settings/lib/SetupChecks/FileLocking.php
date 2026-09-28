<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
		return $this->l10n->t('Transactional File Locking');
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
			return SetupResult::error(
				$this->l10n->t('Transactional File Locking is disabled. This is not a a supported configuraton. It may lead to difficult to isolate problems including file corruption. Please remove the `\'filelocking.enabled\' => false` configuration entry from your `config.php` to avoid these problems.'),
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
