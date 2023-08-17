<?php
/**
 * @copyright Copyright (c) 2016 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Matthew Setter <matthew@matthewsetter.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
// FIXME: disabled for now to be able to inject IGroupManager and also use
// getSubAdmin()
//declare(strict_types=1);

namespace OCA\Settings\Controller;

use OC\Group\Manager as GroupManager;
use OC\User\Session;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\HintException;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;

class ChangePasswordController extends Controller {
	private ?string $userId;
	private IUserManager $userManager;
	private IL10N $l;
	private GroupManager $groupManager;
	private Session $userSession;
	private IAppManager $appManager;

	public function __construct(string $appName,
								IRequest $request,
								?string $userId,
								IUserManager $userManager,
								IUserSession $userSession,
								IGroupManager $groupManager,
								IAppManager $appManager,
								IL10N $l) {
		parent::__construct($appName, $request);

		$this->userId = $userId;
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
		$this->appManager = $appManager;
		$this->l = $l;
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 * @BruteForceProtection(action=changePersonalPassword)
	 */
	public function changePersonalPassword(string $oldpassword = '', string $newpassword = null): JSONResponse {
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

	/**
	 * @NoAdminRequired
	 * @PasswordConfirmationRequired
	 */
	public function changeUserPassword(string $username = null, string $password = null, string $recoveryPassword = null): JSONResponse {
		if ($username === null) {
			return new JSONResponse([
				'status' => 'error',
				'data' => [
					'message' => $this->l->t('No user supplied'),
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
		if ($currentUser === null || $targetUser === null ||
			!($this->groupManager->isAdmin($this->userId) ||
			 $this->groupManager->getSubAdmin()->isUserAccessible($currentUser, $targetUser))
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
			$keyManager = \OCP\Server::get(\OCA\Encryption\KeyManager::class);
			$recovery = \OCP\Server::get(\OCA\Encryption\Recovery::class);
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
						'message' => $this->l->t('Please provide an admin recovery password; otherwise, all user data will be lost.'),
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
							'message' => $this->l->t('Backend does not support password change, but the user\'s encryption key was updated.'),
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
