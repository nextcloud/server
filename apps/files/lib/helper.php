<?php

namespace OCA\Files;

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
					 'usedSpacePercent'  => (int)$storageInfo['relative'],
					 'freeSpace'		 => (int)$storageInfo['free']);
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
					if ($sid[0] !== 'local' and $sid[0] !== 'home') {
						return \OC_Helper::mimetypeIcon('dir-external');
					}
				}
			}
			return \OC_Helper::mimetypeIcon('dir');
		}

		if($file['isPreviewAvailable']) {
			$pathForPreview = $file['directory'] . '/' . $file['name'];
			return \OC_Helper::previewIcon($pathForPreview) . '&c=' . $file['etag'];
		}
		return \OC_Helper::mimetypeIcon($file['mimetype']);
	}

	/**
	 * Comparator function to sort files alphabetically and have
	 * the directories appear first
	 * @param array $a file
	 * @param array $b file
	 * @return -1 if $a must come before $b, 1 otherwise
	 */
	public static function fileCmp($a, $b) {
		if ($a['type'] === 'dir' and $b['type'] !== 'dir') {
			return -1;
		} elseif ($a['type'] !== 'dir' and $b['type'] === 'dir') {
			return 1;
		} else {
			return strnatcasecmp($a['name'], $b['name']);
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

	/**
	 * Returns the numeric permissions for the given directory.
	 * @param string $dir directory without trailing slash
	 * @return numeric permissions
	 */
	public static function getDirPermissions($dir){
		$permissions = \OCP\PERMISSION_READ;
		if (\OC\Files\Filesystem::isCreatable($dir . '/')) {
			$permissions |= \OCP\PERMISSION_CREATE;
		}
		if (\OC\Files\Filesystem::isUpdatable($dir . '/')) {
			$permissions |= \OCP\PERMISSION_UPDATE;
		}
		if (\OC\Files\Filesystem::isDeletable($dir . '/')) {
			$permissions |= \OCP\PERMISSION_DELETE;
		}
		if (\OC\Files\Filesystem::isSharable($dir . '/')) {
			$permissions |= \OCP\PERMISSION_SHARE;
		}
		return $permissions;
	}
}
