<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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

namespace OC\IntegrityCheck\Iterator;

/**
 * Class ExcludeFileByNameFilterIterator provides a custom iterator which excludes
 * entries with the specified file name from the file list.
 *
 * @package OC\Integritycheck\Iterator
 */
class ExcludeFileByNameFilterIterator extends \RecursiveFilterIterator {
	/**
	 * Array of excluded file names. Those are not scanned by the integrity checker.
	 * This is used to exclude files which administrators could upload by mistakes
	 * such as .DS_Store files.
	 *
	 * @var array
	 */
	private $excludedFilenames = [
		'.DS_Store', // Mac OS X
		'Thumbs.db', // Microsoft Windows
		'.directory', // Dolphin (KDE)
		'.webapp', // Gentoo/Funtoo & derivatives use a tool known as webapp-config to manager wep-apps.
	];

	/**
	 * @return bool
	 */
	public function accept() {
		if($this->isDir()) {
			return true;
		}

		return !in_array(
			$this->current()->getFilename(),
			$this->excludedFilenames,
			true
		);
	}
}
