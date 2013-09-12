<?php

namespace OCA\files\lib;

class Helper
{
	public static function buildFileStorageStatistics($dir) {
		$l = new \OC_L10N('files');
		$maxUploadFilesize = \OCP\Util::maxUploadFilesize($dir);
		$maxHumanFilesize = \OCP\Util::humanFileSize($maxUploadFilesize);
		$maxHumanFilesize = $l->t('Upload') . ' max. ' . $maxHumanFilesize;

		// information about storage capacities
		$storageInfo = \OC_Helper::getStorageInfo($dir);

		return array('uploadMaxFilesize' => $maxUploadFilesize,
					 'maxHumanFilesize'  => $maxHumanFilesize,
					 'usedSpacePercent'  => (int)$storageInfo['relative']);
	}

	public static function determineIcon($file) {
		if($file['type'] === 'dir') {
			$dir = $file['directory'];
			$absPath = \OC\Files\Filesystem::getView()->getAbsolutePath($dir.'/'.$file['name']);
			$mount = \OC\Files\Filesystem::getMountManager()->find($absPath);
			if (!is_null($mount)) {
				$sid = $mount->getStorageId();
				if (!is_null($sid)) {
					$sid = explode(':', $sid);
					if ($sid[0] === 'shared') {
						return \OC_Helper::mimetypeIcon('dir-shared');
					}
					if ($sid[0] !== 'local') {
						return \OC_Helper::mimetypeIcon('dir-external');
					}
				}
			}
			return \OC_Helper::mimetypeIcon('dir');
		}

		if($file['isPreviewAvailable']) {
			$relativePath = substr($file['path'], 6);
			return \OC_Helper::previewIcon($relativePath);
		}
		return \OC_Helper::mimetypeIcon($file['mimetype']);
	}


}
