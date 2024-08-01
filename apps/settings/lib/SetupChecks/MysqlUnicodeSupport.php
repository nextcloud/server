<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class MysqlUnicodeSupport implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('MySQL Unicode support');
	}

	public function getCategory(): string {
		return 'database';
	}

	public function run(): SetupResult {
		if ($this->config->getSystemValueString('dbtype') !== 'mysql') {
			return SetupResult::success($this->l10n->t('You are not using MySQL'));
		}
		if ($this->config->getSystemValueBool('mysql.utf8mb4', false)) {
			return SetupResult::success($this->l10n->t('MySQL is used as database and does support 4-byte characters'));
		} else {
			return SetupResult::warning(
				$this->l10n->t('MySQL is used as database but does not support 4-byte characters. To be able to handle 4-byte characters (like emojis) without issues in filenames or comments for example it is recommended to enable the 4-byte support in MySQL.'),
				$this->urlGenerator->linkToDocs('admin-mysql-utf8mb4'),
			);
		}
	}
}
