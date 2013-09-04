<?php
/**
 * ownCloud
 *
 * @author Bjoern Schiessle
 * @copyright 2013 Bjoern Schiessle schiessle@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files\Share;

class Api {

	/**
	 * @brief get share information for a given file/folder
	 *
	 * @param array $params which contains a 'path' to a file/folder
	 * @return \OC_OCS_Result share information
	 */
	public static function getShare($params) {
		$path = $params['path'];

		$view = new \OC\Files\View('/'.\OCP\User::getUser().'/files');
		$fileInfo = $view->getFileInfo($path);
		if ($fileInfo) {
			$share = \OCP\Share::getItemShared('file', $fileInfo['fileid']);
		} else {
			\OCP\Util::writeLog('files_sharing', 'OCS API getShare, file ' . $path . ' does not exists', \OCP\Util::WARN);
			$share = array();
		}

		return new \OC_OCS_Result($share);
	}

}