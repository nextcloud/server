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
	public function __construct(
		IURLGenerator $url,
		IL10N $l,
		private IUserSession $userSession,
		private UserGlobalStoragesService $userGlobalStoragesService,
		private BackendService $backendService,
	) {
		parent::__construct($url, $l);
	}
}
