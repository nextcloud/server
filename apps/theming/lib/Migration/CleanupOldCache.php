<?php

declare(strict_types=1);

/**
 * @copyright 2022 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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

namespace OCA\Theming\Migration;

use OCP\Files\IAppData;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IL10N;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Throwable;

class CleanupOldCache implements IRepairStep {
	private const CACHE_FOLDERS = [
		'global',
		'users',
	];

	private IAppData $appData;
	private IL10N $l10n;

	public function __construct(
		IAppData $appData,
		IL10N $l10n
	) {
		$this->appData = $appData;
		$this->l10n = $l10n;
	}

	public function getName(): string {
		return $this->l10n->t('Cleanup old theming cache');
	}

	public function run(IOutput $output): void {
		$folders = array_filter(
			$this->appData->getDirectoryListing(),
			fn (ISimpleFolder $folder): bool => !in_array($folder->getName(), static::CACHE_FOLDERS, true),
		);

		$output->startProgress(count($folders));

		foreach ($folders as $folder) {
			try {
				$folder->delete();
			} catch (Throwable $e) {
				$output->warning($this->l10n->t('Failed to delete folder: "%1$s", error: %2$s', [$folder->getName(), $e->getMessage()]));
			}
			$output->advance();
		}

		$output->finishProgress();
	}
}
