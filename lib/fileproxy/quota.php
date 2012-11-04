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
 * user quota managment
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
			$this->userQuota[$user]=0;
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
		$storage=OC_Filesystem::getStorage($path);
		$owner=$storage->getOwner($path);

		$totalSpace=$this->getQuota($owner);
		if($totalSpace==0) {
			return 0;
		}

		$rootInfo=OC_FileCache::get('', "/".$owner."/files");
		// TODO Remove after merge of share_api
		if (OC_FileCache::inCache('/Shared', "/".$owner."/files")) {
			$sharedInfo=OC_FileCache::get('/Shared', "/".$owner."/files");
		} else {
			$sharedInfo = null;
		}
		$usedSpace=isset($rootInfo['size'])?$rootInfo['size']:0;
		$usedSpace=isset($sharedInfo['size'])?$usedSpace-$sharedInfo['size']:$usedSpace;
		return $totalSpace-$usedSpace;
	}
	
	public function postFree_space($path, $space) {
		$free=$this->getFreeSpace($path);
		if($free==0) {
			return $space;
		}
		return min($free, $space);
	}

	public function preFile_put_contents($path, $data) {
		if (is_resource($data)) {
			$data = '';//TODO: find a way to get the length of the stream without emptying it
		}
		return (strlen($data)<$this->getFreeSpace($path) or $this->getFreeSpace($path)==0);
	}

	public function preCopy($path1, $path2) {
		if(!self::$rootView) {
			self::$rootView = new OC_FilesystemView('');
		}
		return (self::$rootView->filesize($path1)<$this->getFreeSpace($path2) or $this->getFreeSpace($path2)==0);
	}

	public function preFromTmpFile($tmpfile, $path) {
		return (filesize($tmpfile)<$this->getFreeSpace($path) or $this->getFreeSpace($path)==0);
	}

	public function preFromUploadedFile($tmpfile, $path) {
		return (filesize($tmpfile)<$this->getFreeSpace($path) or $this->getFreeSpace($path)==0);
	}
}
