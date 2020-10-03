<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Settings\SetupChecks;

use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\SetupChecks\ISetupCheck;

class SqliteDatabase implements ISetupCheck {
	/** @var IL10N */
	private $l10n;
	/** @var IConfig */
	private $config;
	/** @var IURLGenerator */
	private $urlGenerator;

	public function __construct(IL10N $l10n, IConfig $config, IURLGenerator $urlGenerator) {
		$this->l10n = $l10n;
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
	}

	public function description(): string {
		return $this->l10n->t('SQLite is used as database. For larger installations we recommend to switch to a different database.');
	}

	public function severity(): string {
		return ISetupCheck::SEVERITY_WARNING;
	}

	public function passes(): bool {
		return strpos($this->config->getSystemValueString('dbtype'), 'sqlite') === false;
	}

	public function linkToDocumentation(): string {
		return $this->urlGenerator->linkToDocs('admin-db-conversion');
	}
}
