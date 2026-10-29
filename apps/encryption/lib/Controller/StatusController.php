<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Encryption\Controller;

use OCA\Encryption\Session;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\Encryption\IManager;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IRequest;

class StatusController extends Controller {

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param IL10N $l
	 * @param Session $session
	 * @param IManager $encryptionManager
	 */
	public function __construct(
		$appName,
		IRequest $request,
		private IL10N $l,
		private Session $session,
		private IManager $encryptionManager,
		private IAppConfig $appConfig,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function getStatus() {
		$status = 'error';
		$message = $this->l->t('Encryption status could not be determined.');

		switch ($this->session->getStatus()) {
			case Session::INIT_EXECUTED:
				$status = 'interactionNeeded';
				if ($this->appConfig->getValueBool('encryption', 'useMasterKey', true)) {
					$message = $this->l->t(
						'Server-side encryption could not be initialized. Please contact your administrator for guidance.'
					);
				} else {
					$message = $this->l->t(
						'Your private encryption key could not be unlocked. If your login password has changed, update your private key password in Personal settings to restore access to your encrypted files.'
					);
				}
				break;
			case Session::NOT_INITIALIZED:
				$status = 'interactionNeeded';
				if (!$this->encryptionManager->isEnabled()) {
					$message = $this->l->t(
						'Server-side encryption is not enabled. Please ask your administrator to enable it in the admin settings.'
					);
				} else {
					$message = $this->l->t(
						'Your encryption keys are not initialized for this session. Please sign out and sign back in.'
					);
				}
				break;
			case Session::INIT_SUCCESSFUL:
				$status = 'success';
				$message = $this->l->t('Encryption is enabled and ready.');
		}

		return new DataResponse(
			[
				'status' => $status,
				'initStatus' => $this->session->getStatus(),
				'data' => [
					'message' => $message,
				],
			]
		);
	}
}
