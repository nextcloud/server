<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Controller;

use OC\Contacts\ContactsMenu\Manager;
use OC\Core\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Share\IShare;

/**
 * @psalm-import-type CoreContactsAction from ResponseDefinitions
 */
class HoverCardController extends OCSController {
	public function __construct(
		IRequest $request,
		private IUserSession $userSession,
		private Manager $manager,
	) {
		parent::__construct('core', $request);
	}

	/**
	 * Get the account details for a hovercard
	 *
	 * @param string $userId ID of the user
	 * @return DataResponse<Http::STATUS_OK, array{userId: string, displayName: string, actions: list<CoreContactsAction>}, array{}>|DataResponse<Http::STATUS_NOT_FOUND, list<empty>, array{}>
	 *
	 * 200: Account details returned
	 * 404: Account not found
	 */
	#[NoAdminRequired]
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

		/** @var list<CoreContactsAction> $actions */
		return new DataResponse([
			'userId' => $userId,
			'displayName' => $contact->getFullName(),
			'actions' => $actions,
		]);
	}
}
