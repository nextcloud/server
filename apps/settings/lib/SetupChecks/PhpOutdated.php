<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
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
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class PhpOutdated implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
	) {
	}

	public function getCategory(): string {
		return 'security';
	}

	public function getName(): string {
		return $this->l10n->t('PHP version');
	}

	public function run(): SetupResult {
		if (PHP_VERSION_ID < 80100) {
			return SetupResult::warning($this->l10n->t('You are currently running PHP %s. PHP 8.0 is now deprecated in Nextcloud 27. Nextcloud 28 may require at least PHP 8.1. Please upgrade to one of the officially supported PHP versions provided by the PHP Group as soon as possible.', [PHP_VERSION]), 'https://secure.php.net/supported-versions.php');
		}
		return SetupResult::success($this->l10n->t('You are currently running PHP %s.', [PHP_VERSION]));
	}
}
