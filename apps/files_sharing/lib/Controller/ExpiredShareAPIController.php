<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Controller;

use OCA\Files_Sharing\ResponseDefinitions;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\IRootFolder;
use OCP\IDateTimeZone;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Share\IManager as ShareManager;
use OCP\Share\IShare;
use OCP\UserStatus\IManager as UserStatusManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type Files_SharingShare from ResponseDefinitions
 */
class ExpiredShareAPIController extends ShareApiControllerFactory {

	public function __construct(
		IRequest $request,
		protected ShareManager $shareManager,
		protected string $userId,
		protected IUserManager $userManager,
		protected IGroupManager $groupManager,
		protected IRootFolder $rootFolder,
		protected IAppManager $appManager,
		protected ContainerInterface $serverContainer,
		protected UserStatusManager $userStatusManager,
		protected IPreview $previewManager,
		protected IDateTimeZone $dateTimeZone,
		protected IURLGenerator $urlGenerator,
		protected IL10N $l,
		protected LoggerInterface $logger,
	) {
		parent::__construct(
			$request, $shareManager,
			$userId,
			$userManager,
			$groupManager,
			$rootFolder,
			$appManager,
			$serverContainer,
			$userStatusManager,
			$previewManager,
			$dateTimeZone,
			$urlGenerator,
			$l,
			$logger,
		);
	}

	/**
	 * Get a list of all expired shares
	 *
	 * @return DataResponse<Http::STATUS_OK, Files_SharingShare[], array{}>
	 *
	 * 200: Deleted shares returned
	 */
	#[NoAdminRequired]
	public function index(): DataResponse {
		$groupShares = $this->shareManager->getExpiredShares($this->userId, IShare::TYPE_GROUP, null, -1, 0);
		$roomShares = $this->shareManager->getExpiredShares($this->userId, IShare::TYPE_ROOM, null, -1, 0);
		$deckShares = $this->shareManager->getExpiredShares($this->userId, IShare::TYPE_DECK, null, -1, 0);
		$sciencemeshShares = $this->shareManager->getExpiredShares($this->userId, IShare::TYPE_SCIENCEMESH, null, -1, 0);
		$linkShares = $this->shareManager->getExpiredShares($this->userId, IShare::TYPE_LINK, null, -1, 0);
		$userShares = $this->shareManager->getExpiredShares($this->userId, IShare::TYPE_USER, null, -1, 0);
		$emailsShares = $this->shareManager->getExpiredShares($this->userId, IShare::TYPE_EMAIL, null, -1, 0);
		$circlesShares = $this->shareManager->getExpiredShares($this->userId, IShare::TYPE_CIRCLE, null, -1, 0);
		$remoteShares = $this->shareManager->getExpiredShares($this->userId, IShare::TYPE_REMOTE, null, -1, 0);

		$shares = array_merge($groupShares, $roomShares, $deckShares, $sciencemeshShares, $linkShares, $userShares, $emailsShares, $circlesShares, $remoteShares);

		$shares = array_map(function (IShare $share) {
			return $this->formatShare($share);
		}, $shares);

		return new DataResponse($shares);
	}
}
