<?php

/**
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
	 * @param \OC_FilesystemView $view
	 * @param $userId
	 * @return string public key or false
	 */
	public static function getPublicKey( \OC_FilesystemView $view, $userId ) {
		
		return $view->file_get_contents( '/public-keys/' . '/' . $userId . '.public.key' );
		
	}
	
	/**
	 * @brief retrieve both keys from a user (private and public)
	 * @param \OC_FilesystemView $view
	 * @param $userId
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
	 * @brief store file encryption key
	 *
	 * @param string $path relative path of the file, including filename
	 * @param string $key
	 * @return bool true/false
	 * @note The keyfile is not encrypted here. Client code must 
	 * asymmetrically encrypt the keyfile before passing it to this method
	 */
	public static function setFileKey( \OC_FilesystemView $view, $path, $userId, $catfile ) {
		
		$basePath = '/' . $userId . '/files_encryption/keyfiles';
		
		$targetPath = self::keySetPreparation( $view, $path, $basePath, $userId );
		
		if ( $view->is_dir( $basePath . '/' . $targetPath ) ) {
		
			
		
		} else {

			// Save the keyfile in parallel directory
			return $view->file_put_contents( $basePath . '/' . $targetPath . '.key', $catfile );
		
		}
		
	}
	
	/**
	 * @brief retrieve keyfile for an encrypted file
	 * @param \OC_FilesystemView $view
	 * @param $userId
	 * @param $filePath
	 * @internal param \OCA\Encryption\file $string name
	 * @return string file key or false
	 * @note The keyfile returned is asymmetrically encrypted. Decryption
	 * of the keyfile must be performed by client code
	 */
	public static function getFileKey( \OC_FilesystemView $view, $userId, $filePath ) {
		
		$filePath_f = ltrim( $filePath, '/' );
		
		$catfilePath = '/' . $userId . '/files_encryption/keyfiles/' . $filePath_f . '.key';
		
		if ( $view->file_exists( $catfilePath ) ) {

			return $view->file_get_contents( $catfilePath );
			
		} else {
		
			return false;
			
		}
		
	}
	
	/**
	 * @brief Delete a keyfile
	 *
	 * @param OC_FilesystemView $view
	 * @param string $userId username
	 * @param string $path path of the file the key belongs to
	 * @return bool Outcome of unlink operation
	 * @note $path must be relative to data/user/files. e.g. mydoc.txt NOT
	 *       /data/admin/files/mydoc.txt
	 */
	public static function deleteFileKey( \OC_FilesystemView $view, $userId, $path ) {
		
		$trimmed = ltrim( $path, '/' );
		$keyPath =  '/' . $userId . '/files_encryption/keyfiles/' . $trimmed . '.key';
		
		// Unlink doesn't tell us if file was deleted (not found returns
		// true), so we perform our own test
		if ( $view->file_exists( $keyPath ) ) {
		
			return $view->unlink( $keyPath );
			
		} else {
			
			\OC_Log::write( 'Encryption library', 'Could not delete keyfile; does not exist: "' . $keyPath, \OC_Log::ERROR );
			
			return false;
			
		}
		
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
		
		if ( !$view->file_exists( '' ) )
			$view->mkdir( '' );
		
		return $view->file_put_contents( $user . '.private.key', $key );

	}
	
	/**
	 * @brief store private keys from the user
	 *
	 * @param string privatekey
	 * @param string publickey
	 * @return bool true/false
	 */
	public static function setUserKeys($privatekey, $publickey) {
	
		return ( self::setPrivateKey( $privatekey ) && self::setPublicKey( $publickey ) );
	
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
		
		if ( !$view->file_exists( '' ) )
			$view->mkdir( '' );
		
		return $view->file_put_contents( \OCP\User::getUser() . '.public.key', $key );

		
	}
	
	/**
	 * @brief store file encryption key
	 *
	 * @param string $path relative path of the file, including filename
	 * @param string $key
	 * @param null $view
	 * @param string $dbClassName
	 * @return bool true/false
	 * @note The keyfile is not encrypted here. Client code must
	 * asymmetrically encrypt the keyfile before passing it to this method
	 */
	public static function setShareKey( \OC_FilesystemView $view, $path, $userId, $shareKey ) {
		
		$basePath = '/' . $userId . '/files_encryption/share-keys';
		
		$shareKeyPath = self::keySetPreparation( $view, $path, $basePath, $userId );
		
		return $view->file_put_contents( $basePath . '/' . $shareKeyPath . '.shareKey', $shareKey );
		
	}
	
	/**
	 * @brief Make preparations to vars and filesystem for saving a keyfile
	 */
	public static function keySetPreparation( \OC_FilesystemView $view, $path, $basePath, $userId ) {
		
		$targetPath = ltrim( $path, '/' );
		
		$path_parts = pathinfo( $targetPath );
		
		// If the file resides within a subdirectory, create it
		if ( 
		isset( $path_parts['dirname'] )
		&& ! $view->file_exists( $basePath . '/' . $path_parts['dirname'] ) 
		) {
		
			$view->mkdir( $basePath . '/' . $path_parts['dirname'] );
			
		}
		
		return $targetPath;
	
	}

	/**
	 * @brief Fetch the legacy encryption key from user files
	 * @param string $login used to locate the legacy key
	 * @param string $passphrase used to decrypt the legacy key
	 * @return true / false
	 *
	 * if the key is left out, the default handler will be used
	 */
	public function getLegacyKey() {
		
		$user = \OCP\User::getUser();
		$view = new \OC_FilesystemView( '/' . $user );
		return $view->file_get_contents( 'encryption.key' );
		
	}
	
}