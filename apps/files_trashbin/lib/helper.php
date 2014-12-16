<?php

namespace OCA\Files_Trashbin;

use OC\Files\FileInfo;

class Helper
{
	/**
	 * Retrieves the contents of a trash bin directory.
	 *
	 * @param string $dir path to the directory inside the trashbin
	 * or empty to retrieve the root of the trashbin
	 * @param string $user
	 * @param string $sortAttribute attribute to sort on or empty to disable sorting
	 * @param bool $sortDescending true for descending sort, false otherwise
	 * @return \OCP\Files\FileInfo[]
	 */
	public static function getTrashFiles($dir, $user, $sortAttribute = '', $sortDescending = false){
		$result = array();
		$timestamp = null;

		$view = new \OC\Files\View('/' . $user . '/files_trashbin/files');

		if (ltrim($dir, '/') !== '' && !$view->is_dir($dir)) {
			throw new \Exception('Directory does not exists');
		}

		$dirContent = $view->opendir($dir);
		if ($dirContent === false) {
			return $result;
		}

		$mount = $view->getMount($dir);
		$storage = $mount->getStorage();
		$absoluteDir = $view->getAbsolutePath($dir);
		$internalPath = $mount->getInternalPath($absoluteDir);

		if (is_resource($dirContent)) {
			$originalLocations = \OCA\Files_Trashbin\Trashbin::getLocations($user);
			while (($entryName = readdir($dirContent)) !== false) {
				if (!\OC\Files\Filesystem::isIgnoredDir($entryName)) {
					$id = $entryName;
					if ($dir === '' || $dir === '/') {
						$pathparts = pathinfo($entryName);
						$timestamp = substr($pathparts['extension'], 1);
						$id = $pathparts['filename'];
					} else if ($timestamp === null) {
						// for subfolders we need to calculate the timestamp only once
						$parts = explode('/', ltrim($dir, '/'));
						$timestamp = substr(pathinfo($parts[0], PATHINFO_EXTENSION), 1);
					}
					$originalPath = '';
					if (isset($originalLocations[$id][$timestamp])) {
						$originalPath = $originalLocations[$id][$timestamp];
						if (substr($originalPath, -1) === '/') {
							$originalPath = substr($originalPath, 0, -1);
						}
					}
					$i = array(
						'name' => $id,
						'mtime' => $timestamp,
						'mimetype' => \OC_Helper::getFileNameMimeType($id),
						'type' => $view->is_dir($dir . '/' . $entryName) ? 'dir' : 'file',
						'directory' => ($dir === '/') ? '' : $dir,
					);
					if ($originalPath) {
						$i['extraData'] = $originalPath.'/'.$id;
					}
					$result[] = new FileInfo($absoluteDir . '/' . $i['name'], $storage, $internalPath . '/' . $i['name'], $i, $mount);
				}
			}
			closedir($dirContent);
		}

		if ($sortAttribute !== '') {
			return \OCA\Files\Helper::sortFiles($result, $sortAttribute, $sortDescending);
		}
		return $result;
	}

	/**
	 * Format file infos for JSON
	 * @param \OCP\Files\FileInfo[] $fileInfos file infos
	 */
	public static function formatFileInfos($fileInfos) {
		$files = array();
		$id = 0;
		foreach ($fileInfos as $i) {
			$entry = \OCA\Files\Helper::formatFileInfo($i);
			$entry['id'] = $id++;
			$entry['etag'] = $entry['mtime']; // add fake etag, it is only needed to identify the preview image
			$entry['permissions'] = \OCP\Constants::PERMISSION_READ;
			if (\OCP\App::isEnabled('files_encryption')) {
				$entry['isPreviewAvailable'] = false;
			}
			$files[] = $entry;
		}
		return $files;
	}
}
