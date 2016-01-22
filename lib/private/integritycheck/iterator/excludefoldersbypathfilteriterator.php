<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
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

namespace OC\IntegrityCheck\Iterator;

class ExcludeFoldersByPathFilterIterator extends \RecursiveFilterIterator {
	private $excludedFolders = [];

	public function __construct(\RecursiveIterator $iterator) {
		parent::__construct($iterator);

		$appFolders = \OC::$APPSROOTS;
		foreach($appFolders as $key => $appFolder) {
			$appFolders[$key] = rtrim($appFolder['path'], '/');
		}

		$this->excludedFolders = array_merge([
			rtrim(\OC::$server->getConfig()->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data'), '/'),
			rtrim(\OC::$SERVERROOT.'/themes', '/'),
			rtrim(\OC::$SERVERROOT.'/config', '/'),
		], $appFolders);
	}

	/**
	 * @return bool
	 */
	public function accept() {
		return !in_array(
			$this->current()->getPathName(),
			$this->excludedFolders,
			true
		);
	}
}
