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

namespace OCA\Encryption;

require_once 'Crypt_Blowfish/Blowfish.php';

// Todo:
//  - Crypt/decrypt button in the userinterface
//  - Setting if crypto should be on by default
//  - Add a setting "Don´t encrypt files larger than xx because of performance reasons"
//  - Transparent decrypt/encrypt in filesystem.php. Autodetect if a file is encrypted (.encrypted extension)
//  - Don't use a password directly as encryption key. but a key which is stored on the server and encrypted with the user password. -> password change faster
//  - IMPORTANT! Check if the block lenght of the encrypted data stays the same

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
		
// 		$mode = \OC_Appconfig::getValue( 'files_encryption', 'mode', 'none' );
// 
// 		if ( $mode == 'user') {
// 			if ( !$user ) {
// 				$user = \OCP\User::getUser();
// 			}
// 			$mode = 'none';
// 			if ( $user ) {
// 				$query = \OC_DB::prepare( "SELECT mode FROM *PREFIX*encryption WHERE uid = ?" );
// 				$result = $query->execute(array($user));
// 				if ($row = $result->fetchRow()){
// 					$mode = $row['mode'];
// 				}
// 			}
// 		}
// 		
// 		return $mode;

		return 'server';
		
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
         * @brief Add arbitrary padding to encrypted data
         * @param string $data data to be padded
         * @return padded data
         * @note In order to end up with data exactly 8192 bytes long we must add two letters. It is impossible to achieve exactly 8192 length blocks with encryption alone, hence padding is added to achieve the required length. 
         */
	public static function addPadding( $data ) {
	
		$padded = $data . 'xx';
		
		return $padded;
	
	}
	
        /**
         * @brief Remove arbitrary padding to encrypted data
         * @param string $padded padded data to remove padding from
         * @return unpadded data on success, false on error
         */
	public static function removePadding( $padded ) {
	
		if ( substr( $padded, -2 ) == 'xx' ) {
	
			$data = substr( $padded, 0, -2 );
			
			return $data;
		
		} else {
		
			# TODO: log the fact that unpadded data was submitted for removal of padding
			return false;
			
		}
	
	}
	
        /**
         * @brief Check if a file's contents contains an IV and is symmetrically encrypted
         * @return true / false
         * @note see also OCA\Encryption\Util->isEncryptedPath()
         */
	public static function isEncryptedContent( $content ) {
	
		if ( !$content ) {
		
			return false;
			
		}
		
		$noPadding = self::removePadding( $content );
		
		// Fetch encryption metadata from end of file
		$meta = substr( $noPadding, -22 );
		
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
	 * Check if a file is encrypted according to database file cache
	 * @param string $path
	 * @return bool
	 */
	public static function isEncryptedMeta( $path ) {
	
		# TODO: Use DI to get OC_FileCache_Cached out of here
	
		// Fetch all file metadata from DB
		$metadata = \OC_FileCache_Cached::get( $path, '' );
		
		// Return encryption status
		return isset( $metadata['encrypted'] ) and ( bool )$metadata['encrypted'];
	
	}
	
        /**
         * @brief Check if a file is encrypted via legacy system
         * @return true / false
         */
	public static function isLegacyEncryptedContent( $content ) {
	
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
	
		if ( $plainContent = openssl_decrypt( $encryptedContent, 'AES-128-CFB', $passphrase, false, $iv ) ) {

			return $plainContent;
		
			
		} else {
		
			throw new \Exception( 'Encryption library: Decryption (symmetric) of content failed' );
			
			return false;
			
		}
	
	}
	
        /**
         * @brief Concatenate encrypted data with its IV and padding
         * @param string $content content to be concatenated
         * @param string $iv IV to be concatenated
         * @returns string concatenated content
         */
	public static function concatIv ( $content, $iv ) {
	
		$combined = $content . '00iv00' . $iv;
		
		return $combined;
	
	}
	
        /**
         * @brief Split concatenated data and IV into respective parts
         * @param string $catFile concatenated data to be split
         * @returns array keys: encrypted, iv
         */
	public static function splitIv ( $catFile ) {
	
		// Fetch encryption metadata from end of file
		$meta = substr( $catFile, -22 );
		
		// Fetch IV from end of file
		$iv = substr( $meta, -16 );
		
		// Remove IV and IV identifier text to expose encrypted content
		$encrypted = substr( $catFile, 0, -22 );
	
		$split = array(
			'encrypted' => $encrypted
			, 'iv' => $iv
		);
		
		return $split;
	
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
				$catfile = self::concatIv( $encryptedContent, $iv );
				
				$padded = self::addPadding( $catfile );
				
				return $padded;
		
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
		
			throw new \Exception( 'Encryption library: no data provided for decryption' );
			
		}
		
		// Remove padding
		$noPadding = self::removePadding( $keyfileContent );
		
		// Split into enc data and catfile
		$catfile = self::splitIv( $noPadding );
		
		if ( $plainContent = self::decrypt( $catfile['encrypted'], $catfile['iv'], $passphrase ) ) {
		
			return $plainContent;
			
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
         * @brief Encrypts content symmetrically and generates keyfile asymmetrically
         * @returns array containing catfile and new keyfile. 
         * keys: data, key
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
         * @brief Takes catfile, keyfile, and private key, and
         * performs decryption
         * @returns decrypted content
         * @note this method is a wrapper for combining other crypt class methods
         */
	public static function keyDecryptKeyfile( $catfile, $keyfile, $privateKey ) {
		
		// Decrypt the keyfile with the user's private key
		$decryptedKeyfile = self::keyDecrypt( $keyfile, $privateKey );
		
		// Decrypt the catfile symmetrically using the decrypted keyfile
		$decryptedData = self::symmetricDecryptFileContent( $catfile, $decryptedKeyfile );
		
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
		
			//echo "\n\n\$block = ".substr( $remaining, 0, 6126 );
		
			// Encrypt a chunk of unencrypted data and add it to the rest
			$block = self::symmetricEncryptFileContent( substr( $remaining, 0, 6126 ), $key );
			
			$padded = self::addPadding( $block );
			
			$crypted .= $block;
			
			$testarray[] = $block;
			
			// Remove the data already encrypted from remaining unencrypted data
			$remaining = substr( $remaining, 6126 );
		
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
		
		$decrypted = '';
		
		$remaining = $crypted;
		
		$testarray = array();
		
		while( strlen( $remaining ) ) {
			
			$testarray[] = substr( $remaining, 0, 8192 );
		
			// Decrypt a chunk of unencrypted data and add it to the rest
			$decrypted .= self::symmetricDecryptFileContent( $remaining, $key );
			
			// Remove the data already encrypted from remaining unencrypted data
			$remaining = substr( $remaining, 8192 );
			
		}
		
		//echo "\n\n\$testarray = "; print_r($testarray);
		
		return $decrypted;
		
	}
	
        /**
         * @brief Generates a pseudo random initialisation vector
         * @return String $iv generated IV
         */
	public static function generateIv() {
		
		if ( $random = openssl_random_pseudo_bytes( 12, $strong ) ) {
		
			if ( !$strong ) {
			
				// If OpenSSL indicates randomness is insecure, log error
				\OC_Log::write( 'Encryption library', 'Insecure symmetric key was generated using openssl_random_pseudo_bytes()' , \OC_Log::WARN );
			
			}
			
			// We encode the iv purely for string manipulation 
			// purposes - it gets decoded before use
			$iv = base64_encode( $random );
			
			return $iv;
			
		} else {
		
			throw new Exception( 'Generating IV failed' );
			
		}
		
	}
	
        /**
         * @brief Generate a pseudo random 1024kb ASCII key
         * @returns $key Generated key
         */
	public static function generateKey() {
		
		// Generate key
		if ( $key = base64_encode( openssl_random_pseudo_bytes( 183, $strong ) ) ) {
		
			if ( !$strong ) {
			
				// If OpenSSL indicates randomness is insecure, log error
				throw new Exception ( 'Encryption library, Insecure symmetric key was generated using openssl_random_pseudo_bytes()' );
			
			}
		
			return $key;
			
		} else {
		
			return false;
			
		}
		
	}

	public static function changekeypasscode($oldPassword, $newPassword) {

		if(\OCP\User::isLoggedIn()){
			$key = Keymanager::getPrivateKey( $user, $view );
			if ( ($key = Crypt::symmetricDecryptFileContent($key,$oldpasswd)) ) {
				if ( ($key = Crypt::symmetricEncryptFileContent($key, $newpasswd)) ) {
					Keymanager::setPrivateKey($key);
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * @brief Get the blowfish encryption handeler for a key
	 * @param $key string (optional)
	 * @return Crypt_Blowfish blowfish object
	 *
	 * if the key is left out, the default handeler will be used
	 */
	public static function getBlowfish( $key = '' ) {
	
		if ( $key ) {
		
			return new \Crypt_Blowfish( $key );
		
		} else {
		
			return false;
			
		}
		
	}
	
	public static function legacyCreateKey( $passphrase ) {
	
		// Generate a random integer
		$key = mt_rand( 10000, 99999 ) . mt_rand( 10000, 99999 ) . mt_rand( 10000, 99999 ) . mt_rand( 10000, 99999 );

		// Encrypt the key with the passphrase
		$legacyEncKey = self::legacyEncrypt( $key, $passphrase );

		return $legacyEncKey;
	
	}
	
	/**
	 * @brief encrypts content using legacy blowfish system
	 * @param $content the cleartext message you want to encrypt
	 * @param $key the encryption key (optional)
	 * @returns encrypted content
	 *
	 * This function encrypts an content
	 */
	public static function legacyEncrypt( $content, $passphrase = '' ) {
	
		$bf = self::getBlowfish( $passphrase );
		
		return $bf->encrypt( $content );
		
	}
	
	/**
	* @brief decrypts content using legacy blowfish system
	* @param $content the cleartext message you want to decrypt
	* @param $key the encryption key (optional)
	* @returns cleartext content
	*
	* This function decrypts an content
	*/
	public static function legacyDecrypt( $content, $passphrase = '' ) {
		
		$bf = self::getBlowfish( $passphrase );
		
		$decrypted = $bf->decrypt( $content );
		
		$trimmed = rtrim( $decrypted, "\0" );
		
		return $trimmed;
		
	}
	
	public static function legacyKeyRecryptKeyfile( $legacyEncryptedContent, $legacyPassphrase, $publicKey, $newPassphrase ) {
	
		$decrypted = self::legacyDecrypt( $legacyEncryptedContent, $legacyPassphrase );
	
		$recrypted = self::keyEncryptKeyfile( $decrypted, $publicKey );
		
		return $recrypted;
	
	}
	
	/**
	* @brief Re-encryptes a legacy blowfish encrypted file using AES with integrated IV
	* @param $legacyContent the legacy encrypted content to re-encrypt
	* @returns cleartext content
	*
	* This function decrypts an content
	*/
	public static function legacyRecrypt( $legacyContent, $legacyPassphrase, $newPassphrase ) {
		
		# TODO: write me
	
	}
	
}

?>