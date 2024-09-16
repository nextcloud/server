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
use OCP\IL10N;
use OCP\IRequest;

class StatusController extends Controller {

	/** @var IL10N */
	private $l;

	/** @var Session */
	private $session;

	/** @var IManager */
	private $encryptionManager;

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param IL10N $l10n
	 * @param Session $session
	 * @param IManager $encryptionManager
	 */
	public function __construct($AppName,
		IRequest $request,
		IL10N $l10n,
		Session $session,
		IManager $encryptionManager
	) {
		parent::__construct($AppName, $request);
		$this->l = $l10n;
		$this->session = $session;
		$this->encryptionManager = $encryptionManager;
	}

	/**
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function getStatus() {
		$status = 'error';
		$message = 'no valid init status';
		switch ($this->session->getStatus()) {
			case Session::INIT_EXECUTED:
				$status = 'interactionNeeded';
				$message = $this->l->t(
					'Invalid private key for encryption app. Please update your private key password in your personal settings to recover access to your encrypted files.'
				);
				break;
			case Session::NOT_INITIALIZED:
				$status = 'interactionNeeded';
				if ($this->encryptionManager->isEnabled()) {
					$message = $this->l->t(
						'Encryption App is enabled, but your keys are not initialized. Please log-out and log-in again.'
					);
				} else {
					$message = $this->l->t(
						'Please enable server side encryption in the admin settings in order to use the encryption module.'
					);
				}
				break;
			case Session::INIT_SUCCESSFUL:
				$status = 'success';
				$message = $this->l->t('Encryption app is enabled and ready');
		}

		return new DataResponse(
			[
				'status' => $status,
				'data' => [
					'message' => $message]
			]
		);
	}
}
