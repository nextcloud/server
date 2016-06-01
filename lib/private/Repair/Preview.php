<?php
/**
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Repair;

use OC\Files\View;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class Preview implements IRepairStep {

	public function getName() {
		return 'Cleaning-up broken previews';
	}

	public function run(IOutput $out) {
		$view = new View('/');
		$children = $view->getDirectoryContent('/');

		foreach ($children as $child) {
			if ($view->is_dir($child->getPath())) {
				$thumbnailsFolder = $child->getPath() . '/thumbnails';
				if ($view->is_dir($thumbnailsFolder)) {
					$view->rmdir($thumbnailsFolder);
				}
			}
		}
	}
}
