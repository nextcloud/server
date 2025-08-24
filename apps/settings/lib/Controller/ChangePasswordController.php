<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
// FIXME: disabled for now to be able to inject IGroupManager and also use
// getSubAdmin()
//declare(strict_types=1);

namespace OCA\Settings\Controller;

use OC\Group\Manager as GroupManager;
use OC\User\Session;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Recovery;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\HintException;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Server;

class ChangePasswordController extends Controller {
	private Session $userSession;

	public function __construct(
		string $appName,
		IRequest $request,
		private ?string $userId,
		private IUserManager $userManager,
		IUserSession $userSession,
		private GroupManager $groupManager,
		private IAppManager $appManager,
		private IL10N $l,
	) {
		parent::__construct($appName, $request);
		$this->userSession = $userSession;
	}

	/**
	 * @NoSubAdminRequired
	 */
	#[NoAdminRequired]
	#[BruteForceProtection(action: 'changePersonalPassword')]
	public function changePersonalPassword(string $oldpassword = '', ?string $newpassword = null): JSONResponse {
		$loginName = $this->userSession->getLoginName();
		/** @var IUser $user */
		$user = $this->userManager->checkPassword($loginName, $oldpassword);
		if ($user === false) {
			$response = new JSONResponse([
				'status' => 'error',
				'data' => [
					'message' => $this->l->t('Wrong password'),
				],
			]);
			$response->throttle();
			return $response;
		}

		try {
			if ($newpassword === null || strlen($newpassword) > IUserManager::MAX_PASSWORD_LENGTH || $user->setPassword($newpassword) === false) {
				return new JSONResponse([
					'status' => 'error',
					'data' => [
						'message' => $this->l->t('Unable to change personal password'),
					],
				]);
			}
			// password policy app throws exception
		} catch (HintException $e) {
			return new JSONResponse([
				'status' => 'error',
				'data' => [
					'message' => $e->getHint(),
				],
			]);
		}

		$this->userSession->updateSessionTokenPassword($newpassword);

		return new JSONResponse([
			'status' => 'success',
			'data' => [
				'message' => $this->l->t('Saved'),
			],
		]);
	}

	#[NoAdminRequired]
	#[PasswordConfirmationRequired]
	public function changeUserPassword(?string $username = null, ?string $password = null, ?string $recoveryPassword = null): JSONResponse {
		if ($username === null) {
			return new JSONResponse([
				'status' => 'error',
				'data' => [
					'message' => $this->l->t('No Login supplied'),
				],
			]);
		}

		if ($password === null) {
			return new JSONResponse([
				'status' => 'error',
				'data' => [
					'message' => $this->l->t('Unable to change password'),
				],
			]);
		}

		if (strlen($password) > IUserManager::MAX_PASSWORD_LENGTH) {
			return new JSONResponse([
				'status' => 'error',
				'data' => [
					'message' => $this->l->t('Unable to change password. Password too long.'),
				],
			]);
		}

		$currentUser = $this->userSession->getUser();
		$targetUser = $this->userManager->get($username);
		if ($currentUser === null || $targetUser === null
			|| !($this->groupManager->isAdmin($this->userId)
			 || $this->groupManager->getSubAdmin()->isUserAccessible($currentUser, $targetUser))
		) {
			return new JSONResponse([
				'status' => 'error',
				'data' => [
					'message' => $this->l->t('Authentication error'),
				],
			]);
		}

		if ($this->appManager->isEnabledForUser('encryption')) {
			//handle the recovery case
			$keyManager = Server::get(KeyManager::class);
			$recovery = Server::get(Recovery::class);
			$recoveryAdminEnabled = $recovery->isRecoveryKeyEnabled();

			$validRecoveryPassword = false;
			$recoveryEnabledForUser = false;
			if ($recoveryAdminEnabled) {
				$validRecoveryPassword = $keyManager->checkRecoveryPassword($recoveryPassword);
				$recoveryEnabledForUser = $recovery->isRecoveryEnabledForUser($username);
			}

			if ($recoveryEnabledForUser && $recoveryPassword === '') {
				return new JSONResponse([
					'status' => 'error',
					'data' => [
						'message' => $this->l->t('Please provide an admin recovery password; otherwise, all account data will be lost.'),
					]
				]);
			} elseif ($recoveryEnabledForUser && ! $validRecoveryPassword) {
				return new JSONResponse([
					'status' => 'error',
					'data' => [
						'message' => $this->l->t('Wrong admin recovery password. Please check the password and try again.'),
					]
				]);
			} else { // now we know that everything is fine regarding the recovery password, let's try to change the password
				try {
					$result = $targetUser->setPassword($password, $recoveryPassword);
					// password policy app throws exception
				} catch (HintException $e) {
					return new JSONResponse([
						'status' => 'error',
						'data' => [
							'message' => $e->getHint(),
						],
					]);
				}
				if (!$result && $recoveryEnabledForUser) {
					return new JSONResponse([
						'status' => 'error',
						'data' => [
							'message' => $this->l->t('Backend does not support password change, but the encryption of the account key was updated.'),
						]
					]);
				} elseif (!$result && !$recoveryEnabledForUser) {
					return new JSONResponse([
						'status' => 'error',
						'data' => [
							'message' => $this->l->t('Unable to change password'),
						]
					]);
				}
			}
		} else {
			try {
				if ($targetUser->setPassword($password) === false) {
					return new JSONResponse([
						'status' => 'error',
						'data' => [
							'message' => $this->l->t('Unable to change password'),
						],
					]);
				}
				// password policy app throws exception
			} catch (HintException $e) {
				return new JSONResponse([
					'status' => 'error',
					'data' => [
						'message' => $e->getHint(),
					],
				]);
			}
		}

		return new JSONResponse([
			'status' => 'success',
			'data' => [
				'username' => $username,
			],
		]);
	}
}
