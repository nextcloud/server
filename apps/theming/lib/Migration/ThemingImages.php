<?php
/**
 * @copyright Copyright (c) 2017 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Haertl <jus@bitgrid.net>
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


namespace OCA\Theming\Migration;

use OCA\Theming\ThemingDefaults;
use OCP\Files\IAppData;
use OCP\Files\IRootFolder;
use OCP\Migration\IRepairStep;
use OCP\Migration\IOutput;
use OC\Files\Node\File;
use OCP\Files\NotFoundException;

class ThemingImages implements IRepairStep {

	private $appData;
	private $rootFolder;

	public function __construct(IAppData $appData, IRootFolder $rootFolder) {
		$this->appData = $appData;
		$this->rootFolder = $rootFolder;
	}

	/*
	 * @inheritdoc
	 */
	public function getName() {
		return 'Move theming files to AppData storage';
	}

	/**
	 * @inheritdoc
	 */
	public function run(IOutput $output) {
		$folder = $this->appData->newFolder("images");
		/** @var File $file */
		$file = null;
		try {
			$file = $this->rootFolder->get('themedinstancelogo');
			$logo = $folder->newFile('logo');
			$logo->putContent($file->getContent());
			$file->delete();
		} catch (NotFoundException $e) {
			$output->info('No theming logo image to migrate');
		}

		try {
			$file = $this->rootFolder->get('themedbackgroundlogo');
			$background = $folder->newFile('background');
			$background->putContent($file->getContent());
			$file->delete();
		} catch (NotFoundException $e) {
			$output->info('No theming background image to migrate');
		}
	}
}
