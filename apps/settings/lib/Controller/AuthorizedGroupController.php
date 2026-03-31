<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Controller;

use OCA\Settings\Service\AuthorizedGroupService;
use OCA\Settings\Service\NotFoundException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\DB\Exception;
use OCP\IRequest;

class AuthorizedGroupController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private AuthorizedGroupService $authorizedGroupService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @throws NotFoundException
	 * @throws Exception
	 * @throws \Throwable
	 */
	public function saveSettings(array $newGroups, string $class): DataResponse {
		// Delegate the diff-and-apply logic to the service so that the cache
		// is flushed exactly once after all mutations, regardless of how many
		// groups were added or removed.
		$this->authorizedGroupService->saveSettings($newGroups, $class);

		return new DataResponse(['valid' => true]);
	}
}
