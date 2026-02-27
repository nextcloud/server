<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\SystemTags\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\Config\IUserConfig;
use OCP\IRequest;
use OCP\IUserSession;

class LastUsedController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		protected readonly IUserConfig $config,
		protected readonly IUserSession $userSession,
	) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	public function getLastUsedTagIds(): DataResponse {
		$lastUsed = $this->config->getValueArray($this->userSession->getUser()->getUID(), 'systemtags', 'last_used');
		return new DataResponse(array_map(static fn ($id): string => (string)$id, $lastUsed));
	}
}
