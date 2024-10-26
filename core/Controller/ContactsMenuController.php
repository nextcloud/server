<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Controller;

use Exception;
use OC\Contacts\ContactsMenu\Manager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

class ContactsMenuController extends Controller {
	public function __construct(
		IRequest $request,
		private IUserSession $userSession,
		private Manager $manager,
	) {
		parent::__construct('core', $request);
	}

	/**
	 * @return \JsonSerializable[]
	 * @throws Exception
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'POST', url: '/contactsmenu/contacts')]
	public function index(?string $filter = null): array {
		return $this->manager->getEntries($this->userSession->getUser(), $filter);
	}

	/**
	 * @return JSONResponse|\JsonSerializable
	 * @throws Exception
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'POST', url: '/contactsmenu/findOne')]
	public function findOne(int $shareType, string $shareWith) {
		$contact = $this->manager->findOne($this->userSession->getUser(), $shareType, $shareWith);

		if ($contact) {
			return $contact;
		}
		return new JSONResponse([], Http::STATUS_NOT_FOUND);
	}
}
