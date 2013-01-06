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

namespace OCA\Encryption;

/**
 * @brief Class to manage storage and retrieval of encryption keys
 * @note Where a method requires a view object, it's root must be '/'
 */
class Keymanager {
	
	# TODO: make all dependencies (including static classes) explicit, such as ocfsview objects, by adding them as method arguments (dependency injection)
		
	/**
	 * @brief retrieve the ENCRYPTED private key from a user
	 * 
	 * @return string private key or false
	 * @note the key returned by this method must be decrypted before use
	 */
	public static function getPrivateKey( \OC_FilesystemView $view, $user ) {
	
		$path =  '/' . $user . '/' . 'files_encryption' . '/' . $user.'.private.key';
		
		$key = $view->file_get_contents( $path );
		
		return $key;
	}

	/**
	 * @brief retrieve public key for a specified user
	 * @return string public key or false
	 */
	public static function getPublicKey( \OC_FilesystemView $view, $userId ) {
		
		return $view->file_get_contents( '/public-keys/' . '/' . $userId . '.public.key' );
		
	}
	
	/**
	 * @brief retrieve both keys from a user (private and public)
	 * @return array keys: privateKey, publicKey
	 */
	public static function getUserKeys( \OC_FilesystemView $view, $userId ) {
	
		return array(
			'publicKey' => self::getPublicKey( $view, $userId )
			, 'privateKey' => self::getPrivateKey( $view, $userId )
		);
	
	}
	
	/**
	 * @brief Retrieve public keys of all users with access to a file
	 * @param string $path Path to file
	 * @return array of public keys for the given file
	 * @note Checks that the sharing app is enabled should be performed 
	 * by client code, that isn't checked here
	 */
	public static function getPublicKeys( \OC_FilesystemView $view, $userId, $filePath ) {
		
		$path = ltrim( $path, '/' );
		
		$filepath = '/' . $userId . '/files/' . $filePath;
		
		// Check if sharing is enabled
		if ( OC_App::isEnabled( 'files_sharing' ) ) {
			
// 			// Check if file was shared with other users
// 			$query = \OC_DB::prepare( "
// 				SELECT 
// 					uid_owner
// 					, source
// 					, target
// 					, uid_shared_with 
// 				FROM 
// 					`*PREFIX*sharing` 
// 				WHERE 
// 					( target = ? AND uid_shared_with = ? ) 
// 					OR source = ? 
// 			" );
// 			
// 			$result = $query->execute( array ( $filepath, $userId, $filepath ) );
// 
// 			$users = array();
// 
// 			if ( $row = $result->fetchRow() ) 
// {
// 				$source = $row['source'];
// 				$owner = $row['uid_owner'];
// 				$users[] = $owner;
// 				// get the uids of all user with access to the file
// 				$query = \OC_DB::prepare( "SELECT source, uid_shared_with FROM `*PREFIX*sharing` WHERE source = ?" );
// 				$result = $query->execute( array ($source));
// 				while ( ($row = $result->fetchRow()) ) {
// 					$users[] = $row['uid_shared_with'];
// 
// 				}
// 
// 			}
		
		} else {
		
			// check if it is a file owned by the user and not shared at all
			$userview = new \OC_FilesystemView( '/'.$userId.'/files/' );
			
			if ( $userview->file_exists( $path ) ) {
			
				$users[] = $userId;
				
			}
			
		}
		
		$view = new \OC_FilesystemView( '/public-keys/' );
		
		$keylist = array();
		
		$count = 0;
		
		foreach ( $users as $user ) {
		
			$keylist['key'.++$count] = $view->file_get_contents( $user.'.public.key' );
			
		}
		
		return $keylist;
		
	}
	
	/**
	 * @brief retrieve keyfile for an encrypted file
	 * @param string file name
	 * @return string file key or false
	 * @note The keyfile returned is asymmetrically encrypted. Decryption
	 * of the keyfile must be performed by client code
	 */
	public static function getFileKey( \OC_FilesystemView $view, $userId, $filePath ) {
		
		$filePath_f = ltrim( $filePath, '/' );

// 		// update $keypath and $userId if path point to a file shared by someone else
// 		$query = \OC_DB::prepare( "SELECT uid_owner, source, target FROM `*PREFIX*sharing` WHERE target = ? AND uid_shared_with = ?" );
// 		
// 		$result = $query->execute( array ('/'.$userId.'/files/'.$keypath, $userId));
// 		
// 		if ($row = $result->fetchRow()) {
// 		
// 			$keypath = $row['source'];
// 			$keypath_parts = explode( '/', $keypath );
// 			$userId = $keypath_parts[1];
// 			$keypath = str_replace( '/' . $userId . '/files/', '', $keypath );
// 			
// 		}

		return $view->file_get_contents( '/' . $userId . '/files_encryption/keyfiles/' . $filePath_f . '.key' );
		
	}
	
	/**
	 * @brief retrieve file encryption key
	 *
	 * @param string file name
	 * @return string file key or false
	 */
	public static function deleteFileKey( $path, $staticUserClass = 'OCP\User' ) {
		
		$keypath = ltrim( $path, '/' );
		$user = $staticUserClass::getUser();

		// update $keypath and $user if path point to a file shared by someone else
// 		$query = \OC_DB::prepare( "SELECT uid_owner, source, target FROM `*PREFIX*sharing` WHERE target = ? AND uid_shared_with = ?" );
// 		
// 		$result = $query->execute( array ('/'.$user.'/files/'.$keypath, $user));
// 		
// 		if ($row = $result->fetchRow()) {
// 		
// 			$keypath = $row['source'];
// 			$keypath_parts = explode( '/', $keypath );
// 			$user = $keypath_parts[1];
// 			$keypath = str_replace( '/' . $user . '/files/', '', $keypath );
// 			
// 		}
		
		$view = new \OC_FilesystemView('/'.$user.'/files_encryption/keyfiles/');
		
		return $view->unlink( $keypath . '.key' );
		
	}
	
	/**
	 * @brief store private key from the user
	 * @param string key
	 * @return bool
	 * @note Encryption of the private key must be performed by client code
	 * as no encryption takes place here
	 */
	public static function setPrivateKey( $key ) {
		
		$user = \OCP\User::getUser();
		
		$view = new \OC_FilesystemView( '/' . $user . '/files_encryption' );
		
		\OC_FileProxy::$enabled = false;
		
		if ( !$view->file_exists( '' ) ) $view->mkdir( '' );
		
		return $view->file_put_contents( $user . '.private.key', $key );
		
		\OC_FileProxy::$enabled = true;
		
	}
	
	/**
	 * @brief store private keys from the user
	 *
	 * @param string privatekey
	 * @param string publickey
	 * @return bool true/false
	 */
	public static function setUserKeys($privatekey, $publickey) {
	
		return (self::setPrivateKey($privatekey) && self::setPublicKey($publickey));
	
	}
	
	/**
	 * @brief store public key of the user
	 *
	 * @param string key
	 * @return bool true/false
	 */
	public static function setPublicKey( $key ) {
		
		$view = new \OC_FilesystemView( '/public-keys' );
		
		\OC_FileProxy::$enabled = false;
		
		if ( !$view->file_exists( '' ) ) $view->mkdir( '' );
		
		return $view->file_put_contents( \OCP\User::getUser() . '.public.key', $key );
		
		\OC_FileProxy::$enabled = true;
		
	}
	
	/**
	 * @brief store file encryption key
	 *
	 * @param string $path relative path of the file, including filename
	 * @param string $key
	 * @return bool true/false
	 * @note The keyfile is not encrypted here. Client code must 
	 * asymmetrically encrypt the keyfile before passing it to this method
	 */
	public static function setFileKey( $path, $key, $view = Null, $dbClassName = '\OC_DB') {

		$targetPath = ltrim(  $path, '/'  );
		$user = \OCP\User::getUser();
		
// 		// update $keytarget and $user if key belongs to a file shared by someone else
// 		$query = $dbClassName::prepare( "SELECT uid_owner, source, target FROM `*PREFIX*sharing` WHERE target = ? AND uid_shared_with = ?" );
// 		
// 		$result = $query->execute(  array ( '/'.$user.'/files/'.$targetPath, $user ) );
// 		
// 		if ( $row = $result->fetchRow(  ) ) {
// 		
// 			$targetPath = $row['source'];
// 			
// 			$targetPath_parts = explode( '/', $targetPath );
// 			
// 			$user = $targetPath_parts[1];
// 
// 			$rootview = new \OC_FilesystemView( '/' );
// 			
// 			if ( ! $rootview->is_writable( $targetPath ) ) {
// 			
// 				\OC_Log::write( 'Encryption library', "File Key not updated because you don't have write access for the corresponding file", \OC_Log::ERROR );
// 				
// 				return false;
// 				
// 			}
// 			
// 			$targetPath = str_replace( '/'.$user.'/files/', '', $targetPath );
// 			
// 			//TODO: check for write permission on shared file once the new sharing API is in place
// 			
// 		}
		
		$path_parts = pathinfo( $targetPath );
		
		if ( !$view ) {
		
			$view = new \OC_FilesystemView( '/' );
			
		}
		
		$view->chroot( '/' . $user . '/files_encryption/keyfiles' );
		
		// If the file resides within a subdirectory, create it
		if ( 
		isset( $path_parts['dirname'] )
		&& ! $view->file_exists( $path_parts['dirname'] ) 
		) {
		
			$view->mkdir( $path_parts['dirname'] );
			
		}
		
		// Save the keyfile in parallel directory
		return $view->file_put_contents( '/' . $targetPath . '.key', $key );
		
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
			return Crypt::changekeypasscode($oldpasswd, $newpasswd);
		}
		return false;
		
	}
	
	/**
	 * @brief Fetch the legacy encryption key from user files
	 * @param string $login used to locate the legacy key
	 * @param string $passphrase used to decrypt the legacy key
	 * @return true / false
	 *
	 * if the key is left out, the default handeler will be used
	 */
	public function getLegacyKey() {
		
		$user = \OCP\User::getUser();
		$view = new \OC_FilesystemView( '/' . $user );
		return $view->file_get_contents( 'encryption.key' );
		
	}
	
}