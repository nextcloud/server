<?php
/**
 * ownCloud - trash bin
 *
 * @author Bjoern Schiessle
 * @copyright 2013 Bjoern Schiessle schiessle@owncloud.com
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

namespace OCA\Files_Trashbin;

class Trashbin {
	// how long do we keep files in the trash bin if no other value is defined in the config file (unit: days)
	const DEFAULT_RETENTION_OBLIGATION=180;

	// unit: percentage; 50% of available disk space/quota
	const DEFAULTMAXSIZE=50;

	/**
	 * move file to the trash bin
	 *
	 * @param $file_path path to the deleted file/directory relative to the files root directory
	 */
	public static function move2trash($file_path) {
		$user = \OCP\User::getUser();
		$view = new \OC\Files\View('/'. $user);
		if (!$view->is_dir('files_trashbin')) {
			$view->mkdir('files_trashbin');
			$view->mkdir("files_trashbin/files");
			$view->mkdir("files_trashbin/versions");
			$view->mkdir("files_trashbin/keyfiles");
		}

		$path_parts = pathinfo($file_path);

		$deleted = $path_parts['basename'];
		$location = $path_parts['dirname'];
		$timestamp = time();
		$mime = $view->getMimeType('files'.$file_path);

		if ( $view->is_dir('files'.$file_path) ) {
			$type = 'dir';
		} else {
			$type = 'file';
		}
		
		$trashbinSize = self::getTrashbinSize($user);
		if ( $trashbinSize === false || $trashbinSize < 0 ) {
			$trashbinSize = self::calculateSize(new \OC\Files\View('/'. $user.'/files_trashbin'));
		}
		
		$sizeOfAddedFiles = self::copy_recursive($file_path, 'files_trashbin/files/'.$deleted.'.d'.$timestamp, $view);
		
		if ( $view->file_exists('files_trashbin/files/'.$deleted.'.d'.$timestamp) ) {
			$trashbinSize += $sizeOfAddedFiles;
			$query = \OC_DB::prepare("INSERT INTO `*PREFIX*files_trash` (`id`,`timestamp`,`location`,`type`,`mime`,`user`) VALUES (?,?,?,?,?,?)");
			$result = $query->execute(array($deleted, $timestamp, $location, $type, $mime, $user));
			if ( !$result ) { // if file couldn't be added to the database than also don't store it in the trash bin.
				$view->deleteAll('files_trashbin/files/'.$deleted.'.d'.$timestamp);
				\OC_Log::write('files_trashbin', 'trash bin database couldn\'t be updated', \OC_log::ERROR);
				return;
			}
			\OCP\Util::emitHook('\OCA\Files_Trashbin\Trashbin', 'post_moveToTrash',
					array('filePath' => \OC\Files\Filesystem::normalizePath($file_path),
							'trashPath' => \OC\Files\Filesystem::normalizePath($deleted.'.d'.$timestamp)));
			
			// Take care of file versions
			if ( \OCP\App::isEnabled('files_versions') ) {
				if ( $view->is_dir('files_versions/'.$file_path) ) {
					$trashbinSize += self::calculateSize(new \OC\Files\View('/'. $user.'/files_versions/'.$file_path));
					$view->rename('files_versions/'.$file_path, 'files_trashbin/versions'. $deleted.'.d'.$timestamp);
				} else if ( $versions = \OCA\Files_Versions\Storage::getVersions($user, $file_path) ) {
					foreach ($versions as $v) {
						$trashbinSize += $view->filesize('files_versions'.$v['path'].'.v'.$v['version']);
						$view->rename('files_versions'.$v['path'].'.v'.$v['version'], 'files_trashbin/versions/'. $deleted.'.v'.$v['version'].'.d'.$timestamp);
					}
				}
			}
			
			// Take care of encryption keys
			$keyfile = \OC\Files\Filesystem::normalizePath('files_encryption/keyfiles/'.$file_path);
			if ( \OCP\App::isEnabled('files_encryption') && $view->file_exists($keyfile.'.key') ) {
				if ( $view->is_dir('files'.$file_path) ) {
					$trashbinSize += self::calculateSize(new \OC\Files\View('/'.$user.'/'.$keyfile));
					$view->rename($keyfile, 'files_trashbin/keyfiles/'. $deleted.'.d'.$timestamp);
				} else {
					$trashbinSize += $view->filesize($keyfile.'.key');
					$view->rename($keyfile.'.key', 'files_trashbin/keyfiles/'. $deleted.'.key.d'.$timestamp);
				}
			}
		} else {
			\OC_Log::write('files_trashbin', 'Couldn\'t move '.$file_path.' to the trash bin', \OC_log::ERROR);
		}

		$trashbinSize -= self::expire($trashbinSize);
		
		self::setTrashbinSize($user, $trashbinSize);

	}


	/**
	 * restore files from trash bin
	 * @param $file path to the deleted file
	 * @param $filename name of the file
	 * @param $timestamp time when the file was deleted
	 */
	public static function restore($file, $filename, $timestamp) {
		$user = \OCP\User::getUser();
		$view = new \OC\Files\View('/'.$user);
		
		$trashbinSize = self::getTrashbinSize($user);
		if ( $trashbinSize === false || $trashbinSize < 0 ) {
			$trashbinSize = self::calculateSize(new \OC\Files\View('/'. $user.'/files_trashbin'));
		}
		if ( $timestamp ) {
			$query = \OC_DB::prepare('SELECT `location`,`type` FROM `*PREFIX*files_trash`'
				.' WHERE `user`=? AND `id`=? AND `timestamp`=?');
			$result = $query->execute(array($user,$filename,$timestamp))->fetchAll();
			if ( count($result) != 1 ) {
				\OC_Log::write('files_trashbin', 'trash bin database inconsistent!', \OC_Log::ERROR);
				return false;
			}

			// if location no longer exists, restore file in the root directory
			$location = $result[0]['location'];
			if ( $result[0]['location'] != '/' &&
				(!$view->is_dir('files'.$result[0]['location']) ||
				 !$view->isUpdatable('files'.$result[0]['location'])) ) {
				$location = '';
			}
		} else {
			$path_parts = pathinfo($file);
			$result[] = array(
					'location' => $path_parts['dirname'],
					'type' => $view->is_dir('/files_trashbin/files/'.$file) ? 'dir' : 'files',
					);
			$location = '';
		}
		
		$source = \OC\Files\Filesystem::normalizePath('files_trashbin/files/'.$file);
		$target = \OC\Files\Filesystem::normalizePath('files/'.$location.'/'.$filename);

		// we need a  extension in case a file/dir with the same name already exists
		$ext = self::getUniqueExtension($location, $filename, $view);
		$mtime = $view->filemtime($source);
		if( $view->rename($source, $target.$ext) ) {
			$view->touch($target.$ext, $mtime);
			\OCP\Util::emitHook('\OCA\Files_Trashbin\Trashbin', 'post_restore', 
					array('filePath' => \OC\Files\Filesystem::normalizePath('/'.$location.'/'.$filename.$ext),
							'trashPath' => \OC\Files\Filesystem::normalizePath($file)));
			if ($view->is_dir($target.$ext)) {
				$trashbinSize -= self::calculateSize(new \OC\Files\View('/'.$user.'/'.$target.$ext));
			} else {
				$trashbinSize -= $view->filesize($target.$ext);
			}
			// if versioning app is enabled, copy versions from the trash bin back to the original location
			if ( \OCP\App::isEnabled('files_versions') ) {
				if ($timestamp ) {
					$versionedFile = $filename;
				} else {
					$versionedFile = $file;
				}
				if ( $result[0]['type'] === 'dir' ) {
					$trashbinSize -= self::calculateSize(new \OC\Files\View('/'.$user.'/'.'files_trashbin/versions/'. $file));
					$view->rename(\OC\Files\Filesystem::normalizePath('files_trashbin/versions/'. $file), \OC\Files\Filesystem::normalizePath('files_versions/'.$location.'/'.$filename.$ext));
				} else if ( $versions = self::getVersionsFromTrash($versionedFile, $timestamp) ) {
					foreach ($versions as $v) {
						if ($timestamp ) {
							$trashbinSize -= $view->filesize('files_trashbin/versions/'.$versionedFile.'.v'.$v.'.d'.$timestamp);
							$view->rename('files_trashbin/versions/'.$versionedFile.'.v'.$v.'.d'.$timestamp, 'files_versions/'.$location.'/'.$filename.$ext.'.v'.$v);
						} else {
							$trashbinSize -= $view->filesize('files_trashbin/versions/'.$versionedFile.'.v'.$v);
							$view->rename('files_trashbin/versions/'.$versionedFile.'.v'.$v, 'files_versions/'.$location.'/'.$filename.$ext.'.v'.$v);
						}
					}
				}
			}
			
			// Take care of encryption keys TODO! Get '.key' in file between file name and delete date (also for permanent delete!)
			$parts = pathinfo($file);
			if ( $result[0]['type'] === 'dir' ) {
				$keyfile = \OC\Files\Filesystem::normalizePath('files_trashbin/keyfiles/'.$parts['dirname'].'/'.$filename);
			} else {
				$keyfile = \OC\Files\Filesystem::normalizePath('files_trashbin/keyfiles/'.$parts['dirname'].'/'.$filename.'.key');
			}
			if ($timestamp) {
				$keyfile .= '.d'.$timestamp;
			}
			if ( \OCP\App::isEnabled('files_encryption') && $view->file_exists($keyfile) ) {
				if ( $result[0]['type'] === 'dir' ) {
					$trashbinSize -= self::calculateSize(new \OC\Files\View('/'.$user.'/'.$keyfile));
					$view->rename($keyfile, 'files_encryption/keyfiles/'. $location.'/'.$filename);
				} else {
					$trashbinSize -= $view->filesize($keyfile);
					$view->rename($keyfile, 'files_encryption/keyfiles/'. $location.'/'.$filename.'.key');
				}
			}
			
			if ( $timestamp ) {
				$query = \OC_DB::prepare('DELETE FROM `*PREFIX*files_trash` WHERE `user`=? AND `id`=? AND `timestamp`=?');
				$query->execute(array($user,$filename,$timestamp));
			}

			self::setTrashbinSize($user, $trashbinSize);
			
			return true;
		} else {
			\OC_Log::write('files_trashbin', 'Couldn\'t restore file from trash bin, '.$filename, \OC_log::ERROR);
		}

		return false;
	}

	/**
	 * delete file from trash bin permanently
	 * @param $filename path to the file
	 * @param $timestamp of deletion time
	 * @return size of deleted files
	 */
	public static function delete($filename, $timestamp=null) {
		$user = \OCP\User::getUser();
		$view = new \OC\Files\View('/'.$user);
		$size = 0;
	
		$trashbinSize = self::getTrashbinSize($user);
		if ( $trashbinSize === false || $trashbinSize < 0 ) {
			$trashbinSize = self::calculateSize(new \OC\Files\View('/'. $user.'/files_trashbin'));
		}

		if ( $timestamp ) {
			$query = \OC_DB::prepare('DELETE FROM `*PREFIX*files_trash` WHERE `user`=? AND `id`=? AND `timestamp`=?');
			$query->execute(array($user,$filename,$timestamp));
			$file = $filename.'.d'.$timestamp;
		} else {
			$file = $filename;
		}

		if ( \OCP\App::isEnabled('files_versions') ) {
			if ($view->is_dir('files_trashbin/versions/'.$file)) {
				$size += self::calculateSize(new \OC\Files\view('/'.$user.'/files_trashbin/versions/'.$file));
				$view->unlink('files_trashbin/versions/'.$file);
			} else if ( $versions = self::getVersionsFromTrash($filename, $timestamp) ) {
				foreach ($versions as $v) {
					if ($timestamp ) {
						$size += $view->filesize('/files_trashbin/versions/'.$filename.'.v'.$v.'.d'.$timestamp);
						$view->unlink('/files_trashbin/versions/'.$filename.'.v'.$v.'.d'.$timestamp);
					} else {
						$size += $view->filesize('/files_trashbin/versions/'.$filename.'.v'.$v);
						$view->unlink('/files_trashbin/versions/'.$filename.'.v'.$v);
					}
				}
			}
		}
		
		// Take care of encryption keys
		$parts = pathinfo($file);
		if ( $view->is_dir('/files_trashbin/files/'.$file) ) {
			$keyfile = \OC\Files\Filesystem::normalizePath('files_trashbin/keyfiles/'.$filename);
		} else {
			$keyfile = \OC\Files\Filesystem::normalizePath('files_trashbin/keyfiles/'.$filename.'.key');
		}
		if ($timestamp) {
			$keyfile .= '.d'.$timestamp;
		}
		if ( \OCP\App::isEnabled('files_encryption') && $view->file_exists($keyfile) ) {
			if ( $view->is_dir($keyfile) ) {
				$size += self::calculateSize(new \OC\Files\View('/'.$user.'/'.$keyfile));
			} else {
				$size += $view->filesize($keyfile);
			}
			$view->unlink($keyfile);
		}
	
		if ($view->is_dir('/files_trashbin/files/'.$file)) {
			$size += self::calculateSize(new \OC\Files\View('/'.$user.'/files_trashbin/files/'.$file));
		} else {
			$size += $view->filesize('/files_trashbin/files/'.$file);
		}
		$view->unlink('/files_trashbin/files/'.$file);
		$trashbinSize -= $size;
		self::setTrashbinSize($user, $trashbinSize);
		
		return $size;
	}

	/**
	 * check to see whether a file exists in trashbin
	 * @param $filename path to the file
	 * @param $timestamp of deletion time
	 * @return true if file exists, otherwise false
	 */
	public static function file_exists($filename, $timestamp=null) {
		$user = \OCP\User::getUser();
		$view = new \OC\Files\View('/'.$user);

		if ($timestamp) {
			$filename = $filename.'.d'.$timestamp;
		} else {
			$filename = $filename;
		}

		$target = \OC\Files\Filesystem::normalizePath('files_trashbin/files/'.$filename);
		return $view->file_exists($target);
	}

	/**
	 * @brief deletes used space for trash bin in db if user was deleted
	 *
	 * @param type $uid id of deleted user
	 * @return result of db delete operation
	 */
	public static function deleteUser($uid) {
		$query = \OC_DB::prepare('DELETE FROM `*PREFIX*files_trash` WHERE `user`=?');
		$result = $query->execute(array($uid));
		if ($result) {
			$query = \OC_DB::prepare('DELETE FROM `*PREFIX*files_trashsize` WHERE `user`=?');
			return $query->execute(array($uid));
		}
		return false;
	}

	/**
	 * calculate remaining free space for trash bin
	 *
	 * @param $trashbinSize current size of the trash bin
	 * @return available free space for trash bin
	 */
	private static function calculateFreeSpace($trashbinSize) {
		$softQuota = true;
		$user = \OCP\User::getUser();
		$quota = \OC_Preferences::getValue($user, 'files', 'quota');
		$view = new \OC\Files\View('/'.$user);
		if ( $quota === null || $quota === 'default') {
			$quota = \OC_Appconfig::getValue('files', 'default_quota');
		}
		if ( $quota === null || $quota === 'none' ) {
			$quota = \OC\Files\Filesystem::free_space('/');
			$softQuota = false;
		} else {
			$quota = \OCP\Util::computerFileSize($quota);
		}

		// calculate available space for trash bin
		// subtract size of files and current trash bin size from quota
		if ($softQuota) {
			$rootInfo = $view->getFileInfo('/files/');
			$free = $quota-$rootInfo['size']; // remaining free space for user
			if ( $free > 0 ) {
				$availableSpace = ($free * self::DEFAULTMAXSIZE / 100) - $trashbinSize; // how much space can be used for versions
			} else {
				$availableSpace = $free-$trashbinSize;
			}
		} else {
			$availableSpace = $quota;
		}

		return $availableSpace;
	}

	/**
	 * clean up the trash bin
	 * @param current size of the trash bin
	 */
	private static function expire($trashbinSize) {

		$user = \OCP\User::getUser();
		$view = new \OC\Files\View('/'.$user);
		$availableSpace = self::calculateFreeSpace($trashbinSize);
		$size = 0;

		$query = \OC_DB::prepare('SELECT `location`,`type`,`id`,`timestamp` FROM `*PREFIX*files_trash` WHERE `user`=?');
		$result = $query->execute(array($user))->fetchAll();

		$retention_obligation = \OC_Config::getValue('trashbin_retention_obligation',
			self::DEFAULT_RETENTION_OBLIGATION);

		$limit = time() - ($retention_obligation * 86400);

		foreach ( $result as $r ) {
			$timestamp = $r['timestamp'];
			$filename = $r['id'];
			if ( $r['timestamp'] < $limit ) {
				$size += self::delete($filename, $timestamp);
				\OC_Log::write('files_trashbin', 'remove "'.$filename.'" fom trash bin because it is older than '.$retention_obligation, \OC_log::INFO);
			}
		}
		$availableSpace = $availableSpace + $size;
		// if size limit for trash bin reached, delete oldest files in trash bin
		if ($availableSpace < 0) {
			$query = \OC_DB::prepare('SELECT `location`,`type`,`id`,`timestamp` FROM `*PREFIX*files_trash`'
				.' WHERE `user`=? ORDER BY `timestamp` ASC');
			$result = $query->execute(array($user))->fetchAll();
			$length = count($result);
			$i = 0;
			while ( $i < $length &&   $availableSpace < 0 ) {
				$tmp = self::delete($result[$i]['id'], $result[$i]['timestamp']);
				\OC_Log::write('files_trashbin', 'remove "'.$result[$i]['id'].'" ('.$tmp.'B) to meet the limit of trash bin size (50% of available quota)', \OC_log::INFO);
				$availableSpace += $tmp;
				$size += $tmp;
				$i++;
			}


		}

		return $size;
	}

	/**
	 * recursive copy to copy a whole directory
	 *
	 * @param $source source path, relative to the users files directory
	 * @param $destination destination path relative to the users root directoy
	 * @param $view file view for the users root directory
	 */
	private static function copy_recursive( $source, $destination, $view ) {
		$size = 0;
		if ( $view->is_dir( 'files'.$source ) ) {
			$view->mkdir( $destination );
			$view->touch($destination,  $view->filemtime('files'.$source));
			foreach ( \OC_Files::getDirectoryContent($source) as $i ) {
				$pathDir = $source.'/'.$i['name'];
				if ( $view->is_dir('files'.$pathDir) ) {
					$size += self::copy_recursive($pathDir, $destination.'/'.$i['name'], $view);
				} else {
					$size += $view->filesize('files'.$pathDir);
					$view->copy( 'files'.$pathDir, $destination . '/' . $i['name'] );
					$view->touch($destination . '/' . $i['name'], $view->filemtime('files'.$pathDir));
				}
			}
		} else {
			$size += $view->filesize('files'.$source);
			$view->copy( 'files'.$source, $destination );
			$view->touch($destination, $view->filemtime('files'.$source));
		}
		return $size;
	}

	/**
	 * find all versions which belong to the file we want to restore
	 * @param $filename name of the file which should be restored
	 * @param $timestamp timestamp when the file was deleted
	 */
	private static function getVersionsFromTrash($filename, $timestamp) {
		$view = new \OC\Files\View('/'.\OCP\User::getUser().'/files_trashbin/versions');
		$versionsName = $view->getLocalFile($filename);
		$versions = array();
		if ($timestamp ) {
			// fetch for old versions
			$matches = glob( $versionsName.'.v*.d'.$timestamp );
			$offset = -strlen($timestamp)-2;
		} else {
			$matches = glob( $versionsName.'.v*' );
		}

		foreach( $matches as $ma ) {
			if ( $timestamp ) {
				$parts = explode( '.v', substr($ma, 0, $offset) );
				$versions[] = ( end( $parts ) );
			} else {
				$parts = explode( '.v', $ma );
				$versions[] = ( end( $parts ) );
			}
		}
		return $versions;
	}

	/**
	 * find unique extension for restored file if a file with the same name already exists
	 * @param $location where the file should be restored
	 * @param $filename name of the file
	 * @param $view filesystem view relative to users root directory
	 * @return string with unique extension
	 */
	private static function getUniqueExtension($location, $filename, $view) {
		$ext = '';
		if ( $view->file_exists('files'.$location.'/'.$filename) ) {
			$tmpext = '.restored';
			$ext = $tmpext;
			$i = 1;
			while ( $view->file_exists('files'.$location.'/'.$filename.$ext) ) {
				$ext = $tmpext.$i;
				$i++;
			}
		}
		return $ext;
	}

	/**
	 * @brief get the size from a given root folder
	 * @param $view file view on the root folder
	 * @return size of the folder
	 */
	private static function calculateSize($view) {
		$root = \OCP\Config::getSystemValue('datadirectory').$view->getAbsolutePath('');
		if (!file_exists($root)) {
			return 0;
		}
		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root),
			\RecursiveIteratorIterator::CHILD_FIRST);
		$size = 0;

		foreach ($iterator as $path) {
			$relpath = substr($path, strlen($root)-1);
			if ( !$view->is_dir($relpath) ) {
				$size += $view->filesize($relpath);
			}
		}
		return $size;
	}

	/**
	 * get current size of trash bin from a given user
	 *
	 * @param $user user who owns the trash bin
	 * @return mixed trash bin size or false if no trash bin size is stored
	 */
	private static function getTrashbinSize($user) {
		$query = \OC_DB::prepare('SELECT `size` FROM `*PREFIX*files_trashsize` WHERE `user`=?');
		$result = $query->execute(array($user))->fetchAll();

		if ($result) {
			return $result[0]['size'];
		}
		return false;
	}
	
	/**
	 * write to the database how much space is in use for the trash bin
	 *
	 * @param $user owner of the trash bin
	 * @param $size size of the trash bin
	 */
	private static function setTrashbinSize($user, $size) {
		if ( self::getTrashbinSize($user) === false) {
			$query = \OC_DB::prepare('INSERT INTO `*PREFIX*files_trashsize` (`size`, `user`) VALUES (?, ?)');
		}else {
			$query = \OC_DB::prepare('UPDATE `*PREFIX*files_trashsize` SET `size`=? WHERE `user`=?');
		}
		$query->execute(array($size, $user));
	}
	
}
