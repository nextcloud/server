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
use OCP\Contacts\ContactsMenu\IEntry;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Teams\ITeamManager;

class ContactsMenuController extends Controller {
	public function __construct(
		IRequest $request,
		private IUserSession $userSession,
		private Manager $manager,
		private ITeamManager $teamManager,
	) {
		parent::__construct('core', $request);
	}

	/**
	 * @return \JsonSerializable[]
	 * @throws Exception
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'POST', url: '/contactsmenu/contacts')]
	public function index(?string $filter = null, ?string $teamId = null): array {
		$entries = $this->manager->getEntries($this->userSession->getUser(), $filter);
		if ($teamId !== null) {
			/** @var \OC\Teams\TeamManager */
			$teamManager = $this->teamManager;
			$memberIds = $teamManager->getMembersOfTeam($teamId, $this->userSession->getUser()->getUID());
			$entries['contacts'] = array_filter(
				$entries['contacts'],
				fn (IEntry $entry) => in_array($entry->getProperty('UID'), $memberIds, true)
			);
		}
		return $entries;
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

	/**
	 * @return \JsonSerializable[]
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/contactsmenu/teams')]
	public function getTeams(): array {
		return $this->teamManager->getTeamsForUser($this->userSession->getUser()->getUID());
	}
}
