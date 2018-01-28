<?php
/**
 * @copyright Copyright (c) 2017 Arne Hamann <kontakt+github@arne.email>
 *
 * @author Arne Hamann <kontakt+github@arne.email>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OC;

use OCP\Defaults;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IGpg;
use OCP\IURLGenerator;
use OCP\IL10N;
use OCP\IUserManager;
use OCP\Util;

use gnupg;


class GpgDummy implements IGpg{

	/**
	 * GpgDummy constructor.
	 *
	 */
	public function __construct() {
	}

	/**
	 * Combination of gnupg_addencryptkey and gnupg_encrypt
	 *
	 * @param string $plaintext
	 * @param array $fingerprints fingerprints of the encryption keys
	 * @param string $uid = null
	 * @return string
	 */
	public function encrypt(array $fingerprints,  $plaintext, $uid = null ) {

		return $plaintext;
	}


	/**
	 * Combination of gnupg_addsignkey gnupg_addencryptkey and gnupg_encryptsign
	 *
	 * @param string $plaintext
	 * @param array $encrypt_fingerprints fingerprints of the encryption keys
	 * @param array $sign_fingerprints fingerprints of the sign keys
	 * @param $uid = null
	 * @return string
	 */
	public function encryptsign(array $encrypt_fingerprints, array $sign_fingerprints,  $plaintext, $uid = null ) {

		return $plaintext;
	}

	/**
	 * Combination of gnupg_addsignkey and gnupg_sign
	 *
	 * @param string $plaintext
	 * @param array $fingerprints fingerprints of the sign keys
	 * @param $uid = null
	 * @return string
	 */
	public function sign(array $fingerprints,  $plaintext, $uid = null ) {

		return $plaintext;
	}


	/**
	 * Mapper for gnupg_import,
	 * with expect that only one key per email can be added.
	 *
	 * @param string $keydata
	 * @return array
	 */
	public function import($keydata, $uid = null ) {
		return [
			 'fingerprint' => '',
		];
	}

	/**
	 * Mapper for gnupg_export,
	 * exports the public key for finterprint.
	 *
	 * @param string $fingerprint
	 * @return string
	 */
	public function export($fingerprint, $uid = null ) {

		return '';
	}


	/**
	 * Mapper for gnupg_keyinfo
	 *
	 * @param string $pattern
	 * @return array
	 */
	public function keyinfo($pattern, $uid = null ) {

		return [];
	}

	/**
	 * Mapper for gnupg_deletekey
	 *
	 * Deletes the key from the keyring. If allowsecret is not set or FALSE it will fail on deleting secret keys.

	 * @param string $fingerprint of the key
	 * @param string|null $uid
	 * @param bool $allowsecret
	 * @return bool
	 */
	public function deletekey($fingerprint, $uid = null, $allowsecret = FALSE ) {

		return True;
	}

	/**
	 * Returns the fingerprint of the first public key matching the email.
	 *
	 * @param string $email
	 * @return string
	 */
	public function getPublicKeyFromEmail($email, $uid = null ) {

		return '';
	}

	/**
	 * Returns the fingerprint of the first privat key matching the email.
	 *
	 * @param string $email
	 * @return string
	 */
	public function getPrivatKeyFromEmail($email, $uid = null ) {

		return '';
	}

	/**
	 * generate a new Key Pair, if no parameter given the key is for the server is generated
	 *
	 * @param string $email = ''
	 * @param string $name = ''
	 * @param string $commend = ''
	 * @return string $fingerprint
	 */
	public function generateKey($email = '', $name = '', $commend = '', $uid = null ) {

		return '';
	}
}