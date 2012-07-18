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
         * @brief Symmetrically encrypt a string
         * @returns encrypted file
         */
	public static function encrypt( $plainContent, $iv, $passphrase = '' ) {
		
		if ( $encryptedContent = openssl_encrypt( $plainContent, 'AES-128-CFB', $passphrase, false, $iv ) ) {

			return $encryptedContent;
			
		} else {
		
			\OC_Log::write( 'Encryption library', 'Encryption (symmetric) of content failed' , \OC_Log::ERROR );
			
			return false;
			
		}
	
	}
	
        /**
         * @brief Symmetrically decrypt a string
         * @returns decrypted file
         */
	public static function decrypt( $encryptedContent, $iv, $passphrase ) {

		if ( $plainContent = openssl_decrypt( $encryptedContent, 'AES-128-CFB', $passphrase, false, $iv ) ) {

			return $plainContent;
		
			
		} else {
		
			\OC_Log::write( 'Encryption library', 'Decryption (symmetric) of content failed' , \OC_Log::ERROR );
			
			return false;
			
		}
	
	}
	
        /**
         * @brief Symmetrically encrypts a string and returns keyfile content
         * @param $plainContent content to be encrypted in keyfile
         * @returns encrypted content combined with IV
         * @note IV need not be specified, as it will be stored in the returned keyfile
         * and remain accessible therein.
         */
	public static function symmetricEncryptFileContent( $plainContent, $passphrase = '' ) {
		
		if ( !$plainContent ) {
		
			return false;
			
		}
		
		$random = openssl_random_pseudo_bytes( 13 );

		$iv = substr( base64_encode( $random ), 0, -4 );
		
		if ( $encryptedContent = self::encrypt( $plainContent, $iv, $passphrase ) ) {
			
				$combinedKeyfile = $encryptedContent .= $iv;
				
				return $combinedKeyfile;
		
		} else {
		
			\OC_Log::write( 'Encryption library', 'Encryption (symmetric) of keyfile content failed' , \OC_Log::ERROR );
			
			return false;
			
		}
		
	}


	/**
	* @brief Symmetrically decrypts keyfile content
	* @param string $source
	* @param string $target
	* @param string $key the decryption key
	*
	* This function decrypts a file
	*/
	public static function symmetricDecryptFileContent( $keyfileContent, $passphrase = '' ) {
	
		if ( !$keyfileContent ) {
		
			return false;
			
		}
		
		$iv = substr( $keyfileContent, -16 );
		
		$encryptedContent = substr( $keyfileContent, 0, -16 );
		
		if ( $plainContent = self::decrypt( $encryptedContent, $iv, $passphrase ) ) {
		
			return $plainContent;
			
		} else {
		
			\OC_Log::write( 'Encryption library', 'Decryption (symmetric) of keyfile content failed' , \OC_Log::ERROR );
			
			return false;
			
		}
	
	}
	
	/**
	* @brief Creates symmetric keyfile content using a generated key
	* @param string $plainContent content to be encrypted
	* @returns array keys: key, encrypted
	* @note symmetricDecryptFileContent() can be used to decrypt files created using this method
	*
	* This function decrypts a file
	*/
	public static function symmetricEncryptFileContentKeyfile( $plainContent ) {
	
		$key = self::generateKey();
	
		if( $encryptedContent = self::symmetricEncryptFileContent( $plainContent, $key ) ) {
		
			return array(
				'key' => $key
				, 'encrypted' => $encryptedContent
			);
		
		} else {
		
			return false;
			
		}
	
	}
	
	/**
	* @brief Create asymmetrically encrypted keyfile content using a generated key
	* @param string $plainContent content to be encrypted
	* @returns array keys: key, encrypted
	* @note symmetricDecryptFileContent() can be used to decrypt files created using this method
	*
	* This function decrypts a file
	*/
	public static function multiKeyEncrypt( $plainContent, array $publicKeys ) {
	
		$envKeys = array();
	
		if( openssl_seal( $plainContent, $sealed, $envKeys, $publicKeys ) ) {
		
			return array(
				'keys' => $envKeys
				, 'encrypted' => $sealed
			);
		
		} else {
		
			return false;
			
		}
	
	}
	
	/**
	* @brief Asymmetrically encrypt a file using multiple public keys
	* @param string $plainContent content to be encrypted
	* @returns array keys: key, encrypted
	* @note symmetricDecryptFileContent() can be used to decrypt files created using this method
	*
	* This function decrypts a file
	*/
	public static function multiKeyDecrypt( $encryptedContent, $envKey, $privateKey ) {
	
		if ( !$encryptedContent ) {
		
			return false;
			
		}
		
		if ( openssl_open( $encryptedContent, $plainContent, $envKey, $privateKey ) ) {
		
			return $plainContent;
			
		} else {
		
			\OC_Log::write( 'Encryption library', 'Decryption (asymmetric) of sealed content failed' , \OC_Log::ERROR );
			
			return false;
			
		}
	
	}
	
        /**
         * @brief Asymetrically encrypt a string using a public key
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
	
        /**
         * @brief Generate a pseudo random 1024kb ASCII key
         * @returns $key Generated key
         */
	public static function generateKey() {
		
		// $key = mt_rand( 10000, 99999 ) . mt_rand( 10000, 99999 ) . mt_rand( 10000, 99999 ) . mt_rand( 10000, 99999 );
		
		// Generate key
		if ( $key = base64_encode( openssl_random_pseudo_bytes( 768000, $strong ) ) ) {
		
			if ( !$strong ) {
			
				// If OpenSSL indicates randomness is insecure, log error
				\OC_Log::write( 'Encryption library', 'Insecure symmetric key was generated using openssl_random_pseudo_bytes()' , \OC_Log::WARN );
			
			}
		
			return $key;
			
		} else {
		
			return false;
			
		}
		
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