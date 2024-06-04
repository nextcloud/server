<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Controller;

use OC\Contacts\ContactsMenu\Manager;
use OCA\Core\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Share\IShare;

/**
 * @psalm-import-type CoreContactsAction from ResponseDefinitions
 */
class HoverCardController extends \OCP\AppFramework\OCSController {
	public function __construct(
		IRequest $request,
		private IUserSession $userSession,
		private Manager $manager,
	) {
		parent::__construct('core', $request);
	}

	/**
	 * @NoAdminRequired
	 *
	 * Get the account details for a hovercard
	 *
	 * @param string $userId ID of the user
	 * @return DataResponse<Http::STATUS_OK, array{userId: string, displayName: string, actions: CoreContactsAction[]}, array{}>|DataResponse<Http::STATUS_NOT_FOUND, array<empty>, array{}>
	 *
	 * 200: Account details returned
	 * 404: Account not found
	 */
	#[ApiRoute(verb: 'GET', url: '/v1/{userId}', root: '/hovercard')]
	public function getUser(string $userId): DataResponse {
		$contact = $this->manager->findOne($this->userSession->getUser(), IShare::TYPE_USER, $userId);

		if (!$contact) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$data = $contact->jsonSerialize();

		$actions = $data['actions'];
		if ($data['topAction']) {
			array_unshift($actions, $data['topAction']);
		}

		/** @var CoreContactsAction[] $actions */
		return new DataResponse([
			'userId' => $userId,
			'displayName' => $contact->getFullName(),
			'actions' => $actions,
		]);
	}
}
