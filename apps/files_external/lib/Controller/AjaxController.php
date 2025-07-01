<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Controller;

use OCA\Files_External\Lib\Auth\Password\GlobalAuth;
use OCA\Files_External\Lib\Auth\PublicKey\RSA;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;

class AjaxController extends Controller {
	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param RSA $rsaMechanism
	 * @param GlobalAuth $globalAuth
	 * @param IUserSession $userSession
	 * @param IGroupManager $groupManager
	 */
	public function __construct(
		$appName,
		IRequest $request,
		private RSA $rsaMechanism,
		private GlobalAuth $globalAuth,
		private IUserSession $userSession,
		private IGroupManager $groupManager,
		private IL10N $l10n,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @param int $keyLength
	 * @return array
	 */
	private function generateSshKeys($keyLength) {
		$key = $this->rsaMechanism->createKey($keyLength);
		// Replace the placeholder label with a more meaningful one
		$key['publickey'] = str_replace('phpseclib-generated-key', gethostname(), $key['publickey']);

		return $key;
	}

	/**
	 * Generates an SSH public/private key pair.
	 *
	 * @param int $keyLength
	 */
	#[NoAdminRequired]
	public function getSshKeys($keyLength = 1024) {
		$key = $this->generateSshKeys($keyLength);
		return new JSONResponse([
			'data' => [
				'private_key' => $key['privatekey'],
				'public_key' => $key['publickey']
			],
			'status' => 'success',
		]);
	}

	/**
	 * @param string $uid
	 * @param string $user
	 * @param string $password
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	#[PasswordConfirmationRequired(strict: true)]
	public function saveGlobalCredentials($uid, $user, $password): JSONResponse {
		$currentUser = $this->userSession->getUser();
		if ($currentUser === null) {
			return new JSONResponse([
				'status' => 'error',
				'message' => $this->l10n->t('You are not logged in'),
			], Http::STATUS_UNAUTHORIZED);
		}

		// Non-admins can only edit their own credentials
		// Admin can edit global credentials
		$allowedToEdit = $uid === ''
			? $this->groupManager->isAdmin($currentUser->getUID())
			: $currentUser->getUID() === $uid;

		if ($allowedToEdit) {
			$this->globalAuth->saveAuth($uid, $user, $password);
			return new JSONResponse([
				'status' => 'success',
			]);
		}

		return new JSONResponse([
			'status' => 'success',
			'message' => $this->l10n->t('Permission denied'),
		], Http::STATUS_FORBIDDEN);
	}
}
