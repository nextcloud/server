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
	 * @brief return encryption mode client or server side encryption
	 * @param string user name (use system wide setting if name=null)
	 * @return string 'client' or 'server'
	 */
	public static function mode( $user = null ) {
		
		$mode = \OC_Appconfig::getValue( 'files_encryption', 'mode', 'none' );

		if ( $mode == 'user') {
			if ( !$user ) {
				$user = \OCP\User::getUser();
			}
			$mode = 'none';
			if ( $user ) {
				$query = \OC_DB::prepare( "SELECT mode FROM *PREFIX*encryption WHERE uid = ?" );
				$result = $query->execute(array($user));
				if ($row = $result->fetchRow()){
					$mode = $row['mode'];
				}
			}
		}

		return $mode;
	}
	
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
         * @brief Check if a file's contents contains an IV and is symmetrically encrypted
         * @return true / false
         */
	public static function isEncryptedContent( $content ) {
	
		if ( !$content ) {
		
			return false;
			
		}
		
		// Fetch encryption metadata from end of file
		$meta = substr( $content, -22 );
		
		// Fetch IV from end of file
		$iv = substr( $meta, -16 );
		
		// Fetch identifier from start of metadata
		$identifier = substr( $meta, 0, 6 );
		
		if ( $identifier == '00iv00') {
		
			return true;
			
		} else {
		
			return false;
			
		}
	
	}
	
        /**
         * @brief Check if a file is encrypted via legacy system
         * @return true / false
         */
	public static function isLegacyEncryptedContent( $content, $path ) {
	
		// Fetch all file metadata from DB
		$metadata = \OC_FileCache_Cached::get( $content, '' );
	
		// If a file is flagged with encryption in DB, but isn't a valid content + IV combination, it's probably using the legacy encryption system
		if ( 
		$content
		and isset( $metadata['encrypted'] ) 
		and $metadata['encrypted'] === true 
		and !self::isEncryptedContent( $content ) 
		) {
		
			return true;
		
		} else {
		
			return false;
			
		}
	
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
	echo "\n\nJET \$passphrase = $passphrase , \$iv = $iv\n\n";
		if ( $plainContent = openssl_decrypt( $encryptedContent, 'AES-128-CFB', $passphrase, false, $iv ) ) {

			return $plainContent;
		
			
		} else {
		
			\OC_Log::write( 'Encryption library', 'Decryption (symmetric) of content failed' , \OC_Log::ERROR );
			
			return false;
			
		}
	
	}
	
	public static function concatIv ( $content, $iv ) {
	
		$combined = $content . '00iv00' . $iv;
		
		return $combined;
	
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
		
		$iv = self::generateIv();
		
		if ( $encryptedContent = self::encrypt( $plainContent, $iv, $passphrase ) ) {
			
				// Combine content to encrypt with IV identifier and actual IV
				$combinedKeyfile = self::concatIv( $encryptedContent, $iv );
				
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
	* @returns decrypted content
	*
	* This function decrypts a file
	*/
	public static function symmetricDecryptFileContent( $keyfileContent, $passphrase = '' ) {
	
		if ( !$keyfileContent ) {
		
			return false;
			
		}
		
		// Fetch IV from end of file
		$iv = substr( $keyfileContent, -16 );
		
		// Remove IV and IV identifier text to expose encrypted content
		$encryptedContent = substr( $keyfileContent, 0, -22 );
		
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
	* @returns string $plainContent decrypted string
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
         * @brief Encrypts content symmetrically and generated keyfile asymmetrically
         * @returns array keys: data, key
         * @note this method is a wrapper for combining other crypt class methods
         */
	public static function keyEncryptKeyfile( $plainContent, $publicKey ) {
		
		// Encrypt plain data, generate keyfile & encrypted file
		$cryptedData = self::symmetricEncryptFileContentKeyfile( $plainContent );
		
		// Encrypt keyfile
		$cryptedKey = self::keyEncrypt( $cryptedData['key'], $publicKey );
		
		return array( 'data' => $cryptedData['encrypted'], 'key' => $cryptedKey );
		
	}
	
        /**
         * @brief Encrypts content symmetrically and generated keyfile asymmetrically
         * @returns decrypted content
         * @note this method is a wrapper for combining other crypt class methods
         */
	public static function keyDecryptKeyfile( $encryptedData, $encryptedKey, $privateKey ) {
		
		// Decrypt keyfile
		$decryptedKey = self::keyDecrypt( $encryptedKey, $privateKey );
		
		// Decrypt encrypted file
		$decryptedData = self::symmetricDecryptFileContent( $encryptedData, $decryptedKey );
		
		return $decryptedData;
		
	}
	
	/**
	* @brief Symmetrically encrypt a file by combining encrypted component data blocks
	*/
	public static function symmetricBlockEncryptFileContent( $plainContent, $key ) {
	
		$crypted = '';
		
		$remaining = $plainContent;
		
		$testarray = array();
		
		while( strlen( $remaining ) ) {
		
			//echo "\n\n\$block = ".substr( $remaining, 0, 8192 );
		
			// Encrypt a chunk of unencrypted data and add it to the rest
			$block = self::symmetricEncryptFileContent( substr( $remaining, 0, 8192 ), $key );
			
			$crypted .= $block;
			
			$testarray[] = $block;
			
			// Remove the data already encrypted from remaining unencrypted data
			$remaining = substr( $remaining, 8192 );
		
		}
		
		//echo "hags   ";
		
		//echo "\n\n\n\$crypted = $crypted\n\n\n";
		
		//print_r($testarray);
		
		return $crypted;

	}


	/**
	* @brief Symmetrically decrypt a file by combining encrypted component data blocks
	*/
	public static function symmetricBlockDecryptFileContent( $crypted, $key ) {

		echo "\n\n\nfags \$crypted = $crypted\n\n\n";
		
		$decrypted = '';
		
		$remaining = $crypted;
		
		$testarray = array();
		
		while( strlen( $remaining ) ) {
			
			$testarray[] = substr( $remaining, 0, 10946 );
		
			// Encrypt a chunk of unencrypted data and add it to the rest
			// 10946 is the length of a 8192 string once it has been encrypted
			$decrypted .= self::symmetricDecryptFileContent( substr( $remaining, 0, 10946 ), $key );
			
			// Remove the data already encrypted from remaining unencrypted data
			$remaining = substr( $remaining, 10946 );
			
		}
		
		echo "nags   ";
		
		print_r($testarray);
		
		return $decrypted;
		
	}
	
        /**
         * @brief Generate a pseudo random 1024kb ASCII key
         * @returns $key Generated key
         */
	public static function generateIv() {
		
		if ( $random = openssl_random_pseudo_bytes( 13, $strong ) ) {
		
			if ( !$strong ) {
			
				// If OpenSSL indicates randomness is insecure, log error
				\OC_Log::write( 'Encryption library', 'Insecure symmetric key was generated using openssl_random_pseudo_bytes()' , \OC_Log::WARN );
			
			}
			
			$iv = substr( base64_encode( $random ), 0, -4 );
			
			return $iv;
			
		} else {
		
			return false;
			
		}
		
	}
	
        /**
         * @brief Generate a pseudo random 1024kb ASCII key
         * @returns $key Generated key
         */
	public static function generateKey() {
		
		// $key = mt_rand( 10000, 99999 ) . mt_rand( 10000, 99999 ) . mt_rand( 10000, 99999 ) . mt_rand( 10000, 99999 );
		
		// Generate key
		if ( $key = base64_encode( openssl_random_pseudo_bytes( 183, $strong ) ) ) {
		
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

		if(\OCP\User::isLoggedIn()){
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

?>