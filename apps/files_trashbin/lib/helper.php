<?php

namespace OCA\Files_Trashbin;

use OC\Files\FileInfo;

class Helper
{
	/**
	 * Retrieves the contents of a trash bin directory.
	 * @param string $dir path to the directory inside the trashbin
	 * or empty to retrieve the root of the trashbin
	 * @return \OCP\Files\FileInfo[]
	 */
	public static function getTrashFiles($dir){
		$result = array();
		$timestamp = null;
		$user = \OCP\User::getUser();

		$view = new \OC_Filesystemview('/' . $user . '/files_trashbin/files');

		if (ltrim($dir, '/') !== '' && !$view->is_dir($dir)) {
			throw new \Exception('Directory does not exists');
		}

		$dirContent = $view->opendir($dir);
		if ($dirContent === false) {
			return $result;
		}

		list($storage, $internalPath) = $view->resolvePath($dir);
		$absoluteDir = $view->getAbsolutePath($dir);

		if (is_resource($dirContent)) {
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
					$i = array(
						'name' => $id,
						'mtime' => $timestamp,
						'mimetype' => \OC_Helper::getFileNameMimeType($id),
						'type' => $view->is_dir($dir . '/' . $entryName) ? 'dir' : 'file',
						'directory' => ($dir === '/') ? '' : $dir,
					);
					$result[] = new FileInfo($absoluteDir . '/' . $i['name'], $storage, $internalPath . '/' . $i['name'], $i);
				}
			}
			closedir($dirContent);
		}

		usort($result, array('\OCA\Files\Helper', 'fileCmp'));

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
			$entry['permissions'] = \OCP\PERMISSION_READ;
			if (\OCP\App::isEnabled('files_encryption')) {
				$entry['isPreviewAvailable'] = false;
			}
			$files[] = $entry;
		}
		return $files;
	}
}
