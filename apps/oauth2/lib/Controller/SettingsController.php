<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\OAuth2\Controller;

use OCA\OAuth2\Service\ClientService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;

class SettingsController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private IL10N $l,
		private readonly ClientService $clientService,
	) {
		parent::__construct($appName, $request);
	}

	#[PasswordConfirmationRequired(strict: true)]
	public function addClient(string $name,
		string $redirectUri): JSONResponse {
		if (filter_var($redirectUri, FILTER_VALIDATE_URL) === false) {
			return new JSONResponse(['message' => $this->l->t('Your redirect URL needs to be a full URL for example: https://yourdomain.com/path')], Http::STATUS_BAD_REQUEST);
		}

		$result = $this->clientService->addClient($name, $redirectUri);

		return new JSONResponse($result);
	}

	#[PasswordConfirmationRequired]
	public function deleteClient(int $id): JSONResponse {
		$this->clientService->deleteClient($id);
		return new JSONResponse([]);
	}
}
