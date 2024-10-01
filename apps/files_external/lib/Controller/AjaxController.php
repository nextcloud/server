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
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;

class AjaxController extends Controller {
	/** @var RSA */
	private $rsaMechanism;
	/** @var GlobalAuth */
	private $globalAuth;
	/** @var IUserSession */
	private $userSession;
	/** @var IGroupManager */
	private $groupManager;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param RSA $rsaMechanism
	 * @param GlobalAuth $globalAuth
	 * @param IUserSession $userSession
	 * @param IGroupManager $groupManager
	 */
	public function __construct($appName,
		IRequest $request,
		RSA $rsaMechanism,
		GlobalAuth $globalAuth,
		IUserSession $userSession,
		IGroupManager $groupManager) {
		parent::__construct($appName, $request);
		$this->rsaMechanism = $rsaMechanism;
		$this->globalAuth = $globalAuth;
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
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
		return new JSONResponse(
			['data' => [
				'private_key' => $key['privatekey'],
				'public_key' => $key['publickey']
			],
				'status' => 'success'
			]);
	}

	/**
	 * @param string $uid
	 * @param string $user
	 * @param string $password
	 * @return bool
	 */
	#[NoAdminRequired]
	public function saveGlobalCredentials($uid, $user, $password) {
		$currentUser = $this->userSession->getUser();
		if ($currentUser === null) {
			return false;
		}

		// Non-admins can only edit their own credentials
		// Admin can edit global credentials
		$allowedToEdit = $uid === ''
			? $this->groupManager->isAdmin($currentUser->getUID())
			: $currentUser->getUID() === $uid;

		if ($allowedToEdit) {
			$this->globalAuth->saveAuth($uid, $user, $password);
			return true;
		}

		return false;
	}
}
