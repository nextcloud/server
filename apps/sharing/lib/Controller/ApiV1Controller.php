<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Controller;

use Exception;
use OCA\Sharing\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Sharing\Exception\ShareInvalidException;
use OCP\Sharing\Exception\ShareNotFoundException;
use OCP\Sharing\Exception\ShareOperationForbiddenException;
use OCP\Sharing\ISharingManager;
use OCP\Sharing\ISharingRegistry;
use OCP\Sharing\Permission\ISharePermissionPreset;
use OCP\Sharing\Permission\ISharePermissionType;
use OCP\Sharing\Permission\SharePermission;
use OCP\Sharing\Property\ISharePropertyType;
use OCP\Sharing\Property\ISharePropertyTypeFilter;
use OCP\Sharing\Property\ShareProperty;
use OCP\Sharing\Recipient\IShareRecipientType;
use OCP\Sharing\Recipient\ShareRecipient;
use OCP\Sharing\Share;
use OCP\Sharing\ShareAccessContext;
use OCP\Sharing\ShareState;
use OCP\Sharing\Source\IShareSourceType;
use OCP\Sharing\Source\ShareSource;
use RuntimeException;
use ValueError;

// TODO: Add "recipient suggestions" endpoint
// TODO: Add rate limiting

/**
 * @psalm-import-type SharingShare from ResponseDefinitions
 * @psalm-import-type SharingRecipient from ResponseDefinitions
 * @psalm-import-type SharingState from ResponseDefinitions
 * @psalm-import-type SharingPermissionPreset from ResponseDefinitions
 */
final class ApiV1Controller extends OCSController {
	public ShareAccessContext $accessContext;

	public function __construct(
		string $appName,
		IRequest $request,
		IUserSession $userSession,
		private readonly ISharingManager $manager,
		private readonly ISharingRegistry $registry,
		private readonly IFactory $l10nFactory,
		private readonly IURLGenerator $urlGenerator,
		private readonly IUserManager $userManager,
		private readonly IDBConnection $dbConnection,
	) {
		parent::__construct($appName, $request);

		$this->accessContext = new ShareAccessContext($userSession->getUser());
	}

	/**
	 * Search for recpients that can be added to a share.
	 *
	 * @param ?list<class-string<IShareRecipientType>> $recipientTypeClasses Type class of recipients to filter by
	 * @param string $query The query to search for
	 * @param int<1, 100> $limit The maximum number of participants
	 * @param non-negative-int $offset The offset of the participants
	 * @return DataResponse<Http::STATUS_OK, list<SharingRecipient>, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, string, array{}>
	 *
	 * 200: Recipients returned
	 * 400: Invalid recipient search parameters
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/recipients')]
	public function searchRecipients(?array $recipientTypeClasses, string $query, int $limit = 10, int $offset = 0): DataResponse {
		/** @psalm-suppress DocblockTypeContradiction */
		if ($limit < 1) {
			return new DataResponse('The limit is too low.', Http::STATUS_BAD_REQUEST);
		}

		/** @psalm-suppress DocblockTypeContradiction */
		if ($limit > 100) {
			return new DataResponse('The limit is too high.', Http::STATUS_BAD_REQUEST);
		}

		/** @psalm-suppress DocblockTypeContradiction */
		if ($offset < 0) {
			return new DataResponse('The offset is too low.', Http::STATUS_BAD_REQUEST);
		}

		try {
			$recipients = $this->manager->searchRecipients($this->accessContext, $recipientTypeClasses, $query, $limit, $offset);
			return new DataResponse(ShareRecipient::formatMultiple($this->registry, $this->l10nFactory, $this->urlGenerator, $this->userManager, $recipients));
		} catch (ShareInvalidException $shareInvalidException) {
			return new DataResponse($shareInvalidException->getHint(), Http::STATUS_BAD_REQUEST);
		}
	}

	/**
	 * Generate a new secret.
	 *
	 * @return DataResponse<Http::STATUS_OK, string, array{}>
	 *
	 * 200: Generated secret returned
	 */
	#[PublicPage]
	#[ApiRoute(verb: 'GET', url: '/api/v1/secret')]
	public function generateSecret(): DataResponse {
		return new DataResponse($this->manager->generateSecret());
	}

	/**
	 * Create a new share.
	 *
	 * @return DataResponse<Http::STATUS_CREATED, SharingShare, array{}>
	 *
	 * 201: Share created successfully
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'POST', url: '/api/v1/share')]
	public function createShare(): DataResponse {
		try {
			try {
				$this->dbConnection->beginTransaction();

				$id = $this->manager->createShare($this->accessContext);

				$share = $this->manager->getShare($this->accessContext, $id);
				$this->dbConnection->commit();
				return new DataResponse($share->format($this->registry, $this->l10nFactory, $this->urlGenerator, $this->userManager), Http::STATUS_CREATED);
			} catch (Exception $exception) {
				$this->dbConnection->rollBack();
				throw $exception;
			}
		} catch (ShareNotFoundException $shareNotFoundException) {
			throw new RuntimeException($shareNotFoundException->getHint(), $shareNotFoundException->getCode(), $shareNotFoundException);
		}
	}

	/**
	 * Update the state of a share.
	 *
	 * @param string $id ID of the share
	 * @param SharingState $state New state of the share
	 * @return DataResponse<Http::STATUS_OK, SharingShare, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, string, array{}>
	 *
	 * 200: Share state updated successfully
	 * 400: Invalid share state
	 * 403: Updating the share state is not allowed
	 * 404: Share not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1/share/{id}/state')]
	public function updateShareState(string $id, string $state): DataResponse {
		try {
			$shareState = ShareState::from($state);
		} catch (ValueError $valueError) {
			return new DataResponse($valueError->getMessage(), Http::STATUS_BAD_REQUEST);
		}

		try {
			try {
				$this->dbConnection->beginTransaction();

				$this->manager->updateShareState($this->accessContext, $id, $shareState);
				$share = $this->manager->getShare($this->accessContext, $id);
				$this->dbConnection->commit();
				return new DataResponse($share->format($this->registry, $this->l10nFactory, $this->urlGenerator, $this->userManager));
			} catch (Exception $exception) {
				$this->dbConnection->rollBack();
				throw $exception;
			}
		} catch (ShareOperationForbiddenException $shareOperationForbiddenException) {
			return new DataResponse($shareOperationForbiddenException->getHint(), Http::STATUS_FORBIDDEN);
		} catch (ShareNotFoundException $shareNotFoundException) {
			return new DataResponse($shareNotFoundException->getHint(), Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Add a new source to a share.
	 *
	 * @param string $id ID of the share
	 * @param class-string<IShareSourceType> $class Type class of the source
	 * @param non-empty-string $value Value of the source
	 * @return DataResponse<Http::STATUS_OK, SharingShare, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, string, array{}>
	 *
	 * 200: Share source added successfully
	 * 400: Invalid share source
	 * 403: Adding the share source is not allowed
	 * 404: Share not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'POST', url: '/api/v1/share/{id}/source')]
	public function addShareSource(string $id, string $class, string $value): DataResponse {
		try {
			try {
				$this->dbConnection->beginTransaction();

				$this->manager->addShareSource($this->accessContext, $id, new ShareSource($class, $value));
				$share = $this->manager->getShare($this->accessContext, $id);
				$this->dbConnection->commit();
				return new DataResponse($share->format($this->registry, $this->l10nFactory, $this->urlGenerator, $this->userManager));
			} catch (Exception $exception) {
				$this->dbConnection->rollBack();
				throw $exception;
			}
		} catch (ShareInvalidException $shareInvalidException) {
			return new DataResponse($shareInvalidException->getHint(), Http::STATUS_BAD_REQUEST);
		} catch (ShareOperationForbiddenException $shareOperationForbiddenException) {
			return new DataResponse($shareOperationForbiddenException->getHint(), Http::STATUS_FORBIDDEN);
		} catch (ShareNotFoundException $shareNotFoundException) {
			return new DataResponse($shareNotFoundException->getHint(), Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Remove an existing source from a share.
	 *
	 * @param string $id ID of the share
	 * @param class-string<IShareSourceType> $class Type class of the source
	 * @param non-empty-string $value Value of the source
	 * @return DataResponse<Http::STATUS_OK, SharingShare, array{}>|DataResponse<Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, string, array{}>
	 *
	 * 200: Share source removed successfully
	 * 403: Removing the share source is not allowed
	 * 404: Share not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/v1/share/{id}/source')]
	public function removeShareSource(string $id, string $class, string $value): DataResponse {
		try {
			try {
				$this->dbConnection->beginTransaction();

				$this->manager->removeShareSource($this->accessContext, $id, new ShareSource($class, $value));
				$share = $this->manager->getShare($this->accessContext, $id);
				$this->dbConnection->commit();
				return new DataResponse($share->format($this->registry, $this->l10nFactory, $this->urlGenerator, $this->userManager));
			} catch (Exception $exception) {
				$this->dbConnection->rollBack();
				throw $exception;
			}
		} catch (ShareOperationForbiddenException $shareOperationForbiddenException) {
			return new DataResponse($shareOperationForbiddenException->getHint(), Http::STATUS_FORBIDDEN);
		} catch (ShareNotFoundException $shareNotFoundException) {
			return new DataResponse($shareNotFoundException->getHint(), Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Add a new recipient to a share.
	 *
	 * @param string $id ID of the share
	 * @param class-string<IShareRecipientType> $class Type class of the recipient
	 * @param non-empty-string $value Value of the recipient
	 * @param ?non-empty-string $instance Instance of the recipient
	 * @return DataResponse<Http::STATUS_OK, SharingShare, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, string, array{}>
	 *
	 * 200: Share recipient added successfully
	 * 400: Invalid share recipient
	 * 403: Adding the share recipient is not allowed
	 * 404: Share not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'POST', url: '/api/v1/share/{id}/recipient')]
	public function addShareRecipient(string $id, string $class, string $value, ?string $instance): DataResponse {
		try {
			try {
				$this->dbConnection->beginTransaction();

				$this->manager->addShareRecipient($this->accessContext, $id, new ShareRecipient($class, $value, $instance));
				$share = $this->manager->getShare($this->accessContext, $id);
				$this->dbConnection->commit();
				return new DataResponse($share->format($this->registry, $this->l10nFactory, $this->urlGenerator, $this->userManager));
			} catch (Exception $exception) {
				$this->dbConnection->rollBack();
				throw $exception;
			}
		} catch (ShareInvalidException $shareInvalidException) {
			return new DataResponse($shareInvalidException->getHint(), Http::STATUS_BAD_REQUEST);
		} catch (ShareOperationForbiddenException $shareOperationForbiddenException) {
			return new DataResponse($shareOperationForbiddenException->getHint(), Http::STATUS_FORBIDDEN);
		} catch (ShareNotFoundException $shareNotFoundException) {
			return new DataResponse($shareNotFoundException->getHint(), Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Remove an existing recipient from a share.
	 *
	 * @param string $id ID of the share
	 * @param class-string<IShareRecipientType> $class Type class of the recipient
	 * @param non-empty-string $value Value of the recipient
	 * @param ?non-empty-string $instance Instance of the recipient
	 * @return DataResponse<Http::STATUS_OK, SharingShare, array{}>|DataResponse<Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, string, array{}>
	 *
	 * 200: Share recipient removed successfully
	 * 403: Removing the share recipient is not allowed
	 * 404: Share not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/v1/share/{id}/recipient')]
	public function removeShareRecipient(string $id, string $class, string $value, ?string $instance): DataResponse {
		try {
			try {
				$this->dbConnection->beginTransaction();

				$this->manager->removeShareRecipient($this->accessContext, $id, new ShareRecipient($class, $value, $instance));
				$share = $this->manager->getShare($this->accessContext, $id);
				$this->dbConnection->commit();
				return new DataResponse($share->format($this->registry, $this->l10nFactory, $this->urlGenerator, $this->userManager));
			} catch (Exception $exception) {
				$this->dbConnection->rollBack();
				throw $exception;
			}
		} catch (ShareOperationForbiddenException $shareOperationForbiddenException) {
			return new DataResponse($shareOperationForbiddenException->getHint(), Http::STATUS_FORBIDDEN);
		} catch (ShareNotFoundException $shareNotFoundException) {
			return new DataResponse($shareNotFoundException->getHint(), Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Update the scecret of a recipient.
	 *
	 * @param string $id ID of the share
	 * @param class-string<IShareRecipientType> $class Type class of the recipient
	 * @param non-empty-string $value Value of the recipient
	 * @param ?non-empty-string $instance Instance of the recipient
	 * @param non-empty-string $secret Secret of the recipient
	 * @return DataResponse<Http::STATUS_OK, SharingShare, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, string, array{}>
	 *
	 * 200: Share recipient secret updated successfully
	 * 400: Invalid secret
	 * 403: Updating the share recipient secret is not allowed
	 * 404: Share not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1/share/{id}/recipient/secret')]
	public function updateShareRecipientSecret(string $id, string $class, string $value, ?string $instance, string $secret): DataResponse {
		try {
			try {
				$this->dbConnection->beginTransaction();

				$this->manager->updateShareRecipientSecret($this->accessContext, $id, new ShareRecipient($class, $value, $instance), $secret);
				$share = $this->manager->getShare($this->accessContext, $id);
				$this->dbConnection->commit();
				return new DataResponse($share->format($this->registry, $this->l10nFactory, $this->urlGenerator, $this->userManager));
			} catch (Exception $exception) {
				$this->dbConnection->rollBack();
				throw $exception;
			}
		} catch (ShareInvalidException $shareInvalidException) {
			return new DataResponse($shareInvalidException->getHint(), Http::STATUS_BAD_REQUEST);
		} catch (ShareOperationForbiddenException $shareOperationForbiddenException) {
			return new DataResponse($shareOperationForbiddenException->getHint(), Http::STATUS_FORBIDDEN);
		} catch (ShareNotFoundException $shareNotFoundException) {
			return new DataResponse($shareNotFoundException->getHint(), Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Update a property of a share.
	 *
	 * @param string $id ID of the share
	 * @param class-string<ISharePropertyType> $class Type class of the property
	 * @param ?string $value Value of the property
	 * @return DataResponse<Http::STATUS_OK, SharingShare, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, string, array{}>
	 *
	 * 200: Share property updated successfully
	 * 400: Invalid share property
	 * 403: Updating the share property is not allowed
	 * 404: Share not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1/share/{id}/property')]
	public function updateShareProperty(string $id, string $class, ?string $value): DataResponse {
		try {
			try {
				$this->dbConnection->beginTransaction();

				$this->manager->updateShareProperty($this->accessContext, $id, new ShareProperty($class, $value));
				$share = $this->manager->getShare($this->accessContext, $id);
				$this->dbConnection->commit();
				return new DataResponse($share->format($this->registry, $this->l10nFactory, $this->urlGenerator, $this->userManager));
			} catch (Exception $exception) {
				$this->dbConnection->rollBack();
				throw $exception;
			}
		} catch (ShareInvalidException $shareInvalidException) {
			return new DataResponse($shareInvalidException->getHint(), Http::STATUS_BAD_REQUEST);
		} catch (ShareOperationForbiddenException $shareOperationForbiddenException) {
			return new DataResponse($shareOperationForbiddenException->getHint(), Http::STATUS_FORBIDDEN);
		} catch (ShareNotFoundException $shareNotFoundException) {
			return new DataResponse($shareNotFoundException->getHint(), Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Update a permission of a share.
	 *
	 * @param string $id ID of the share
	 * @param class-string<ISharePermissionType> $class Type class of the permission
	 * @param bool $enabled Enabled state of the permission
	 * @return DataResponse<Http::STATUS_OK, SharingShare, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, string, array{}>
	 *
	 * 200: Share permission updated successfully
	 * 400: Invalid share permission
	 * 403: Updating the share permission is not allowed
	 * 404: Share not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1/share/{id}/permission')]
	public function updateSharePermission(string $id, string $class, bool $enabled): DataResponse {
		try {
			try {
				$this->dbConnection->beginTransaction();

				$this->manager->updateSharePermission($this->accessContext, $id, new SharePermission($class, $enabled));
				$share = $this->manager->getShare($this->accessContext, $id);
				$this->dbConnection->commit();
				return new DataResponse($share->format($this->registry, $this->l10nFactory, $this->urlGenerator, $this->userManager));
			} catch (Exception $exception) {
				$this->dbConnection->rollBack();
				throw $exception;
			}
		} catch (ShareInvalidException $shareInvalidException) {
			return new DataResponse($shareInvalidException->getHint(), Http::STATUS_BAD_REQUEST);
		} catch (ShareOperationForbiddenException $shareOperationForbiddenException) {
			return new DataResponse($shareOperationForbiddenException->getHint(), Http::STATUS_FORBIDDEN);
		} catch (ShareNotFoundException $shareNotFoundException) {
			return new DataResponse($shareNotFoundException->getHint(), Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Select a permission preset for a share.
	 *
	 * @param string $id ID of the share
	 * @param class-string<ISharePermissionPreset> $permissionPresetClass New permission preset of the share
	 * @return DataResponse<Http::STATUS_OK, SharingShare, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, string, array{}>
	 *
	 * 200: Share permission preset selected successfully
	 * 400: Invalid share permission preset
	 * 403: Selecting the share permission preset is not allowed
	 * 404: Share not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1/share/{id}/permission/preset')]
	public function selectSharePermissionPreset(string $id, string $permissionPresetClass): DataResponse {
		try {
			try {
				$this->dbConnection->beginTransaction();

				$this->manager->selectSharePermissionPreset($this->accessContext, $id, $permissionPresetClass);
				$share = $this->manager->getShare($this->accessContext, $id);
				$this->dbConnection->commit();
				return new DataResponse($share->format($this->registry, $this->l10nFactory, $this->urlGenerator, $this->userManager));
			} catch (Exception $exception) {
				$this->dbConnection->rollBack();
				throw $exception;
			}
		} catch (ShareOperationForbiddenException $shareOperationForbiddenException) {
			return new DataResponse($shareOperationForbiddenException->getHint(), Http::STATUS_FORBIDDEN);
		} catch (ShareNotFoundException $shareNotFoundException) {
			return new DataResponse($shareNotFoundException->getHint(), Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Delete a share.
	 *
	 * @param string $id ID of the share
	 * @return DataResponse<Http::STATUS_NO_CONTENT, list<empty>, array{}>|DataResponse<Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, string, array{}>
	 *
	 * 204: Share deleted
	 * 403: Deleting the share is not allowed
	 * 404: Share not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/v1/share/{id}')]
	public function deleteShare(string $id): DataResponse {
		try {
			try {
				$this->dbConnection->beginTransaction();

				$this->manager->deleteShare($this->accessContext, $id);
				$this->dbConnection->commit();
				return new DataResponse([], Http::STATUS_NO_CONTENT);
			} catch (Exception $exception) {
				$this->dbConnection->rollBack();
				throw $exception;
			}
		} catch (ShareNotFoundException $shareNotFoundException) {
			return new DataResponse($shareNotFoundException->getHint(), Http::STATUS_NOT_FOUND);
		} catch (ShareOperationForbiddenException $shareOperationNotAllowedException) {
			return new DataResponse($shareOperationNotAllowedException->getHint(), Http::STATUS_FORBIDDEN);
		}
	}

	/**
	 * Get a share.
	 *
	 * @param string $id ID of the share
	 * @param ?string $secret Secret of the share
	 * @param array<class-string<IShareRecipientType|ISharePropertyTypeFilter>, mixed> $arguments Arguments for accessing the share
	 * @return DataResponse<Http::STATUS_OK, SharingShare, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_NOT_FOUND, string, array{}>
	 *
	 * 200: Share returned
	 * 400: Invalid arguments
	 * 404: Share not found
	 */
	#[PublicPage]
	// This should be a GET, but GET doesn't allow a request body which is required for the $arguments.
	#[ApiRoute(verb: 'POST', url: '/api/v1/share/{id}')]
	public function getShare(string $id, ?string $secret = null, array $arguments = []): DataResponse {
		try {
			try {
				$this->dbConnection->beginTransaction();

				$share = $this->manager->getShare(new ShareAccessContext($this->accessContext->currentUser, $secret, $arguments, $this->accessContext->overrideChecks), $id);
				$this->dbConnection->commit();
				return new DataResponse($share->format($this->registry, $this->l10nFactory, $this->urlGenerator, $this->userManager));
			} catch (Exception $exception) {
				$this->dbConnection->rollBack();
				throw $exception;
			}
		} catch (ShareInvalidException $shareInvalidException) {
			return new DataResponse($shareInvalidException->getHint(), Http::STATUS_BAD_REQUEST);
		} catch (ShareNotFoundException $shareNotFoundException) {
			return new DataResponse($shareNotFoundException->getHint(), Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Get multiple shares.
	 *
	 * @param ?class-string<IShareSourceType> $filterSourceTypeClass Source type class to filter by.
	 * @param ?string $filterSourceTypeValue Source type value to filter by.
	 * @param ?string $lastShareID The ID of the previous share. This is used as an offset and only shares with higher IDs are returned.
	 * @param int<1, 100> $limit The number of shares to return.
	 * @return DataResponse<Http::STATUS_OK, list<SharingShare>, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, string, array{}>
	 *
	 * 200: Shares returned
	 * 400: Invalid parameters
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/shares')]
	public function getShares(?string $filterSourceTypeClass, ?string $filterSourceTypeValue, ?string $lastShareID, int $limit = 100): DataResponse {
		/** @psalm-suppress DocblockTypeContradiction */
		if ($limit < 1) {
			return new DataResponse('The limit is too low.', Http::STATUS_BAD_REQUEST);
		}

		/** @psalm-suppress DocblockTypeContradiction */
		if ($limit > 100) {
			return new DataResponse('The limit is too high.', Http::STATUS_BAD_REQUEST);
		}

		try {
			$this->dbConnection->beginTransaction();

			$shares = $this->manager->getShares($this->accessContext, $filterSourceTypeClass, $filterSourceTypeValue, $lastShareID, $limit);
			$this->dbConnection->commit();
			return new DataResponse(Share::formatMultiple($this->registry, $this->l10nFactory, $this->urlGenerator, $this->userManager, $shares));
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw $exception;
		}
	}
}
