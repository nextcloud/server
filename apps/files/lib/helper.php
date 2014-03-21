<?php

namespace OCA\Files;

class Helper
{
	public static function buildFileStorageStatistics($dir) {
		// information about storage capacities
		$storageInfo = \OC_Helper::getStorageInfo($dir);

		$l = new \OC_L10N('files');
		$maxUploadFilesize = \OCP\Util::maxUploadFilesize($dir, $storageInfo['free']);
		$maxHumanFilesize = \OCP\Util::humanFileSize($maxUploadFilesize);
		$maxHumanFilesize = $l->t('Upload') . ' max. ' . $maxHumanFilesize;

		return array('uploadMaxFilesize' => $maxUploadFilesize,
					 'maxHumanFilesize'  => $maxHumanFilesize,
					 'freeSpace' => $storageInfo['free'],
					 'usedSpacePercent'  => (int)$storageInfo['relative']);
	}

	public static function determineIcon($file) {
		if($file['type'] === 'dir') {
			$dir = $file['directory'];
			$icon = \OC_Helper::mimetypeIcon('dir');
			$absPath = \OC\Files\Filesystem::getView()->getAbsolutePath($dir.'/'.$file['name']);
			$mount = \OC\Files\Filesystem::getMountManager()->find($absPath);
			if (!is_null($mount)) {
				$sid = $mount->getStorageId();
				if (!is_null($sid)) {
					$sid = explode(':', $sid);
					if ($sid[0] === 'shared') {
						$icon = \OC_Helper::mimetypeIcon('dir-shared');
					}
					if ($sid[0] !== 'local' and $sid[0] !== 'home') {
						$icon = \OC_Helper::mimetypeIcon('dir-external');
					}
				}
			}
		}else{
			if($file['isPreviewAvailable']) {
				$pathForPreview = $file['directory'] . '/' . $file['name'];
				return \OC_Helper::previewIcon($pathForPreview) . '&c=' . $file['etag'];
			}
			$icon = \OC_Helper::mimetypeIcon($file['mimetype']);
		}

		return substr($icon, 0, -3) . 'svg';
	}

	/**
	 * Comparator function to sort files alphabetically and have
	 * the directories appear first
	 *
	 * @param \OCP\Files\FileInfo $a file
	 * @param \OCP\Files\FileInfo $b file
	 * @return int -1 if $a must come before $b, 1 otherwise
	 */
	public static function fileCmp($a, $b) {
		$aType = $a->getType();
		$bType = $b->getType();
		if ($aType === 'dir' and $bType !== 'dir') {
			return -1;
		} elseif ($aType !== 'dir' and $bType === 'dir') {
			return 1;
		} else {
			return strnatcasecmp($a->getName(), $b->getName());
		}
	}

	/**
	 * Retrieves the contents of the given directory and
	 * returns it as a sorted array.
	 * @param string $dir path to the directory
	 * @return array of files
	 */
	public static function getFiles($dir) {
		$content = \OC\Files\Filesystem::getDirectoryContent($dir);
		$files = array();

		foreach ($content as $i) {
			$i['date'] = \OCP\Util::formatDate($i['mtime']);
			if ($i['type'] === 'file') {
				$fileinfo = pathinfo($i['name']);
				$i['basename'] = $fileinfo['filename'];
				if (!empty($fileinfo['extension'])) {
					$i['extension'] = '.' . $fileinfo['extension'];
				} else {
					$i['extension'] = '';
				}
			}
			$i['directory'] = $dir;
			$i['isPreviewAvailable'] = \OC::$server->getPreviewManager()->isMimeSupported($i['mimetype']);
			$i['icon'] = \OCA\Files\Helper::determineIcon($i);
			$files[] = $i;
		}

		usort($files, array('\OCA\Files\Helper', 'fileCmp'));

		return $files;
	}

	/**
	 * Splits the given path into a breadcrumb structure.
	 * @param string $dir path to process
	 * @return array where each entry is a hash of the absolute
	 * directory path and its name
	 */
	public static function makeBreadcrumb($dir){
		$breadcrumb = array();
		$pathtohere = '';
		foreach (explode('/', $dir) as $i) {
			if ($i !== '') {
				$pathtohere .= '/' . $i;
				$breadcrumb[] = array('dir' => $pathtohere, 'name' => $i);
			}
		}
		return $breadcrumb;
	}
}
