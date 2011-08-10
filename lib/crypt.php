<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @copyright 2010 Frank Karlitschek karlitschek@kde.org
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



// Todo:
//  Crypt/decrypt button in the userinterface
//  transparent decrypt/encrpt in filesystem.php
//  don't use a password directly as encryption key. but a key which is stored on the server and encrypted with the user password. -> password change faster



require_once('Crypt_Blowfish/Blowfish.php');

/**
 * This class is for crypting and decrypting
 */
class OC_Crypt {

        static $encription_extension='.encrypted';

	public static function createkey( $passcode) {
		// generate a random key
		$key=mt_rand(10000,99999).mt_rand(10000,99999).mt_rand(10000,99999).mt_rand(10000,99999);

		// encrypt the key with the passcode of the user
		$enckey=OC_Crypt::encrypt($key,$passcode);

		// Write the file
		file_put_contents( "$SERVERROOT/config/encryption.key", $enckey );
	}

	/**
	 * @brief encrypts an content
	 * @param $content the cleartext message you want to encrypt
	 * @param $key the encryption key
	 * @returns encrypted content
	 *
	 * This function encrypts an content
	 */
	public static function encrypt( $content, $key) {
		$bf = new Crypt_Blowfish($key);
		return($bf->encrypt($contents));
	}


        /**
         * @brief decryption of an content
         * @param $content the cleartext message you want to decrypt
         * @param $key the encryption key
         * @returns cleartext content
         *
         * This function decrypts an content
         */
        public static function decrypt( $content, $key) {
		$bf = new Crypt_Blowfish($key);
		return($bf->encrypt($contents));
        }       


        /**
         * @brief encryption of a file
         * @param $filename
         * @param $key the encryption key
         *
         * This function encrypts a file
         */
	public static function encryptfile( $filename, $key) {
		$handleread  = fopen($filename, "rb");
		if($handleread<>FALSE) {
			$handlewrite = fopen($filename.OC_Crypt::$encription_extension, "wb");
			while (!feof($handleread)) {
				$content = fread($handleread, 8192);
				$enccontent=OC_CRYPT::encrypt( $content, $key);
				fwrite($handlewrite, $enccontent);
			}
			fclose($handlewrite);
			unlink($filename);
		}
		fclose($handleread);
	}


        /**
         * @brief decryption of a file
         * @param $filename
         * @param $key the decryption key
         *
         * This function decrypts a file
         */
	public static function decryptfile( $filename, $key) {
		$handleread  = fopen($filename.OC_Crypt::$encription_extension, "rb");
		if($handleread<>FALSE) {
			$handlewrite = fopen($filename, "wb");
			while (!feof($handleread)) {
				$content = fread($handleread, 8192);
				$enccontent=OC_CRYPT::decrypt( $content, $key);
				fwrite($handlewrite, $enccontent);
			}
			fclose($handlewrite);
			unlink($filename.OC_Crypt::$encription_extension);
		}
		fclose($handleread);
	}




}
