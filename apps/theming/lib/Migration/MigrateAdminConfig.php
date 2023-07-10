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
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IL10N;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Throwable;

class MigrateAdminConfig implements IRepairStep {
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
		return $this->l10n->t('Failed to clean up the old administration theming images folder');
	}

	public function run(IOutput $output): void {
		$output->info('Migrating administration images');
		$this->migrateAdminImages($output);
		$this->cleanupAdminImages($output);
	}

	private function migrateAdminImages(IOutput $output): void {
		try {
			$images = $this->appData->getFolder('images');
			$output->info('Migrating administration images');

				// get or init the global folder if any
			try {
				$global = $this->appData->getFolder('global');
			} catch (NotFoundException $e) {
				$global = $this->appData->newFolder('global');
			}

			// get or init the new images folder if any
			try {
				$newImages = $global->getFolder('images');
			} catch (NotFoundException $e) {
				$newImages = $global->newFolder('images');
			}
			
			$files = $images->getDirectoryListing();
			$output->startProgress(count($files));
			foreach($files as $file) {
				$newImages->newFile($file->getName(), $file->getContent());
				$output->advance();
			}

			$output->finishProgress();
		} catch(NotFoundException $e) {
			$output->info('No administration images to migrate');
		}
	}


	private function cleanupAdminImages(IOutput $output): void {
		try {
			$images = $this->appData->getFolder('images');
			$images->delete();
		} catch (NotFoundException $e) {
		} catch (Throwable $e) {
			$output->warning($this->l10n->t('Failed to clean up the old administration image folder', [$e->getMessage()]));
		}
	}
}
