<?php
/**
 * ownCloud
 *
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * @author Sam Tuke <samtuke@owncloud.com>
 * @author Bjoern Schiessle <schiessle@owncloud.com>
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

namespace OCA\Files_Encryption;

/**
 * Class for handling encryption related session data
 */

class Session {

	private $view;
	private static $publicShareKey = false;

	const NOT_INITIALIZED = '0';
	const INIT_EXECUTED = '1';
	const INIT_SUCCESSFUL = '2';


	/**
	 * if session is started, check if ownCloud key pair is set up, if not create it
	 * @param \OC\Files\View $view
	 *
	 * @note The ownCloud key pair is used to allow public link sharing even if encryption is enabled
	 */
	public function __construct($view) {

		$this->view = $view;

		if (!$this->view->is_dir('files_encryption')) {

			$this->view->mkdir('files_encryption');

		}

		$appConfig = \OC::$server->getAppConfig();

		$publicShareKeyId = Helper::getPublicShareKeyId();

		if ($publicShareKeyId === false) {
			$publicShareKeyId = 'pubShare_' . substr(md5(time()), 0, 8);
			$appConfig->setValue('files_encryption', 'publicShareKeyId', $publicShareKeyId);
		}

		if (!Keymanager::publicShareKeyExists($view)) {

			$keypair = Crypt::createKeypair();


			// Save public key
			Keymanager::setPublicKey($keypair['publicKey'], $publicShareKeyId);

			// Encrypt private key empty passphrase
			$cipher = Helper::getCipher();
			$encryptedKey = Crypt::symmetricEncryptFileContent($keypair['privateKey'], '', $cipher);
			if ($encryptedKey) {
				Keymanager::setPrivateSystemKey($encryptedKey, $publicShareKeyId);
			} else {
				\OCP\Util::writeLog('files_encryption', 'Could not create public share keys', \OCP\Util::ERROR);
			}

		}

		if (Helper::isPublicAccess() && !self::getPublicSharePrivateKey()) {
			// Disable encryption proxy to prevent recursive calls
			$proxyStatus = \OC_FileProxy::$enabled;
			\OC_FileProxy::$enabled = false;

			$encryptedKey = Keymanager::getPrivateSystemKey($publicShareKeyId);
			$privateKey = Crypt::decryptPrivateKey($encryptedKey, '');
			self::setPublicSharePrivateKey($privateKey);

			\OC_FileProxy::$enabled = $proxyStatus;
		}
	}

	/**
	 * Sets user private key to session
	 * @param string $privateKey
	 * @return bool
	 *
	 * @note this should only be set on login
	 */
	public function setPrivateKey($privateKey) {

		\OC::$server->getSession()->set('privateKey', $privateKey);

		return true;

	}

	/**
	 * remove keys from session
	 */
	public function removeKeys() {
		\OC::$server->getSession()->remove('publicSharePrivateKey');
		\OC::$server->getSession()->remove('privateKey');
	}

	/**
	 * Sets status of encryption app
	 * @param string $init INIT_SUCCESSFUL, INIT_EXECUTED, NOT_INITIALIZED
	 * @return bool
	 *
	 * @note this doesn not indicate of the init was successful, we just remeber the try!
	 */
	public function setInitialized($init) {

		\OC::$server->getSession()->set('encryptionInitialized', $init);

		return true;

	}

	/**
	 * remove encryption keys and init status from session
	 */
	public function closeSession() {
		\OC::$server->getSession()->remove('encryptionInitialized');
		\OC::$server->getSession()->remove('privateKey');
	}


	/**
	 * Gets status if we already tried to initialize the encryption app
	 * @return string init status INIT_SUCCESSFUL, INIT_EXECUTED, NOT_INITIALIZED
	 *
	 * @note this doesn not indicate of the init was successful, we just remeber the try!
	 */
	public function getInitialized() {
		if (!is_null(\OC::$server->getSession()->get('encryptionInitialized'))) {
			return \OC::$server->getSession()->get('encryptionInitialized');
		} else if (Helper::isPublicAccess() && self::getPublicSharePrivateKey()) {
			return self::INIT_SUCCESSFUL;
		} else {
			return self::NOT_INITIALIZED;
		}
	}

	/**
	 * Gets user or public share private key from session
	 * @return string $privateKey The user's plaintext private key
	 *
	 */
	public function getPrivateKey() {
		// return the public share private key if this is a public access
		if (Helper::isPublicAccess()) {
			return self::getPublicSharePrivateKey();
		} else {
			if (!is_null(\OC::$server->getSession()->get('privateKey'))) {
				return \OC::$server->getSession()->get('privateKey');
			} else {
				return false;
			}
		}
	}

	/**
	 * Sets public user private key to session
	 * @param string $privateKey
	 * @return bool
	 */
	private static function setPublicSharePrivateKey($privateKey) {
		self::$publicShareKey = $privateKey;
		return true;
	}

	/**
	 * Gets public share private key from session
	 * @return string $privateKey
	 *
	 */
	private static function getPublicSharePrivateKey() {
		return self::$publicShareKey;
	}

}
