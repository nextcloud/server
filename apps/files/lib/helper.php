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

	/**
	 * Determine icon for a given file
	 *
	 * @param \OC\Files\FileInfo $file file info
	 * @return string icon URL
	 */
	public static function determineIcon($file) {
		if($file['type'] === 'dir') {
			$dir = $file['directory'];
			$icon = \OC_Helper::mimetypeIcon('dir');
			$absPath = $file->getPath();
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
			$icon = \OC_Helper::mimetypeIcon($file->getMimetype());
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
	 * Formats the file info to be returned as JSON to the client.
	 *
	 * @param \OCP\Files\FileInfo file info
	 * @return array formatted file info
	 */
	public static function formatFileInfo($i) {
		$entry = array();

		$entry['id'] = $i['fileid'];
		$entry['date'] = \OCP\Util::formatDate($i['mtime']);
		$entry['mtime'] = $i['mtime'] * 1000;
		// only pick out the needed attributes
		$entry['icon'] = \OCA\Files\Helper::determineIcon($i);
		if (\OC::$server->getPreviewManager()->isMimeSupported($i['mimetype'])) {
			$entry['isPreviewAvailable'] = true;
		}
		$entry['name'] = $i['name'];
		$entry['permissions'] = $i['permissions'];
		$entry['mimetype'] = $i['mimetype'];
		$entry['size'] = $i['size'];
		$entry['type'] = $i['type'];
		$entry['etag'] = $i['etag'];
		if (isset($i['displayname_owner'])) {
			$entry['shareOwner'] = $i['displayname_owner'];
		}
		return $entry;
	}

	/**
	 * Format file info for JSON
	 * @param \OCP\Files\FileInfo[] $fileInfos file infos
	 */
	public static function formatFileInfos($fileInfos) {
		$files = array();
		foreach ($fileInfos as $i) {
			$files[] = self::formatFileInfo($i);
		}

		return $files;
	}

	/**
	 * Retrieves the contents of the given directory and
	 * returns it as a sorted array of FileInfo.
	 *
	 * @param string $dir path to the directory
	 * @return \OCP\Files\FileInfo[] files
	 */
	public static function getFiles($dir) {
		$content = \OC\Files\Filesystem::getDirectoryContent($dir);

		usort($content, array('\OCA\Files\Helper', 'fileCmp'));
		return $content;
	}
}
