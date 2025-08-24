<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Encryption\Controller;

use OCA\Encryption\Crypto\Crypt;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Session;
use OCA\Encryption\Util;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\AppFramework\Http\DataResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserManager;
use OCP\IUserSession;

class SettingsController extends Controller {

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param IL10N $l
	 * @param IUserManager $userManager
	 * @param IUserSession $userSession
	 * @param KeyManager $keyManager
	 * @param Crypt $crypt
	 * @param Session $session
	 * @param ISession $ocSession
	 * @param Util $util
	 */
	public function __construct(
		$AppName,
		IRequest $request,
		private IL10N $l,
		private IUserManager $userManager,
		private IUserSession $userSession,
		private KeyManager $keyManager,
		private Crypt $crypt,
		private Session $session,
		private ISession $ocSession,
		private Util $util,
	) {
		parent::__construct($AppName, $request);
	}


	/**
	 * @param string $oldPassword
	 * @param string $newPassword
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	#[UseSession]
	public function updatePrivateKeyPassword($oldPassword, $newPassword) {
		$result = false;
		$uid = $this->userSession->getUser()->getUID();
		$errorMessage = $this->l->t('Could not update the private key password.');

		//check if password is correct
		$passwordCorrect = $this->userManager->checkPassword($uid, $newPassword);
		if ($passwordCorrect === false) {
			// if check with uid fails we need to check the password with the login name
			// e.g. in the ldap case. For local user we need to check the password with
			// the uid because in this case the login name is case insensitive
			$loginName = $this->ocSession->get('loginname');
			$passwordCorrect = $this->userManager->checkPassword($loginName, $newPassword);
		}

		if ($passwordCorrect !== false) {
			$encryptedKey = $this->keyManager->getPrivateKey($uid);
			$decryptedKey = $this->crypt->decryptPrivateKey($encryptedKey, $oldPassword, $uid);

			if ($decryptedKey) {
				$encryptedKey = $this->crypt->encryptPrivateKey($decryptedKey, $newPassword, $uid);
				$header = $this->crypt->generateHeader();
				if ($encryptedKey) {
					$this->keyManager->setPrivateKey($uid, $header . $encryptedKey);
					$this->session->setPrivateKey($decryptedKey);
					$result = true;
				}
			} else {
				$errorMessage = $this->l->t('The old password was not correct, please try again.');
			}
		} else {
			$errorMessage = $this->l->t('The current log-in password was not correct, please try again.');
		}

		if ($result === true) {
			$this->session->setStatus(Session::INIT_SUCCESSFUL);
			return new DataResponse(
				['message' => $this->l->t('Private key password successfully updated.')]
			);
		} else {
			return new DataResponse(
				['message' => $errorMessage],
				Http::STATUS_BAD_REQUEST
			);
		}
	}

	/**
	 * @param bool $encryptHomeStorage
	 * @return DataResponse
	 */
	#[UseSession]
	public function setEncryptHomeStorage($encryptHomeStorage) {
		$this->util->setEncryptHomeStorage($encryptHomeStorage);
		return new DataResponse();
	}
}
