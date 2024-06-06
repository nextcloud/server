<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Settings;

use OCA\Files_External\Service\BackendService;
use OCA\Files_External\Service\UserGlobalStoragesService;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserSession;

class PersonalSection extends Section {
	/** @var IUserSession */
	private $userSession;

	/** @var UserGlobalStoragesService */
	private $userGlobalStoragesService;

	/** @var BackendService */
	private $backendService;

	public function __construct(
		IURLGenerator $url,
		IL10N $l,
		IUserSession $userSession,
		UserGlobalStoragesService $userGlobalStoragesService,
		BackendService $backendService
	) {
		parent::__construct($url, $l);
		$this->userSession = $userSession;
		$this->userGlobalStoragesService = $userGlobalStoragesService;
		$this->backendService = $backendService;
	}
}
