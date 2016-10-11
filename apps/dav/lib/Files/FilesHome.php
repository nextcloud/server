<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
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
namespace OCA\DAV\Files;

use OCA\DAV\Connector\Sabre\Directory;
use Sabre\DAV\Exception\Forbidden;
use Sabre\HTTP\URLUtil;

class FilesHome extends Directory {

	/**
	 * @var array
	 */
	private $principalInfo;

	/**
	 * FilesHome constructor.
	 *
	 * @param array $principalInfo
	 */
	public function __construct($principalInfo) {
		$this->principalInfo = $principalInfo;
		$view = \OC\Files\Filesystem::getView();
		$rootInfo = $view->getFileInfo('');
		parent::__construct($view, $rootInfo);
	}

	function delete() {
		throw new Forbidden('Permission denied to delete home folder');
	}

	function getName() {
		list(,$name) = URLUtil::splitPath($this->principalInfo['uri']);
		return $name;
	}

	function setName($name) {
		throw new Forbidden('Permission denied to rename this folder');
	}
}
