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
use OCP\ICacheFactory;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Teams\ITeamManager;

class ContactsMenuController extends Controller {
	private const PREVIEW_AVATARS_LIMIT = 3;
	private const PREVIEW_AVATARS_CACHE_TTL = 300;

	public function __construct(
		IRequest $request,
		private IUserSession $userSession,
		private Manager $manager,
		private ITeamManager $teamManager,
		private ICacheFactory $cacheFactory,
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
			$memberIds = $this->teamManager->getMembersOfTeam($teamId, $this->userSession->getUser()->getUID());
			$entries['contacts'] = array_filter(
				$entries['contacts'],
				fn (IEntry $entry) => array_key_exists($entry->getProperty('UID'), $memberIds)
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

	/**
	 * Top contacts for the People menu header avatar stack (max 3).
	 * Uses a lightweight query (limited results, no action providers) and
	 * caches per user for a few minutes.
	 *
	 * @return list<array>
	 * @throws Exception
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/contactsmenu/preview-avatars')]
	public function previewAvatars(?string $teamId = null): array {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return [];
		}

		$cache = $this->cacheFactory->createDistributed('contactsmenu-preview');
		$cacheKey = $user->getUID();
		$cached = $cache->get($cacheKey);
		if (!is_array($cached)) {
			$entries = $this->manager->getPreviewEntries($user, self::PREVIEW_AVATARS_LIMIT);
			$cached = array_map(
				static fn (IEntry $entry): array => $entry->jsonSerialize(),
				$entries,
			);
			$cache->set($cacheKey, $cached, self::PREVIEW_AVATARS_CACHE_TTL);
		}

		if ($teamId !== null && $teamId !== '') {
			$memberIds = $this->teamManager->getMembersOfTeam($teamId, $user->getUID());
			$cached = array_filter(
				$cached,
				static fn (array $entry): bool => array_key_exists($entry['uid'] ?? '', $memberIds)
			);
		}

		return array_values(array_slice($cached, 0, self::PREVIEW_AVATARS_LIMIT));
	}
}
