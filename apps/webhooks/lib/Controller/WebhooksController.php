<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Webhooks\Controller;

use Doctrine\DBAL\Exception;
use OCA\Webhooks\Db\AuthMethod;
use OCA\Webhooks\Db\WebhookListenerMapper;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\ISession;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type WebhooksListenerInfo from ResponseDefinitions
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
	 * @return DataResponse<Http::STATUS_OK, WebhooksListenerInfo[], array{}>
	 *
	 * 200: Webhook registrations returned
	 */
	#[ApiRoute(verb: 'GET', url: '/api/v1/webhooks')]
	#[AuthorizedAdminSetting(settings:'OCA\Webhooks\Settings\Admin')]
	public function index(): DataResponse {
		$webhookListeners = $this->mapper->getAll();

		return new DataResponse($webhookListeners);
	}

	/**
	 * Get details on a registered webhook
	 *
	 * @param int $id id of the webhook
	 *
	 * @return DataResponse<Http::STATUS_OK, WebhooksListenerInfo, array{}>
	 *
	 * 200: Webhook registration returned
	 */
	#[ApiRoute(verb: 'GET', url: '/api/v1/webhooks/{id}')]
	#[AuthorizedAdminSetting(settings:'OCA\Webhooks\Settings\Admin')]
	public function show(int $id): DataResponse {
		return new DataResponse($this->mapper->getById($id));
	}

	/**
	 * Register a new webhook
	 *
	 * @param string $httpMethod HTTP method to use to contact the webhook
	 * @param string $uri Webhook URI endpoint
	 * @param string $event Event class name to listen to
	 * @param ?array<string,mixed> $eventFilter Mongo filter to apply to the serialized data to decide if firing
	 * @param ?array<string,string> $headers Array of headers to send
	 * @param "none"|"headers"|null $authMethod Authentication method to use
	 * @param ?array<string,mixed> $authData Array of data for authentication
	 *
	 * @return DataResponse<Http::STATUS_OK, WebhooksListenerInfo, array{}>
	 *
	 * 200: Webhook registration returned
	 *
	 * @throws OCSBadRequestException Bad request
	 * @throws OCSForbiddenException Insufficient permissions
	 * @throws OCSException Other error
	 */
	#[ApiRoute(verb: 'POST', url: '/api/v1/webhooks')]
	#[AuthorizedAdminSetting(settings:'OCA\Webhooks\Settings\Admin')]
	public function create(
		string $httpMethod,
		string $uri,
		string $event,
		?array $eventFilter,
		?array $headers,
		?string $authMethod,
		?array $authData,
	): DataResponse {
		$appId = null;
		if ($this->session->get('app_api') === true) {
			$appId = $this->request->getHeader('EX-APP-ID');
		}
		try {
			$webhookListener = $this->mapper->addWebhookListener(
				$appId,
				$this->userId,
				$httpMethod,
				$uri,
				$event,
				$eventFilter,
				$headers,
				AuthMethod::from($authMethod ?? AuthMethod::None->value),
				$authData,
			);
			return new DataResponse($webhookListener);
		} catch (\UnexpectedValueException $e) {
			throw new OCSBadRequestException($e->getMessage(), $e);
		} catch (\DomainException $e) {
			throw new OCSForbiddenException($e->getMessage(), $e);
		} catch (\Exception $e) {
			$this->logger->error('Error when inserting webhook', ['exception' => $e]);
			throw new OCSException('An internal error occurred', $e->getCode(), $e);
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
	 * @param ?array<string,string> $headers Array of headers to send
	 * @param "none"|"headers"|null $authMethod Authentication method to use
	 * @param ?array<string,mixed> $authData Array of data for authentication
	 *
	 * @return DataResponse<Http::STATUS_OK, WebhooksListenerInfo, array{}>
	 *
	 * 200: Webhook registration returned
	 *
	 * @throws OCSBadRequestException Bad request
	 * @throws OCSForbiddenException Insufficient permissions
	 * @throws OCSException Other error
	 */
	#[ApiRoute(verb: 'POST', url: '/api/v1/webhooks/{id}')]
	#[AuthorizedAdminSetting(settings:'OCA\Webhooks\Settings\Admin')]
	public function update(
		int $id,
		string $httpMethod,
		string $uri,
		string $event,
		?array $eventFilter,
		?array $headers,
		?string $authMethod,
		?array $authData,
	): DataResponse {
		$appId = null;
		if ($this->session->get('app_api') === true) {
			$appId = $this->request->getHeader('EX-APP-ID');
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
				$headers,
				AuthMethod::from($authMethod ?? AuthMethod::None->value),
				$authData,
			);
			return new DataResponse($webhookListener);
		} catch (\UnexpectedValueException $e) {
			throw new OCSBadRequestException($e->getMessage(), $e);
		} catch (\DomainException $e) {
			throw new OCSForbiddenException($e->getMessage(), $e);
		} catch (\Exception $e) {
			$this->logger->error('Error when updating flow with id ' . $id, ['exception' => $e]);
			throw new OCSException('An internal error occurred', $e->getCode(), $e);
		}
	}

	/**
	 * Remove an existing webhook registration
	 *
	 * @param int $id id of the webhook
	 *
	 * @return DataResponse<Http::STATUS_OK, bool, array{}>
	 *
	 * 200: Boolean returned whether something was deleted FIXME
	 *
	 * @throws OCSBadRequestException Bad request
	 * @throws OCSForbiddenException Insufficient permissions
	 * @throws OCSException Other error
	 */
	#[ApiRoute(verb: 'DELETE', url: '/api/v1/webhooks/{id}')]
	#[AuthorizedAdminSetting(settings:'OCA\Webhooks\Settings\Admin')]
	public function destroy(int $id): DataResponse {
		try {
			$deleted = $this->mapper->deleteById($id);
			return new DataResponse($deleted);
		} catch (\UnexpectedValueException $e) {
			throw new OCSBadRequestException($e->getMessage(), $e);
		} catch (\DomainException $e) {
			throw new OCSForbiddenException($e->getMessage(), $e);
		} catch (Exception $e) {
			$this->logger->error('Error when deleting flow with id ' . $id, ['exception' => $e]);
			throw new OCSException('An internal error occurred', $e->getCode(), $e);
		}
	}
}
