<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Profile\Controller;

use OCA\Profile\ResponseDefinitions;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\Calendar\ICalendarQuery;
use OCP\Calendar\IManager;
use OCP\Files\File;
use OCP\IDateTimeFormatter;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IShare;

/**
 * @psalm-import-type ProfileSharedResource from ResponseDefinitions
 */
#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
class ProfileApiController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private IUserSession $userSession,
		private IUserManager $userManager,
		private IManager $calendarManager,
		private IShareManager $shareManager,
		private IDateTimeFormatter $formatter,
		private IURLGenerator $urlGenerator,
		private IAppManager $appManager,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get resources shared between the current user and the specified user.
	 *
	 * @param string $userId - The user ID of the user to get shared resources with.
	 * @return DataResponse<Http::STATUS_OK, list<ProfileSharedResource>, array{}>
	 * @throws OCSNotFoundException - The specified user does not exist.
	 *
	 * 200: The shared resources between the current user and the specified user.
	 * 404: The specified user does not exist.
	 */
	#[NoCSRFRequired]
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/resources/{userId}')]
	public function getResources(string $userId): DataResponse {
		$user = $this->userManager->get($userId);
		if (!$user) {
			throw new OCSNotFoundException();
		}

		$files = $this->getSharedNodes($userId);
		$events = $this->getSharedCalendarEvents($userId);

		$entries = array_values(array_merge($events, $files));
		return new DataResponse($entries);
	}

	/**
	 * Get all upcoming events shared between a user and the current user.
	 *
	 * If the calendar app is disabled for the current user no events will be returned.
	 *
	 * @param string $userId - The user ID of the user to get shared events with.
	 * @return list<ProfileSharedResource>
	 */
	private function getSharedCalendarEvents(string $userId) {
		if (!$this->appManager->isEnabledForUser('calendar', $this->userSession->getUser())) {
			return [];
		}

		$mePrincipal = 'principals/users/' . $this->userSession->getUser()->getUID();

		$query = $this->calendarManager->newQuery($mePrincipal);
		$query->setSearchPattern($userId);
		$query->addType('VEVENT');
		$query->addSearchProperty(ICalendarQuery::SEARCH_PROPERTY_ATTENDEE);
		$query->addSearchProperty(ICalendarQuery::SEARCH_PROPERTY_ORGANIZER);
		$now = new \DateTimeImmutable('now');
		$query->setTimerangeStart($now->modify('-1 hour'));
		$query->setLimit(9);

		$events = $this->calendarManager->searchForPrincipal($query);
		$result = [];
		foreach ($events as $event) {
			if (isset($event['objects'][0]['STATUS']) && $event['objects'][0]['STATUS'][0] === 'CANCELLED') {
				continue;
			}

			$end = $event['objects'][0]['DTEND'][0];
			if ($now->diff($end)->invert === 1) {
				// already ended, skip
				continue;
			}

			$calendarUid = $event['objects'][0]['UID'][0];
			if (isset($event['RECURRENCE-ID'])) {
				$recurrenceId = $event['RECURRENCE-ID'][0];
				$href = $this->urlGenerator->linkToRouteAbsolute('calendar.object.indexuid.recurrenceId', ['uid' => $calendarUid, 'recurrenceId' => $recurrenceId]);
			} else {
				$href = $this->urlGenerator->linkToRouteAbsolute('calendar.object.indexuid', ['uid' => $calendarUid]);
			}

			$start = \DateTime::createFromImmutable($event['objects'][0]['DTSTART'][0]);
			$result[] = [
				'label' => $event['objects'][0]['SUMMARY'][0],
				'text' => $this->formatter->formatTimeSpan($start, \DateTime::createFromImmutable($now)),
				'href' => $href,
				'img' => $this->urlGenerator->getAbsoluteURL($this->appManager->getAppIcon('calendar')),
			];
		}
		return $result;
	}

	/**
	 * @return ProfileSharedResource[]
	 */
	private function getSharedNodes(string $userId): array {
		$outgoingShares = [];
		$offset = 0;
		while (count($outgoingShares) < 5) {
			$shares = $this->shareManager->getSharesBy($userId, IShare::TYPE_USER, limit: 50, offset: $offset);
			$outgoingShares = array_merge($outgoingShares, array_filter($shares, fn ($share) => $share->getSharedWith() === $this->userSession->getUser()->getUID()));
			$offset += 50;
			if (count($shares) < 50) {
				break;
			}
		}

		$incomingShares = [];
		$offset = 0;
		while (count($incomingShares) < 5) {
			$shares = $this->shareManager->getSharesBy($this->userSession->getUser()->getUID(), IShare::TYPE_USER, limit: 50, offset: $offset);
			$incomingShares = array_merge($incomingShares, array_filter($shares, fn ($share) => $share->getSharedWith() === $userId));
			$offset += 50;
			if (count($shares) < 50) {
				break;
			}
		}

		$shares = array_slice(array_merge($outgoingShares, $incomingShares), 0, 5);
		usort($shares, fn ($a, $b) => $b->getNode()->getMTime() <=> $a->getNode()->getMTime());
		$files = [];
		foreach ($shares as $share) {
			$node = $share->getNode();
			// Preview endpoint only serves files; folders need the mime icon directly.
			if ($node instanceof File) {
				$img = $this->urlGenerator->linkToRouteAbsolute('core.Preview.getPreviewByFileId', [
					'fileId' => $node->getId(),
					'mimeFallback' => true,
				]);
			} else {
				$img = $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'filetypes/folder.svg'));
			}
			$files[] = [
				'label' => $node->getName(),
				'text' => $this->formatter->formatTimeSpan($node->getMTime()),
				'href' => $this->urlGenerator->linkToRouteAbsolute('files.view.index', [
					'dir' => $node->getParent()->getPath(),
					'fileid' => $node->getId(),
				]),
				'img' => $img,
			];
		}
		return $files;
	}
}
