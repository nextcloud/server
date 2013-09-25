<?php

namespace OCA\Files_Trashbin;

class Helper
{
	/**
	 * Retrieves the contents of a trash bin directory.
	 * @param string $dir path to the directory inside the trashbin
	 * or empty to retrieve the root of the trashbin
	 * @return array of files
	 */
	public static function getTrashFiles($dir){
		$result = array();
		$user = \OCP\User::getUser();

		if ($dir && $dir !== '/') {
			$view = new \OC_Filesystemview('/'.$user.'/files_trashbin/files');
			$dirContent = $view->opendir($dir);
			if ($dirContent === false){
				return null;
			}
			if(is_resource($dirContent)){
				while(($entryName = readdir($dirContent)) !== false) {
					if (!\OC\Files\Filesystem::isIgnoredDir($entryName)) {
						$pos = strpos($dir.'/', '/', 1);
						$tmp = substr($dir, 0, $pos);
						$pos = strrpos($tmp, '.d');
						$timestamp = substr($tmp, $pos+2);
						$result[] = array(
								'id' => $entryName,
								'timestamp' => $timestamp,
								'mime' =>  $view->getMimeType($dir.'/'.$entryName),
								'type' => $view->is_dir($dir.'/'.$entryName) ? 'dir' : 'file',
								'location' => $dir,
								);
					}
				}
				closedir($dirContent);
			}
		} else {
			$query = \OC_DB::prepare('SELECT `id`,`location`,`timestamp`,`type`,`mime` FROM `*PREFIX*files_trash` WHERE `user` = ?');
			$result = $query->execute(array($user))->fetchAll();
		}

		$files = array();
		foreach ($result as $r) {
			$i = array();
			$i['name'] = $r['id'];
			$i['date'] = \OCP\Util::formatDate($r['timestamp']);
			$i['timestamp'] = $r['timestamp'];
			$i['mimetype'] = $r['mime'];
			$i['type'] = $r['type'];
			if ($i['type'] === 'file') {
				$fileinfo = pathinfo($r['id']);
				$i['basename'] = $fileinfo['filename'];
				$i['extension'] = isset($fileinfo['extension']) ? ('.'.$fileinfo['extension']) : '';
			}
			$i['directory'] = $r['location'];
			if ($i['directory'] === '/') {
				$i['directory'] = '';
			}
			$i['permissions'] = \OCP\PERMISSION_READ;
			$i['isPreviewAvailable'] = \OC::$server->getPreviewManager()->isMimeSupported($r['mime']);
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
		// Make breadcrumb
		$pathtohere = '';
		$breadcrumb = array();
		foreach (explode('/', $dir) as $i) {
			if ($i !== '') {
				if ( preg_match('/^(.+)\.d[0-9]+$/', $i, $match) ) {
					$name = $match[1];
				} else {
					$name = $i;
				}
				$pathtohere .= '/' . $i;
				$breadcrumb[] = array('dir' => $pathtohere, 'name' => $name);
			}
		}
		return $breadcrumb;
	}
}
