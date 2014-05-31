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
	/**
	 * @var \OC_L10N
	 */
	private $l10n;

	/**
	 * @var \OCP\INavigationManager
	 */
	private static $navigationManager;

	/**
	 * @var \OC\Files\View
	 */
	private $view;

	public function __construct($view, $l10n) {
		$this->view = $view;
		$this->l10n = $l10n;
	}

	/**
	 * Returns the app's navigation manager
	 *
	 * @return \OCP\INavigationManager
	 */
	public static function getNavigationManager() {
		if (self::$navigationManager === null) {
			self::$navigationManager = new \OC\NavigationManager();
		}
		return self::$navigationManager;
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

		// rename to non-existing folder is denied
		if (!$this->view->file_exists($dir)) {
			$result['data'] = array('message' => (string)$this->l10n->t(
					'The target folder has been moved or deleted.',
					array($dir)),
					'code' => 'targetnotfound'
				);
		// rename to existing file is denied
		} else if ($this->view->file_exists($dir . '/' . $newname)) {

			$result['data'] = array(
				'message'	=> $this->l10n->t(
						"The name %s is already used in the folder %s. Please choose a different name.",
						array($newname, $dir))
			);
		} else if (
			// rename to "." is denied
			$newname !== '.' and
			// THEN try to rename
			$this->view->rename($dir . '/' . $oldname, $dir . '/' . $newname)
		) {
			// successful rename
			$meta = $this->view->getFileInfo($dir . '/' . $newname);
			$fileinfo = \OCA\Files\Helper::formatFileInfo($meta);
			$result['success'] = true;
			$result['data'] = $fileinfo;
		} else {
			// rename failed
			$result['data'] = array(
				'message'	=> $this->l10n->t('%s could not be renamed', array($oldname))
			);
		}
		return $result;
	}

}
