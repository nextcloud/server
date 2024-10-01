<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Controller;

use OC\Authentication\WebAuthn\Manager;
use OCA\Settings\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Webauthn\PublicKeyCredentialCreationOptions;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class WebAuthnController extends Controller {
	private const WEBAUTHN_REGISTRATION = 'webauthn_registration';

	public function __construct(
		IRequest $request,
		private LoggerInterface $logger,
		private Manager $manager,
		private IUserSession $userSession,
		private ISession $session,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * @NoSubAdminRequired
	 */
	#[NoAdminRequired]
	#[PasswordConfirmationRequired]
	#[UseSession]
	#[NoCSRFRequired]
	public function startRegistration(): JSONResponse {
		$this->logger->debug('Starting WebAuthn registration');

		$credentialOptions = $this->manager->startRegistration($this->userSession->getUser(), $this->request->getServerHost());

		// Set this in the session since we need it on finish
		$this->session->set(self::WEBAUTHN_REGISTRATION, $credentialOptions);

		return new JSONResponse($credentialOptions);
	}

	/**
	 * @NoSubAdminRequired
	 */
	#[NoAdminRequired]
	#[PasswordConfirmationRequired]
	#[UseSession]
	public function finishRegistration(string $name, string $data): JSONResponse {
		$this->logger->debug('Finishing WebAuthn registration');

		if (!$this->session->exists(self::WEBAUTHN_REGISTRATION)) {
			$this->logger->debug('Trying to finish WebAuthn registration without session data');
			return new JSONResponse([], Http::STATUS_BAD_REQUEST);
		}

		// Obtain the publicKeyCredentialOptions from when we started the registration
		$publicKeyCredentialCreationOptions = PublicKeyCredentialCreationOptions::createFromArray($this->session->get(self::WEBAUTHN_REGISTRATION));

		$this->session->remove(self::WEBAUTHN_REGISTRATION);

		return new JSONResponse($this->manager->finishRegister($publicKeyCredentialCreationOptions, $name, $data));
	}

	/**
	 * @NoSubAdminRequired
	 */
	#[NoAdminRequired]
	#[PasswordConfirmationRequired]
	public function deleteRegistration(int $id): JSONResponse {
		$this->logger->debug('Finishing WebAuthn registration');

		$this->manager->deleteRegistration($this->userSession->getUser(), $id);

		return new JSONResponse([]);
	}
}
