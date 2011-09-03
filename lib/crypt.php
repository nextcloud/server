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
//  - Crypt/decrypt button in the userinterface
//  - Setting if crypto should be on by default
//  - Add a setting "Don´t encrypt files larger than xx because of performance reasons"
//  - Transparent decrypt/encrpt in filesystem.php. Autodetect if a file is encrypted (.encrypted extensio)
//  - Don't use a password directly as encryption key. but a key which is stored on the server and encrypted with the user password. -> password change faster
//  - IMPORTANT! Check if the block lenght of the encrypted data stays the same


require_once('Crypt_Blowfish/Blowfish.php');

/**
 * This class is for crypting and decrypting
 */
class OC_Crypt {

        static $encription_extension='.encrypted';

	public static function init($login,$password) {
		$_SESSION['user_password'] = $password;  // save the password as passcode for the encryption
		if(OC_User::isLoggedIn()){
			// does key exist?
			if(!file_exists(OC_Config::getValue( "datadirectory").'/'.$login.'/encryption.key')){
				OC_Crypt::createkey($_SESSION['user_password']);
			}
		}
	}



	public static function createkey($passcode) {
		if(OC_User::isLoggedIn()){
			// generate a random key
			$key=mt_rand(10000,99999).mt_rand(10000,99999).mt_rand(10000,99999).mt_rand(10000,99999);

			// encrypt the key with the passcode of the user
			$enckey=OC_Crypt::encrypt($key,$passcode);

			// Write the file
		        $username=OC_USER::getUser();
			@file_put_contents(OC_Config::getValue( "datadirectory").'/'.$username.'/encryption.key', $enckey );
		}
	}

	public static function changekeypasscode( $newpasscode) {
		if(OC_User::isLoggedIn()){
		        $username=OC_USER::getUser();

			// read old key
			$key=file_get_contents(OC_Config::getValue( "datadirectory").'/'.$username.'/encryption.key');

			// decrypt key with old passcode
			$key=OC_Crypt::decrypt($key, $_SESSION['user_password']);

			// encrypt again with new passcode
			$key=OC_Crypt::encrypt($key,$newpassword);

			// store the new key
			file_put_contents(OC_Config::getValue( "datadirectory").'/'.$username.'/encryption.key', $key );

			 $_SESSION['user_password']=$newpasscode;
		}
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
		return($bf->encrypt($content));
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
