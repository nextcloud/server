<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Settings\SetupChecks;

use OCP\IConfig;
use OCP\IL10N;

class NeedsSystemAddressBookSync {
	public function __construct(private IConfig $config, private IL10N $l10n) {}

	public function description(): string {
		return $this->l10n->t('The DAV system address book sync has not run yet as your instance has more than 1000 users or because an error occurred. Please run it manually by calling "occ dav:sync-system-addressbook".');
	}

	public function severity(): string {
		return 'warning';
	}

	public function run(): bool {
		return $this->config->getAppValue('dav', 'needs_system_address_book_sync', 'no') === 'no';
	}
}
