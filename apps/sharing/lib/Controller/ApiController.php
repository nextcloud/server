<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Controller;

use OCA\Sharing\Exception\ShareInvalidException;
use OCA\Sharing\Exception\ShareInvalidRecipientSearchParametersException;
use OCA\Sharing\Exception\ShareNotFoundException;
use OCA\Sharing\Exception\ShareOperationNotAllowedException;
use OCA\Sharing\Manager;
use OCA\Sharing\Model\AShareRecipientType;
use OCA\Sharing\Model\AShareSourceType;
use OCA\Sharing\Model\Share;
use OCA\Sharing\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Snowflake\ISnowflakeGenerator;

/**
 * @psalm-import-type SharingShare from ResponseDefinitions
 * @psalm-import-type SharingFeature from ResponseDefinitions
 */
class ApiController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly IUserSession $userSession,
		private readonly Manager $manager,
		private readonly ISnowflakeGenerator $snowflakeGenerator,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Searches for recipients
	 *
	 * @param class-string<AShareRecipientType> $recipientType Type of the recipients
	 * @param non-empty-string $query The query to search for
	 * @param int<1, 100> $limit The maximum number of participants
	 * @param non-negative-int $offset The offset of the participants
	 * @return DataResponse<Http::STATUS_OK, list<string>, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, string, array{}>
	 *
	 * 200: Recipients returned
	 * 400: Bad recipient search parameters
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/recipients')]
	public function searchRecipients(string $recipientType, string $query, int $limit = 25, int $offset = 0): DataResponse {
		try {
			$recipients = $this->manager->searchRecipients($recipientType, $query, $limit, $offset);
		} catch (ShareInvalidRecipientSearchParametersException $shareInvalidRecipientSearchParametersException) {
			return new DataResponse($shareInvalidRecipientSearchParametersException->getMessage(), Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse($recipients);
	}

	/**
	 * Creates a new share
	 *
	 * @param SharingShare $data The new share data
	 * @return DataResponse<Http::STATUS_CREATED, SharingShare, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_UNAUTHORIZED|Http::STATUS_FORBIDDEN, string, array{}>
	 *
	 * 201: Share created successfully
	 * 400: Invalid share data
	 * 403: Creating the share is not allowed
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'POST', url: '/api/v1/share')]
	public function createShare(array $data): DataResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse('Not logged in', Http::STATUS_UNAUTHORIZED);
		}

		/** @var non-empty-string $id */
		$id = $this->snowflakeGenerator->nextId();
		$data['id'] = $id;
		$share = Share::fromArray($data);

		try {
			$this->manager->insert($user, $share);
		} catch (ShareInvalidException $e) {
			return new DataResponse($e->getMessage(), Http::STATUS_BAD_REQUEST);
		} catch (ShareOperationNotAllowedException $e) {
			return new DataResponse($e->getMessage(), Http::STATUS_FORBIDDEN);
		}

		return new DataResponse($share->toArray(), Http::STATUS_CREATED);
	}


	/**
	 * Gets a share
	 *
	 * @param string $id ID of the share
	 * @return DataResponse<Http::STATUS_OK, SharingShare, array{}>|DataResponse<Http::STATUS_UNAUTHORIZED|Http::STATUS_NOT_FOUND, string, array{}>
	 *
	 * 200: Share returned
	 * 404: Share not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/share/{id}')]
	public function getShare(string $id): DataResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse('Not logged in', Http::STATUS_UNAUTHORIZED);
		}

		try {
			$share = $this->manager->get($user, $id);
		} catch (ShareNotFoundException $shareNotFoundException) {
			return new DataResponse($shareNotFoundException->getMessage(), Http::STATUS_NOT_FOUND);
		}

		return new DataResponse($share->toArray());
	}

	/**
	 * Gets all shares
	 *
	 * @param ?class-string<AShareSourceType> $sourceType Filter by source type.
	 * @return DataResponse<Http::STATUS_OK, list<SharingShare>, array{}>|DataResponse<Http::STATUS_UNAUTHORIZED, string, array{}>
	 *
	 * 200: Shares returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/shares')]
	public function getShares(?string $sourceType = null) {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse('Not logged in', Http::STATUS_UNAUTHORIZED);
		}

		$shares = $this->manager->list($user, $sourceType);

		return new DataResponse(array_map(static fn (Share $share): array => $share->toArray(), $shares));
	}

	/**
	 * Updates a share
	 *
	 * @param non-empty-string $id ID of the share
	 * @param SharingShare $data The updated share data
	 * @return DataResponse<Http::STATUS_OK, SharingShare, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_UNAUTHORIZED|Http::STATUS_NOT_FOUND, string, array{}>
	 *
	 * 200: Share updated
	 * 400: Invalid share data
	 * 404: Share not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1/share/{id}')]
	public function updateShare(string $id, array $data): DataResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse('Not logged in', Http::STATUS_UNAUTHORIZED);
		}

		$data['id'] = $id;
		$share = Share::fromArray($data);

		try {
			$this->manager->update($user, $share);
		} catch (ShareInvalidException $e) {
			return new DataResponse($e->getMessage(), Http::STATUS_BAD_REQUEST);
		} catch (ShareNotFoundException $e) {
			return new DataResponse($e->getMessage(), Http::STATUS_NOT_FOUND);
		}

		return new DataResponse($share->toArray());
	}

	/**
	 * Deletes a share
	 *
	 * @param string $id ID of the share
	 * @return DataResponse<Http::STATUS_NO_CONTENT, list<empty>, array{}>|DataResponse<Http::STATUS_UNAUTHORIZED|Http::STATUS_NOT_FOUND, string, array{}>
	 *
	 * 204: Share deleted
	 * 404: Share not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/v1/share/{id}')]
	public function deleteShare(string $id): DataResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse('Not logged in', Http::STATUS_UNAUTHORIZED);
		}

		try {
			$this->manager->delete($user, $id);
		} catch (ShareNotFoundException $shareNotFoundException) {
			return new DataResponse($shareNotFoundException->getMessage(), Http::STATUS_NOT_FOUND);
		}

		return new DataResponse([], Http::STATUS_NO_CONTENT);
	}
}
