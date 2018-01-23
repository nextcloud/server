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

namespace OCP;

/**
 * Interface IGpg
 *
 * @package OCP
 * @since 14.0.0
 */
interface IGpg {
	/**
	 * Combination of gnupg_addencryptkey and gnupg_encrypt
	 *
	 * @param string $plaintext
	 * @param array $fingerprints fingerprints of the encryption keys
	 * @param $uid = null
	 * @return string
	 * @since 14.0.0
	 */
	public function encrypt(array $fingerprints,  $plaintext, $uid = null);


	/**
	 * Combination of gnupg_addsignkey gnupg_addencryptkey and gnupg_encryptsign
	 *
	 * @param string $plaintext
	 * @param array $encrypt_fingerprints fingerprints of the encryption keys
	 * @param array $sign_fingerprints passphrase can be passed as $sign_fingerprint => $passphrase fingerprints of the sign keys
	 * @param $uid = null
	 * @return string
	 * @since 14.0.0
	 */
	public function encryptsign(array $encrypt_fingerprints, array $sign_fingerprints,  $plaintext, $uid = null);

	/**
	 * Combination of gnupg_addsignkey and gnupg_sign
	 *
	 * @param string $plaintext
	 * @param array $fingerprints passphrase can be passed as $fingerprint => $passphrase fingerprints of the sign keys
	 * @param $uid = null
	 * @return string
	 * @since 14.0.0
	 */
	public function sign(array $fingerprints,  $plaintext, $uid = null);


	/**
	 * Mapper for gnupg_import,
	 * with expect that only one key per email can be added.
	 *
	 * @param string $keydata
	 * @param $uid = null
	 * @return array
	 * @since 14.0.0
	 */
	public function import($keydata, $uid = null);

	/**
	 * Mapper for gnupg_export,
	 * exports the public key for finterprint.
	 *
	 * @param string $fingerprint
	 * @param $uid = null
	 * @return string
	 * @since 14.0.0
	 */
	public function export($fingerprint, $uid = null);


	/**
	 * Mapper for gnupg_keyinfo
	 *
	 * @param string $pattern
	 * @param $uid = null
	 * @return array
	 * @since 14.0.0
	 */
	public function keyinfo($pattern, $uid = null);

	/**
	 * Mapper for gnupg_deletekey
	 *
	 * Deletes the key from the keyring. If allowsecret is not set or FALSE it will fail on deleting secret keys.

	 * @param string $fingerprint of the key
	 * @param $uid = null
	 * @param bool $allowsecret
	 * @return bool
	 * @since 14.0.0
	 */
	public function deletekey($fingerprint, $uid = null, $allowsecret = FALSE );

	/**
	 * Returns the fingerprint of the first public key matching the email.
	 *
	 * @param string $email
	 * @param $uid = null
	 * @return string
	 * @since 14.0.0
	 */
	public function getPublicKeyFromEmail($email, $uid = null);

	/**
	 * Returns the fingerprint of the first privat key matching the email.
	 *
	 * @param string $email
	 * @param $uid = null
	 * @return string
	 * @since 14.0.0
	 */
	public function getPrivatKeyFromEmail($email, $uid = null);

	/**
	 * generate a new Key Pair, if no parameter given the key is for the server is generated
	 *
	 * @param string $email = ''
	 * @param string $name = ''
	 * @param string $commend = ''
	 * @param $uid = null
	 * @since 14.0.0
	 */
	public function generateKey($email = '', $name = '', $commend = '', $uid = null);




}