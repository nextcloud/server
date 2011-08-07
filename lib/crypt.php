<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @author Jakob Sack
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

require_once('Crypt_Blowfish/Blowfish.php');

/**
 * This class is for crypting and decrypting
 */
class OC_Crypt {

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





}
