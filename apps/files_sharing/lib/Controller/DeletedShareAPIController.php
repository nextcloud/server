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
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\Files\IRootFolder;
use OCP\IDateTimeZone;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Share\Exceptions\GenericShareException;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager as ShareManager;
use OCP\Share\IShare;
use OCP\UserStatus\IManager as UserStatusManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type Files_SharingShare from ResponseDefinitions
 */
class DeletedShareAPIController extends ShareApiControllerFactory {
	
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
		$this->isDeletedShareController = true;
	}

	/**
	 * Get a list of all deleted shares
	 *
	 * @return DataResponse<Http::STATUS_OK, Files_SharingShare[], array{}>
	 *
	 * 200: Deleted shares returned
	 */
	#[NoAdminRequired]
	public function index(): DataResponse {
		$groupShares = $this->shareManager->getDeletedSharedWith($this->userId, IShare::TYPE_GROUP, null, -1, 0);
		$roomShares = $this->shareManager->getDeletedSharedWith($this->userId, IShare::TYPE_ROOM, null, -1, 0);
		$deckShares = $this->shareManager->getDeletedSharedWith($this->userId, IShare::TYPE_DECK, null, -1, 0);
		$sciencemeshShares = $this->shareManager->getDeletedSharedWith($this->userId, IShare::TYPE_SCIENCEMESH, null, -1, 0);

		$shares = array_merge($groupShares, $roomShares, $deckShares, $sciencemeshShares);

		$shares = array_map(function (IShare $share) {
			return $this->formatShare($share);
		}, $shares);

		return new DataResponse($shares);
	}

	/**
	 * Undelete a deleted share
	 *
	 * @param string $id ID of the share
	 * @return DataResponse<Http::STATUS_OK, array<empty>, array{}>
	 * @throws OCSException
	 * @throws OCSNotFoundException Share not found
	 *
	 * 200: Share undeleted successfully
	 */
	#[NoAdminRequired]
	public function undelete(string $id): DataResponse {
		try {
			$share = $this->shareManager->getShareById($id, $this->userId);
		} catch (ShareNotFound $e) {
			throw new OCSNotFoundException('Share not found');
		}

		if ($share->getPermissions() !== 0) {
			throw new OCSNotFoundException('No deleted share found');
		}

		try {
			$this->shareManager->restoreShare($share, $this->userId);
		} catch (GenericShareException $e) {
			throw new OCSException('Something went wrong');
		}

		return new DataResponse([]);
	}
}
