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

class PhpGetEnv implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('PHP getenv');
	}

	public function getCategory(): string {
		return 'php';
	}

	public function run(): SetupResult {
		if (!empty(getenv('PATH'))) {
			return SetupResult::success();
		} else {
			return SetupResult::warning($this->l10n->t('PHP does not seem to be setup properly to query system environment variables. The test with getenv("PATH") only returns an empty response.'), $this->urlGenerator->linkToDocs('admin-php-fpm'));
		}
	}
}
