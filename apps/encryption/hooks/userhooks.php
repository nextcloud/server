<?php
/**
 * @author Clark Tomlinson  <clark@owncloud.com>
 * @since 2/19/15, 10:02 AM
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Encryption\Hooks;


use OCA\Encryption\Hooks\Contracts\IHook;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Migrator;
use OCA\Encryption\RequirementsChecker;
use OCA\Encryption\Users\Setup;
use OCP\App;
use OCP\ILogger;
use OCP\IUserSession;
use OCP\Util;
use Test\User;

class UserHooks implements IHook {
	/**
	 * @var KeyManager
	 */
	private $keyManager;
	/**
	 * @var ILogger
	 */
	private $logger;
	/**
	 * @var Setup
	 */
	private $userSetup;
	/**
	 * @var Migrator
	 */
	private $migrator;
	/**
	 * @var IUserSession
	 */
	private $user;

	/**
	 * UserHooks constructor.
	 *
	 * @param KeyManager $keyManager
	 * @param ILogger $logger
	 * @param Setup $userSetup
	 * @param Migrator $migrator
	 * @param IUserSession $user
	 */
	public function __construct(
		KeyManager $keyManager, ILogger $logger, Setup $userSetup, Migrator $migrator, IUserSession $user) {

		$this->keyManager = $keyManager;
		$this->logger = $logger;
		$this->userSetup = $userSetup;
		$this->migrator = $migrator;
		$this->user = $user;
	}

	/**
	 * Connects Hooks
	 *
	 * @return null
	 */
	public function addHooks() {
		Util::connectHook('OC_User', 'post_login', $this, 'login');
		Util::connectHook('OC_User', 'logout', $this, 'logout');
		Util::connectHook('OC_User', 'post_setPassword', $this, 'setPassphrase');
		Util::connectHook('OC_User', 'pre_setPassword', $this, 'preSetPassphrase');
		Util::connectHook('OC_User', 'post_createUser', $this, 'postCreateUser');
		Util::connectHook('OC_User', 'post_deleteUser', $this, 'postDeleteUser');
	}


	/**
	 * Startup encryption backend upon user login
	 *
	 * @note This method should never be called for users using client side encryption
	 */
	public function login($params) {

		if (!App::isEnabled('encryption')) {
			return true;
		}

		// ensure filesystem is loaded
		// Todo: update?
		if (!\OC\Files\Filesystem::$loaded) {
			\OC_Util::setupFS($params['uid']);
		}

		// setup user, if user not ready force relogin
		if (!$this->userSetup->setupUser($params['password'])) {
			return false;
		}

		$cache = $this->keyManager->init();

		// Check if first-run file migration has already been performed
		$ready = false;
		$migrationStatus = $this->migrator->getStatus($params['uid']);
		if ($migrationStatus === Migrator::$migrationOpen && $cache !== false) {
			$ready = $this->migrator->beginMigration();
		} elseif ($migrationStatus === Migrator::$migrationInProgress) {
			// refuse login as long as the initial encryption is running
			sleep(5);
			$this->user->logout();
			return false;
		}

		$result = true;

		// If migration not yet done
		if ($ready) {

			// Encrypt existing user files
			try {
				$result = $util->encryptAll('/' . $params['uid'] . '/' . 'files');
			} catch (\Exception $ex) {
				\OCP\Util::writeLog('Encryption library', 'Initial encryption failed! Error: ' . $ex->getMessage(), \OCP\Util::FATAL);
				$result = false;
			}

			if ($result) {
				\OC_Log::write(
					'Encryption library', 'Encryption of existing files belonging to "' . $params['uid'] . '" completed'
					, \OC_Log::INFO
				);
				// Register successful migration in DB
				$util->finishMigration();
			} else {
				\OCP\Util::writeLog('Encryption library', 'Initial encryption failed!', \OCP\Util::FATAL);
				$util->resetMigrationStatus();
				\OCP\User::logout();
			}
		}

		return $result;
	}

	/**
	 * remove keys from session during logout
	 */
	public function logout() {
		$session = new Session(new \OC\Files\View());
		$session->removeKeys();
	}

	/**
	 * setup encryption backend upon user created
	 *
	 * @note This method should never be called for users using client side encryption
	 */
	public function postCreateUser($params) {

		if (App::isEnabled('files_encryption')) {
			$view = new \OC\Files\View('/');
			$util = new Util($view, $params['uid']);
			Helper::setupUser($util, $params['password']);
		}
	}

	/**
	 * cleanup encryption backend upon user deleted
	 *
	 * @note This method should never be called for users using client side encryption
	 */
	public function postDeleteUser($params) {

		if (App::isEnabled('files_encryption')) {
			Keymanager::deletePublicKey(new \OC\Files\View(), $params['uid']);
		}
	}

	/**
	 * If the password can't be changed within ownCloud, than update the key password in advance.
	 */
	public function preSetPassphrase($params) {
		if (App::isEnabled('files_encryption')) {
			if (!\OC_User::canUserChangePassword($params['uid'])) {
				self::setPassphrase($params);
			}
		}
	}

	/**
	 * Change a user's encryption passphrase
	 *
	 * @param array $params keys: uid, password
	 */
	public function setPassphrase($params) {
		if (App::isEnabled('files_encryption') === false) {
			return true;
		}

		// Only attempt to change passphrase if server-side encryption
		// is in use (client-side encryption does not have access to
		// the necessary keys)
		if (Crypt::mode() === 'server') {

			$view = new \OC\Files\View('/');
			$session = new Session($view);

			// Get existing decrypted private key
			$privateKey = $session->getPrivateKey();

			if ($params['uid'] === \OCP\User::getUser() && $privateKey) {

				// Encrypt private key with new user pwd as passphrase
				$encryptedPrivateKey = Crypt::symmetricEncryptFileContent($privateKey, $params['password'], Helper::getCipher());

				// Save private key
				if ($encryptedPrivateKey) {
					Keymanager::setPrivateKey($encryptedPrivateKey, \OCP\User::getUser());
				} else {
					\OCP\Util::writeLog('files_encryption', 'Could not update users encryption password', \OCP\Util::ERROR);
				}

				// NOTE: Session does not need to be updated as the
				// private key has not changed, only the passphrase
				// used to decrypt it has changed


			} else { // admin changed the password for a different user, create new keys and reencrypt file keys

				$user = $params['uid'];
				$util = new Util($view, $user);
				$recoveryPassword = isset($params['recoveryPassword']) ? $params['recoveryPassword'] : null;

				// we generate new keys if...
				// ...we have a recovery password and the user enabled the recovery key
				// ...encryption was activated for the first time (no keys exists)
				// ...the user doesn't have any files
				if (($util->recoveryEnabledForUser() && $recoveryPassword)
					|| !$util->userKeysExists()
					|| !$view->file_exists($user . '/files')
				) {

					// backup old keys
					$util->backupAllKeys('recovery');

					$newUserPassword = $params['password'];

					// make sure that the users home is mounted
					\OC\Files\Filesystem::initMountPoints($user);

					$keypair = Crypt::createKeypair();

					// Disable encryption proxy to prevent recursive calls
					$proxyStatus = \OC_FileProxy::$enabled;
					\OC_FileProxy::$enabled = false;

					// Save public key
					Keymanager::setPublicKey($keypair['publicKey'], $user);

					// Encrypt private key with new password
					$encryptedKey = Crypt::symmetricEncryptFileContent($keypair['privateKey'], $newUserPassword, Helper::getCipher());
					if ($encryptedKey) {
						Keymanager::setPrivateKey($encryptedKey, $user);

						if ($recoveryPassword) { // if recovery key is set we can re-encrypt the key files
							$util = new Util($view, $user);
							$util->recoverUsersFiles($recoveryPassword);
						}
					} else {
						\OCP\Util::writeLog('files_encryption', 'Could not update users encryption password', \OCP\Util::ERROR);
					}

					\OC_FileProxy::$enabled = $proxyStatus;
				}
			}
		}
	}

	/**
	 * after password reset we create a new key pair for the user
	 *
	 * @param array $params
	 */
	public function postPasswordReset($params) {
		$uid = $params['uid'];
		$password = $params['password'];

		$util = new Util(new \OC\Files\View(), $uid);
		$util->replaceUserKeys($password);
	}
}
