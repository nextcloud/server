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
 */
interface IGpg {
	/**
	 * Combination of gnupg_addencryptkey and gnupg_encrypt
	 *
	 * @param string $plaintext
	 * @param array $fingerprints fingerprints of the encryption keys
	 * @return string
	 */
	public function encrypt(array $fingerprints,  $plaintext );


	/**
	 * Combination of gnupg_addsignkey gnupg_addencryptkey and gnupg_encryptsign
	 *
	 * @param string $plaintext
	 * @param array $encrypt_fingerprints fingerprints of the encryption keys
	 * @param array $sign_fingerprints fingerprints of the sign keys
	 * @return string
	 */
	public function encryptsign(array $encrypt_fingerprints, array $sign_fingerprints,  $plaintext);

	/**
	 * Combination of gnupg_addsignkey and gnupg_sign
	 *
	 * @param string $plaintext
	 * @param array $fingerprints fingerprints of the sign keys
	 * @return string
	 */
	public function sign(array $fingerprints,  $plaintext);


	/**
	 * Mapper for gnupg_import,
	 * with expect that only one key per email can be added.
	 *
	 * @param string $keydata
	 * @return array
	 */
	public function import($keydata);

	/**
	 * Mapper for gnupg_export,
	 * exports the public key for finterprint.
	 *
	 * @param string $fingerprint
	 * @return string
	 */
	public function export($fingerprint);


	/**
	 * Mapper for gnupg_keyinfo
	 *
	 * @param string $pattern
	 * @return array
	 */
	public function keyinfo($pattern);

	/**
	 * Mapper for gnupg_deletekey
	 *
	 * Deletes the key from the keyring. If allowsecret is not set or FALSE it will fail on deleting secret keys.

	 * @param string $fingerprint of the key
	 * @param bool $allowsecret
	 * @return bool
	 */
	public function deletekey($fingerprint, $allowsecret=FALSE  );

	/**
	 * Returns the fingerprint of the first public key matching the email.
	 *
	 * @param string $email
	 * @return string
	 */
	public function getPublicKeyFromEmail($email);

	/**
	 * Returns the fingerprint of the first privat key matching the email.
	 *
	 * @param string $email
	 * @return string
	 */
	public function getPrivatKeyFromEmail($email);

	/**
	 * generate a new Key Pair, if no parameter given the key is for the server is generated
	 *
	 * @param string $email = ''
	 * @param string $name = ''
	 * @param string $commend = ''
	 */
	public function generateKey($email = '', $name = '', $commend = '');


	/**
	 * Change the GPG home from nextcloud-data/.gnupg to user-home/.gnugp
	 * Takes an empty string to reset it to nextcloud-data
	 *
	 * @param string $uid
	 * @return $this
	 */
	public function setUser(string $uid);


}