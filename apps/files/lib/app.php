<?php

/**
 * ownCloud - Core
 *
 * @author Morris Jobke
 * @copyright 2013 Morris Jobke morris.jobke@gmail.com
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


namespace OCA\Files;

class App {
	private $l10n;
	private $view;

	public function __construct($view, $l10n) {
		$this->view = $view;
		$this->l10n = $l10n;
	}

	/**
	 * rename a file
	 *
	 * @param string $dir
	 * @param string $oldname
	 * @param string $newname
	 * @return array
	 */
	public function rename($dir, $oldname, $newname) {
		$result = array(
			'success' 	=> false,
			'data'		=> NULL
		);

		// rename to "/Shared" is denied
		if( $dir === '/' and $newname === 'Shared' ) {
			$result['data'] = array(
				'message'	=> $this->l10n->t("Invalid folder name. Usage of 'Shared' is reserved by ownCloud")
			);
		} elseif(
			// rename to "." is denied
			$newname !== '.' and
			// rename of  "/Shared" is denied
			!($dir === '/' and $oldname === 'Shared') and
			// THEN try to rename
			$this->view->rename($dir . '/' . $oldname, $dir . '/' . $newname)
		) {
			// successful rename
			$result['success'] = true;
			$result['data'] = array(
				'dir'		=> $dir,
				'file'		=> $oldname,
				'newname'	=> $newname
			);
		} else {
			// rename failed
			$result['data'] = array(
				'message'	=> $this->l10n->t('%s could not be renamed', array($oldname))
			);
		}
		return $result;
	}

}