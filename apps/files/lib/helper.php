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
		$storageInfo = \OC_Helper::getStorageInfo();

		return array('uploadMaxFilesize' => $maxUploadFilesize,
					 'maxHumanFilesize'  => $maxHumanFilesize,
					 'usedSpacePercent'  => (int)$storageInfo['relative']);
	}
}
