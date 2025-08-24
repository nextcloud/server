<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WebhookListeners\Controller;

use OCA\WebhookListeners\Db\AuthMethod;
use OCA\WebhookListeners\Db\WebhookListener;
use OCA\WebhookListeners\Db\WebhookListenerMapper;
use OCA\WebhookListeners\ResponseDefinitions;
use OCA\WebhookListeners\Settings\Admin;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\AppApiAdminAccessWithoutUser;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\ISession;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type WebhookListenersWebhookInfo from ResponseDefinitions
 */
#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
class WebhooksController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private LoggerInterface $logger,
		private WebhookListenerMapper $mapper,
		private ?string $userId,
		private ISession $session,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * List registered webhooks
	 *
	 * @param string|null $uri The callback URI to filter by
	 * @return DataResponse<Http::STATUS_OK, list<WebhookListenersWebhookInfo>, array{}>
	 * @throws OCSException Other internal error
	 *
	 * 200: Webhook registrations returned
	 */
	#[ApiRoute(verb: 'GET', url: '/api/v1/webhooks')]
	#[AuthorizedAdminSetting(settings:Admin::class)]
	#[AppApiAdminAccessWithoutUser]
	public function index(?string $uri = null): DataResponse {
		try {
			if ($uri !== null) {
				$webhookListeners = $this->mapper->getByUri($uri);
			} else {
				$webhookListeners = $this->mapper->getAll();
			}

			return new DataResponse(array_values(array_map(
				fn (WebhookListener $listener): array => $listener->jsonSerialize(),
				$webhookListeners
			)));
		} catch (\Exception $e) {
			$this->logger->error('Error when listing webhooks', ['exception' => $e]);
			throw new OCSException('An internal error occurred', Http::STATUS_INTERNAL_SERVER_ERROR, $e);
		}
	}

	/**
	 * Get details on a registered webhook
	 *
	 * @param int $id id of the webhook
	 *
	 * @return DataResponse<Http::STATUS_OK, WebhookListenersWebhookInfo, array{}>
	 * @throws OCSNotFoundException Webhook not found
	 * @throws OCSException Other internal error
	 *
	 * 200: Webhook registration returned
	 */
	#[ApiRoute(verb: 'GET', url: '/api/v1/webhooks/{id}')]
	#[AuthorizedAdminSetting(settings:Admin::class)]
	#[AppApiAdminAccessWithoutUser]
	public function show(int $id): DataResponse {
		try {
			return new DataResponse($this->mapper->getById($id)->jsonSerialize());
		} catch (DoesNotExistException $e) {
			throw new OCSNotFoundException($e->getMessage(), $e);
		} catch (\Exception $e) {
			$this->logger->error('Error when getting webhook', ['exception' => $e]);
			throw new OCSException('An internal error occurred', Http::STATUS_INTERNAL_SERVER_ERROR, $e);
		}
	}

	/**
	 * Register a new webhook
	 *
	 * @param string $httpMethod HTTP method to use to contact the webhook
	 * @param string $uri Webhook URI endpoint
	 * @param string $event Event class name to listen to
	 * @param ?array<string,mixed> $eventFilter Mongo filter to apply to the serialized data to decide if firing
	 * @param ?string $userIdFilter User id to filter on. The webhook will only be called by requests from this user. Empty or null means no filtering.
	 * @param ?array<string,string> $headers Array of headers to send
	 * @param "none"|"header"|null $authMethod Authentication method to use
	 * @param ?array<string,mixed> $authData Array of data for authentication
	 *
	 * @return DataResponse<Http::STATUS_OK, WebhookListenersWebhookInfo, array{}>
	 *
	 * 200: Webhook registration returned
	 *
	 * @throws OCSBadRequestException Bad request
	 * @throws OCSForbiddenException Insufficient permissions
	 * @throws OCSException Other error
	 */
	#[ApiRoute(verb: 'POST', url: '/api/v1/webhooks')]
	#[AuthorizedAdminSetting(settings:Admin::class)]
	#[AppApiAdminAccessWithoutUser]
	public function create(
		string $httpMethod,
		string $uri,
		string $event,
		?array $eventFilter,
		?string $userIdFilter,
		?array $headers,
		?string $authMethod,
		#[\SensitiveParameter]
		?array $authData,
	): DataResponse {
		$appId = null;
		if ($this->session->get('app_api') === true) {
			$appId = $this->request->getHeader('ex-app-id');
		}
		try {
			$authMethod = AuthMethod::from($authMethod ?? AuthMethod::None->value);
		} catch (\ValueError $e) {
			throw new OCSBadRequestException('This auth method does not exist');
		}
		try {
			$webhookListener = $this->mapper->addWebhookListener(
				$appId,
				$this->userId,
				$httpMethod,
				$uri,
				$event,
				$eventFilter,
				$userIdFilter,
				$headers,
				$authMethod,
				$authData,
			);
			return new DataResponse($webhookListener->jsonSerialize());
		} catch (\UnexpectedValueException $e) {
			throw new OCSBadRequestException($e->getMessage(), $e);
		} catch (\DomainException $e) {
			throw new OCSForbiddenException($e->getMessage(), $e);
		} catch (\Exception $e) {
			$this->logger->error('Error when inserting webhook', ['exception' => $e]);
			throw new OCSException('An internal error occurred', Http::STATUS_INTERNAL_SERVER_ERROR, $e);
		}
	}

	/**
	 * Update an existing webhook registration
	 *
	 * @param int $id id of the webhook
	 * @param string $httpMethod HTTP method to use to contact the webhook
	 * @param string $uri Webhook URI endpoint
	 * @param string $event Event class name to listen to
	 * @param ?array<string,mixed> $eventFilter Mongo filter to apply to the serialized data to decide if firing
	 * @param ?string $userIdFilter User id to filter on. The webhook will only be called by requests from this user. Empty or null means no filtering.
	 * @param ?array<string,string> $headers Array of headers to send
	 * @param "none"|"header"|null $authMethod Authentication method to use
	 * @param ?array<string,mixed> $authData Array of data for authentication
	 *
	 * @return DataResponse<Http::STATUS_OK, WebhookListenersWebhookInfo, array{}>
	 *
	 * 200: Webhook registration returned
	 *
	 * @throws OCSBadRequestException Bad request
	 * @throws OCSForbiddenException Insufficient permissions
	 * @throws OCSException Other error
	 */
	#[ApiRoute(verb: 'POST', url: '/api/v1/webhooks/{id}')]
	#[AuthorizedAdminSetting(settings:Admin::class)]
	#[AppApiAdminAccessWithoutUser]
	public function update(
		int $id,
		string $httpMethod,
		string $uri,
		string $event,
		?array $eventFilter,
		?string $userIdFilter,
		?array $headers,
		?string $authMethod,
		#[\SensitiveParameter]
		?array $authData,
	): DataResponse {
		$appId = null;
		if ($this->session->get('app_api') === true) {
			$appId = $this->request->getHeader('ex-app-id');
		}
		try {
			$authMethod = AuthMethod::from($authMethod ?? AuthMethod::None->value);
		} catch (\ValueError $e) {
			throw new OCSBadRequestException('This auth method does not exist');
		}
		try {
			$webhookListener = $this->mapper->updateWebhookListener(
				$id,
				$appId,
				$this->userId,
				$httpMethod,
				$uri,
				$event,
				$eventFilter,
				$userIdFilter,
				$headers,
				$authMethod,
				$authData,
			);
			return new DataResponse($webhookListener->jsonSerialize());
		} catch (\UnexpectedValueException $e) {
			throw new OCSBadRequestException($e->getMessage(), $e);
		} catch (\DomainException $e) {
			throw new OCSForbiddenException($e->getMessage(), $e);
		} catch (\Exception $e) {
			$this->logger->error('Error when updating flow with id ' . $id, ['exception' => $e]);
			throw new OCSException('An internal error occurred', Http::STATUS_INTERNAL_SERVER_ERROR, $e);
		}
	}

	/**
	 * Remove an existing webhook registration
	 *
	 * @param int $id id of the webhook
	 *
	 * @return DataResponse<Http::STATUS_OK, bool, array{}>
	 *
	 * 200: Boolean returned whether something was deleted
	 *
	 * @throws OCSBadRequestException Bad request
	 * @throws OCSForbiddenException Insufficient permissions
	 * @throws OCSException Other error
	 */
	#[ApiRoute(verb: 'DELETE', url: '/api/v1/webhooks/{id}')]
	#[AuthorizedAdminSetting(settings:Admin::class)]
	#[AppApiAdminAccessWithoutUser]
	public function destroy(int $id): DataResponse {
		try {
			$deleted = $this->mapper->deleteById($id);
			return new DataResponse($deleted);
		} catch (\UnexpectedValueException $e) {
			throw new OCSBadRequestException($e->getMessage(), $e);
		} catch (\DomainException $e) {
			throw new OCSForbiddenException($e->getMessage(), $e);
		} catch (\Exception $e) {
			$this->logger->error('Error when deleting flow with id ' . $id, ['exception' => $e]);
			throw new OCSException('An internal error occurred', Http::STATUS_INTERNAL_SERVER_ERROR, $e);
		}
	}

	/**
	 * Remove all existing webhook registration mapped to an AppAPI app id
	 *
	 * @param string $appid id of the app, as in the ex-app-id for creation
	 *
	 * @return DataResponse<Http::STATUS_OK, int, array{}>
	 *
	 * 200: Integer number of registrations deleted
	 *
	 * @throws OCSBadRequestException Bad request
	 * @throws OCSForbiddenException Insufficient permissions
	 * @throws OCSException Other error
	 */
	#[ApiRoute(verb: 'DELETE', url: '/api/v1/webhooks/byappid/{appid}')]
	#[AuthorizedAdminSetting(settings:Admin::class)]
	#[AppApiAdminAccessWithoutUser]
	public function deleteByAppId(string $appid): DataResponse {
		try {
			$deletedCount = $this->mapper->deleteByAppId($appid);
			return new DataResponse($deletedCount);
		} catch (\UnexpectedValueException $e) {
			throw new OCSBadRequestException($e->getMessage(), $e);
		} catch (\DomainException $e) {
			throw new OCSForbiddenException($e->getMessage(), $e);
		} catch (\Exception $e) {
			$this->logger->error('Error when deleting flows for app id ' . $appid, ['exception' => $e]);
			throw new OCSException('An internal error occurred', Http::STATUS_INTERNAL_SERVER_ERROR, $e);
		}
	}
}
