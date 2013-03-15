<?php

/**
* ownCloud
*
* @author Robin Appelman
* @copyright 2011 Robin Appelman icewind1991@gmail.com
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

/**
 * user quota management
 */

class OC_FileProxy_Quota extends OC_FileProxy{
	static $rootView;
	private $userQuota=array();

	/**
	 * get the quota for the user
	 * @param user
	 * @return int
	 */
	private function getQuota($user) {
		if(in_array($user, $this->userQuota)) {
			return $this->userQuota[$user];
		}
		$userQuota=OC_Preferences::getValue($user, 'files', 'quota', 'default');
		if($userQuota=='default') {
			$userQuota=OC_AppConfig::getValue('files', 'default_quota', 'none');
		}
		if($userQuota=='none') {
			$this->userQuota[$user]=-1;
		}else{
			$this->userQuota[$user]=OC_Helper::computerFileSize($userQuota);
		}
		return $this->userQuota[$user];

	}

	/**
	 * get the free space in the path's owner home folder
	 * @param path
	 * @return int
	 */
	private function getFreeSpace($path) {
		/**
		 * @var \OC\Files\Storage\Storage $storage
		 * @var string $internalPath
		 */
		list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($path);
		$owner = $storage->getOwner($internalPath);
		if (!$owner) {
			return -1;
		}

		$totalSpace = $this->getQuota($owner);
		if($totalSpace == -1) {
			return -1;
		}

		$view = new \OC\Files\View("/".$owner."/files");

		$rootInfo = $view->getFileInfo('/');
		$usedSpace = isset($rootInfo['size'])?$rootInfo['size']:0;
		return $totalSpace - $usedSpace;
	}

	public function postFree_space($path, $space) {
		$free=$this->getFreeSpace($path);
		if($free==-1) {
			return $space;
		}
		if ($space < 0){
			return $free;
		}
		return min($free, $space);
	}

	public function preFile_put_contents($path, $data) {
		if (is_resource($data)) {
			$data = '';//TODO: find a way to get the length of the stream without emptying it
		}
		return (strlen($data)<$this->getFreeSpace($path) or $this->getFreeSpace($path)==-1);
	}

	public function preCopy($path1, $path2) {
		if(!self::$rootView) {
			self::$rootView = new \OC\Files\View('');
		}
		return (self::$rootView->filesize($path1)<$this->getFreeSpace($path2) or $this->getFreeSpace($path2)==-1);
	}

	public function preFromTmpFile($tmpfile, $path) {
		return (filesize($tmpfile)<$this->getFreeSpace($path) or $this->getFreeSpace($path)==-1);
	}

	public function preFromUploadedFile($tmpfile, $path) {
		return (filesize($tmpfile)<$this->getFreeSpace($path) or $this->getFreeSpace($path)==-1);
	}
}
