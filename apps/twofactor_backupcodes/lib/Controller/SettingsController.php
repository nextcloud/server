<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Controller;

use OCA\TwoFactorBackupCodes\Service\BackupCodeStorage;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

class SettingsController extends Controller {

	/** @var BackupCodeStorage */
	private $storage;

	/** @var IUserSession */
	private $userSession;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param BackupCodeStorage $storage
	 * @param IUserSession $userSession
	 */
	public function __construct($appName, IRequest $request, BackupCodeStorage $storage, IUserSession $userSession) {
		parent::__construct($appName, $request);
		$this->userSession = $userSession;
		$this->storage = $storage;
	}

	/**
	 * @NoAdminRequired
	 * @PasswordConfirmationRequired
	 *
	 * @return JSONResponse
	 */
	public function createCodes(): JSONResponse {
		$user = $this->userSession->getUser();
		$codes = $this->storage->createCodes($user);
		return new JSONResponse([
			'codes' => $codes,
			'state' => $this->storage->getBackupCodesState($user),
		]);
	}
}
