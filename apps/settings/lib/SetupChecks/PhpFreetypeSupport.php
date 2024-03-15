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
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class PhpFreetypeSupport implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('Freetype');
	}

	public function getCategory(): string {
		return 'php';
	}

	/**
	 * Check if the required FreeType functions are present
	 */
	protected function hasFreeTypeSupport(): bool {
		return function_exists('imagettfbbox') && function_exists('imagettftext');
	}

	public function run(): SetupResult {
		if ($this->hasFreeTypeSupport()) {
			return SetupResult::success($this->l10n->t('Supported'));
		} else {
			return SetupResult::info(
				$this->l10n->t('Your PHP does not have FreeType support, resulting in breakage of profile pictures and the settings interface.'),
			);
		}
	}
}
