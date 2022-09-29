<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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

namespace OC\Repair;

use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class CleanUpAbandonedApps implements IRepairStep {
	protected const ABANDONED_APPS = ['accessibility', 'files_videoplayer'];
	private IConfig $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	public function getName(): string {
		return 'Clean up abandoned apps';
	}

	public function run(IOutput $output): void {
		foreach (self::ABANDONED_APPS as $app) {
			// only remove global app values
			// user prefs of accessibility are dealt with in Theming migration
			// videoplayer did not have user prefs
			$this->config->deleteAppValues($app);
		}
	}
}
