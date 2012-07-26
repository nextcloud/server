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
	
	# TODO: Try and get rid of username dependencies as these methods need to be used in a proxy class that doesn't have username access
	
	/**
	 * @brief retrieve private key from a user
	 * 
	 * @param string user name
	 * @return string private key or false
	 */
	public static function getPrivateKey( $user ) {

		$view = new \OC_FilesystemView( '/' . $user . '/' . 'files_encryption' );
		
		return $view->file_get_contents( '/' . $user.'.private.key' );
	}
	
	/**
	 * @brief retrieve public key from a user
	 *
	 * @param string user name
	 * @return string private key or false
	 */
	public static function getPublicKey($user) {
		$view = new \OC_FilesystemView( '/public-keys/' );
		return $view->file_get_contents($user.'.public.key');
	}
	
	/**
	 * @brief retrieve file encryption key
	 *
	 * @param string file name
	 * @param string user name of the file owner
	 * @return string file key or false
	 */
	public static function getFileKey($user, $file) {
		$view = new \OC_FilesystemView('/'.$user.'/files_encryption/keyfiles/');
		return $view->file_get_contents($file.'.key');
	}	
	
	/**
	 * @brief store private key from a user
	 *
	 * @param string user name
	 * @param string key
	 * @return bool true/false
	 */
	public static function setPrivateKey($user, $key) {

		\OC_FileProxy::$enabled = false;
		
		$view = new \OC_FilesystemView('/'.$user.'/files_encryption');
		if (!$view->file_exists('')) $view->mkdir('');
		$result = $view->file_put_contents($user.'.private.key', $key);
		
		\OC_FileProxy::$enabled = true;
		
		return $result;
	}
	
	
	/**
	 * @brief store public key from a user
	 *
	 * @param string user name
	 * @param string key
	 * @return bool true/false
	 */
	public static function setPublicKey($user, $key) {
		
		\OC_FileProxy::$enabled = false;
		
		$view = new \OC_FilesystemView('/public-keys');
		if (!$view->file_exists('')) $view->mkdir('');
		$result = $view->file_put_contents($user.'.public.key', $key);
		
		\OC_FileProxy::$enabled = true;
		
		return $result;
	}
	
	/**
	 * @brief store file encryption key
	 *
	 * @param string $userId name of the file owner
	 * @param string $path relative path of the file, including filename
	 * @param string $key
	 * @return bool true/false
	 */
	public static function setFileKey( $userId, $path, $key ) {
	
		\OC_FileProxy::$enabled = false;
		
		$view = new \OC_FilesystemView( '/' . $userId . '/' . 'files_encryption' );
		$path_parts = pathinfo($path);
		if (!$view->file_exists($path_parts['dirname'])) $view->mkdir($path_parts['dirname']);
		$result = $view->file_put_contents( '/' . $path . '.key', $key );
		
		\OC_FileProxy::$enabled = true;	
		
		return $result;
	}
	
}