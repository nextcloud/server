<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019, Morris Jobke <hey@morrisjobke.de>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Repair\NC16;

use OC\IntegrityCheck\Checker;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class CleanupCypressFiles
 *
 * This repair step removes "cypress" files and folder created by viewer app in 16.0.1
 *
 * See https://github.com/nextcloud/server/issues/16229 for more details.
 *
 * @deprecated - can be removed in 18 because this is the first version where no migration from 16 can happen
 */
class RemoveCypressFiles implements IRepairStep {

	/** @var Checker $checker */
	private $checker;

	private $pathToViewerApp = __DIR__ . '/../../../../apps/viewer';

	public function getName(): string {
		return 'Cleanup cypress files from viewer app';
	}

	public function __construct(Checker $checker) {
		$this->checker = $checker;
	}

	public function run(IOutput $output): void {
		$file = $this->pathToViewerApp . '/cypress.json';
		if (file_exists($file)) {
			unlink($file);
		}

		$dir = $this->pathToViewerApp . '/cypress';
		if (is_dir($dir)) {
			$files = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
				\RecursiveIteratorIterator::CHILD_FIRST
			);

			foreach ($files as $fileInfo) {
				/** @var \SplFileInfo $fileInfo */
				if ($fileInfo->isLink()) {
					unlink($fileInfo->getPathname());
				} else if ($fileInfo->isDir()) {
					rmdir($fileInfo->getRealPath());
				} else {
					unlink($fileInfo->getRealPath());
				}
			}
			rmdir($dir);
		}

		// re-run the instance verification
		$this->checker->runInstanceVerification();
	}
}
