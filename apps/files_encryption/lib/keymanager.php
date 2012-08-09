<?php
/***
 * ownCloud
 *
 * @author Bjoern Schiessle
 * @copyright 2012 Bjoern Schiessle <schiessle@owncloud.com>
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

namespace OCA_Encryption;

/**
 * This class provides basic operations to read/write encryption keys from/to the filesystem 
 */
class Keymanager {
	
	# TODO: make all dependencies (including static classes) explicit, such as ocfsview objects, by adding them as method arguments (dependency injection)
		
	/**
	 * @brief retrieve private key from a user
	 * 
	 * @return string private key or false
	 */
	public static function getPrivateKey() {

		\OC_FileProxy::$enabled = false;
		
		$user = \OCP\User::getUser();	
		$view = new \OC_FilesystemView( '/' . $user . '/' . 'files_encryption' );
		$result = $view->file_get_contents( '/' . $user.'.private.key' );
		
		\OC_FileProxy::$enabled = true;
		
		return $result;
	}
	
	/**
	 * @brief retrieve a list of the public key from all users with access to the file
	 *
	 * @param string path to file
	 * @return array of public keys for the given file
	 */
	public static function getPublicKeys($path) {
		$userId = \OCP\User::getUser();
		$path = ltrim( $path, '/' );
		$filepath = '/'.$userId.'/files/'.$path;
		
		// check if file was shared with other users
		$query = \OC_DB::prepare( "SELECT uid_owner, source, target, uid_shared_with FROM `*PREFIX*sharing` WHERE ( target = ? AND uid_shared_with = ? ) OR source = ? " );
		$result = $query->execute( array ($filepath, $userId, $filepath));
		$users = array();
		if ($row = $result->fetchRow()){
			$source = $row['source'];
			$owner = $row['uid_owner'];
			$users[] = $owner;
			// get the uids of all user with access to the file
			$query = \OC_DB::prepare( "SELECT source, uid_shared_with FROM `*PREFIX*sharing` WHERE source = ?" );
			$result = $query->execute( array ($source));
			while ( ($row = $result->fetchRow()) ) {
				$users[] = $row['uid_shared_with'];
			}
		} else {
			// check if it is a file owned by the user and not shared at all
			$userview = new \OC_FilesystemView( '/'.$userId.'/files/' );
			if ($userview->file_exists($path)) {
				$users[] = $userId;
			}
		}
		
		\OC_FileProxy::$enabled = false;
		
		$view = new \OC_FilesystemView( '/public-keys/' );
		
		$keylist = array();
		$count = 0;
		foreach ($users as $user) {
			$keylist['key'.++$count] = $view->file_get_contents($user.'.public.key');
		}

		\OC_FileProxy::$enabled = true;
		
		return $keylist;
		
	}
	
	/**
	 * @brief retrieve file encryption key
	 *
	 * @param string file name
	 * @return string file key or false
	 */
	public static function getFileKey( $path ) {
		
		$keypath = ltrim( $path, '/' );
		$user = \OCP\User::getUser();

		// update $keypath and $user if path point to a file shared by someone else
		$query = \OC_DB::prepare( "SELECT uid_owner, source, target FROM `*PREFIX*sharing` WHERE target = ? AND uid_shared_with = ?" );
		$result = $query->execute( array ('/'.$user.'/files/'.$keypath, $user));
		if ($row = $result->fetchRow()){
			$keypath = $row['source'];
			$keypath_parts=explode('/',$keypath);
			$user = $keypath_parts[1];
			$keypath = str_replace('/'.$user.'/files/', '', $keypath);
		}
		
		\OC_FileProxy::$enabled = false;
		
		$view = new \OC_FilesystemView('/'.$user.'/files_encryption/keyfiles/');
		$result = $view->file_get_contents($keypath.'.key');
		
		\OC_FileProxy::$enabled = true;
		
		return $result;
	}	
	
	/**
	 * @brief store private key from the user
	 *
	 * @param string key
	 * @return bool true/false
	 */
	public static function setPrivateKey($key) {

		\OC_FileProxy::$enabled = false;
		
		$user = \OCP\User::getUser();
		$view = new \OC_FilesystemView('/'.$user.'/files_encryption');
		if (!$view->file_exists('')) $view->mkdir('');
		$result = $view->file_put_contents($user.'.private.key', $key);
		
		\OC_FileProxy::$enabled = true;
		
		return $result;
	}
	
	
	/**
	 * @brief store public key of the user
	 *
	 * @param string key
	 * @return bool true/false
	 */
	public static function setPublicKey($key) {
		
		\OC_FileProxy::$enabled = false;
		
		$view = new \OC_FilesystemView('/public-keys');
		if (!$view->file_exists('')) $view->mkdir('');
		$result = $view->file_put_contents(\OCP\User::getUser().'.public.key', $key);
		
		\OC_FileProxy::$enabled = true;
		
		return $result;
	}
	
	/**
	 * @brief store file encryption key
	 *
	 * @param string $path relative path of the file, including filename
	 * @param string $key
	 * @return bool true/false
	 */
	public static function setFileKey( $path, $key, $view = Null, $dbClassName = '\OC_DB', $fileProxyClassName =  '\OC_FileProxy') {
		$fileProxyClassName::$enabled = false;

		$targetpath = ltrim(  $path, '/'  );
		$user = \OCP\User::getUser();
		
		// update $keytarget and $user if key belongs to a file shared by someone else
		$query = $dbClassName::prepare( "SELECT uid_owner, source, target FROM `*PREFIX*sharing` WHERE target = ? AND uid_shared_with = ?" );
		
		$result = $query->execute(  array ( '/'.$user.'/files/'.$targetpath, $user ) );
		
		if ( $row = $result->fetchRow(  ) ) {
		
			$targetpath = $row['source'];
			
			$targetpath_parts=explode( '/',$targetpath );
			
			$user = $targetpath_parts[1];
			
			$targetpath = str_replace( '/'.$user.'/files/', '', $targetpath );
			
			//TODO: check for write permission on shared file once the new sharing API is in place
			
		}
		
		$path_parts = pathinfo( $targetpath );

		if (!$view) {
			$view = new \OC_FilesystemView( '/' . $user . '/files_encryption/keyfiles' );
		}
		
		if ( !$view->file_exists( $path_parts['dirname'] ) ) $view->mkdir( $path_parts['dirname'] );
		
		$result = $view->file_put_contents( '/' . $targetpath . '.key', $key );
		
		$fileProxyClassName::$enabled = true;	
		
		return $result;
	}
	
	/**
	 * @brief change password of private encryption key
	 *
	 * @param string $oldpasswd old password
	 * @param string $newpasswd new password
	 * @return bool true/false
	 */
	public static function changePasswd($oldpasswd, $newpasswd) {
		if ( \OCP\User::checkPassword(\OCP\User::getUser(), $newpasswd) ) {
			$key = Keymanager::getPrivateKey();
			if ( ($key = Crypt::symmetricDecryptFileContent($key,$oldpasswd)) ) {
				if ( ($key = Crypt::symmetricEncryptFileContent($key, $newpasswd)) ) {
					Keymanager::setPrivateKey($key);
					return true;
				
				}
			}
		}
		return false;
	}
	
}