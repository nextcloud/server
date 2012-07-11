<?php
/**
 * ownCloud
 *
 * @author Sam Tuke, Frank Karlitschek, Robin Appelman
 * @copyright 2012 Sam Tuke samtuke@owncloud.com, 
 * Robin Appelman icewind@owncloud.com, Frank Karlitschek 
 * frank@owncloud.org
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
 * Class for common cryptography functionality
 */

class Crypt {

        /**
         * @brief Create a new encryption keypair
         * @return array publicKey, privatekey
         */
	public static function createKeypair() {
	
		$res = openssl_pkey_new();

		// Get private key
		openssl_pkey_export( $res, $privateKey );

		// Get public key
		$publicKey = openssl_pkey_get_details( $res );
		
		$publicKey = $publicKey['key'];
		
		return( array( 'publicKey' => $publicKey, 'privateKey' => $privateKey ) );
	
	}
	
        /**
         * @brief Symmetrically encrypt a file
         * @returns encrypted file
         */
	public static function encrypt( $plainContent, $iv, $passphrase = '' ) {
	
		# TODO: Move these methods into a separate public class for app developers
	
		$iv64 = base64_encode( $iv );
		
		$raw = false; // true returns raw bytes, false returns base64
		
		if ( $encryptedContent = openssl_encrypt( $plainContent, 'AES-256-OFB', $passphrase, $raw, $iv ) ) {

			return $encryptedContent;
			
		} else {
		
			\OC_Log::write( 'Encrypted storage', 'Encryption (symmetric) of file failed' , \OC_Log::ERROR );
			
			return false;
			
		}
	
	}
	
        /**
         * @brief Symmetrically decrypt a file
         * @returns decrypted file
         */
	public static function decrypt( $encryptedContent, $iv, $passphrase ) {
		
// 		$iv64 = base64_encode( $iv );
// 		
// 		$iv = base64_decode( $iv64 );

		$raw = false; // true returns raw bytes, false returns base64

		if ( $plainContent = openssl_decrypt( $encryptedContent, 'AES-256-OFB', $passphrase, $raw, $iv) ) {

			return $plainContent;
		
			
		} else {
		
			\OC_Log::write( 'Encrypted storage', 'Decryption (symmetric) of file failed' , \OC_Log::ERROR );
			
			return false;
			
		}
	
	}
	
        /**
         * @brief Asymetrically encrypt a file using a public key
         * @returns encrypted file
         */
	public static function keyEncrypt( $plainContent, $publicKey ) {
	
		openssl_public_encrypt( $plainContent, $encryptedContent, $publicKey );
		
		return $encryptedContent;
	
	}
	
        /**
         * @brief Asymetrically decrypt a file using a private key
         * @returns decrypted file
         */
	public static function keyDecrypt( $encryptedContent, $privatekey ) {

		openssl_private_decrypt( $encryptedContent, $plainContent, $privatekey );
		
		return $plainContent;
	
	}
	
	public static function encryptFile( $source, $target, $key='') {
		$handleread  = fopen($source, "rb");
		if($handleread!=FALSE) {
			$handlewrite = fopen($target, "wb");
			while (!feof($handleread)) {
				$content = fread($handleread, 8192);
				$enccontent=OC_CRYPT::encrypt( $content, $key);
				fwrite($handlewrite, $enccontent);
			}
			fclose($handlewrite);
			fclose($handleread);
		}
	}


	/**
	* @brief decryption of a file
	* @param string $source
	* @param string $target
	* @param string $key the decryption key
	*
	* This function decrypts a file
	*/
	public static function decryptFile( $source, $target, $key='') {
		$handleread  = fopen($source, "rb");
		if($handleread!=FALSE) {
			$handlewrite = fopen($target, "wb");
			while (!feof($handleread)) {
				$content = fread($handleread, 8192);
				$enccontent=OC_CRYPT::decrypt( $content, $key);
				if(feof($handleread)){
					$enccontent=rtrim($enccontent, "\0");
				}
				fwrite($handlewrite, $enccontent);
			}
			fclose($handlewrite);
			fclose($handleread);
		}
	}
	
        /**
         * @brief Encrypts data in 8192 byte sized blocks
         * @returns encrypted data
         */
	public static function blockEncrypt( $data, $key = '' ){
	
		$result = '';
		
		while( strlen( $data ) ) {
		
			// Encrypt byte block
			$result .= self::encrypt( substr( $data, 0, 8192 ), $key );
			
			$data = substr( $data, 8192 );
		
		}
		
		return $result;
	}
	
	/**
	 * decrypt data in 8192b sized blocks
	 */
	public static function blockDecrypt( $data, $key='', $maxLength = 0 ) {
		
		$result = '';
		
		while( strlen( $data ) ) {
		
			$result .= self::decrypt( substr( $data, 0, 8192 ), $key );
			
			$data = substr( $data,8192 );
			
		}
		
		if ( $maxLength > 0 ) {
		
			return substr( $result, 0, $maxLength );
			
		} else {
		
			return rtrim( $result, "\0" );
			
		}
	}
	
        /**
         * @brief Generate a random key for symmetric encryption
         * @returns $key Generated key
         */
	public static function generateKey() {
		
		$key = mt_rand( 10000, 99999 ) . mt_rand( 10000, 99999 ) . mt_rand( 10000, 99999 ) . mt_rand( 10000, 99999 );
		
		return $key;
		
	}

	public static function changekeypasscode($oldPassword, $newPassword) {
		if(OCP\User::isLoggedIn()){
			$username=OCP\USER::getUser();
			$view=new OC_FilesystemView('/'.$username);

			// read old key
			$key=$view->file_get_contents('/encryption.key');

			// decrypt key with old passcode
			$key=OC_Crypt::decrypt($key, $oldPassword);

			// encrypt again with new passcode
			$key=OC_Crypt::encrypt($key, $newPassword);

			// store the new key
			$view->file_put_contents('/encryption.key', $key );
		}
	}

}

?>