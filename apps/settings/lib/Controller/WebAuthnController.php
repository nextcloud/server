<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Kate Döen <kate.doeen@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Settings\Controller;

use OC\Authentication\WebAuthn\Manager;
use OCA\Settings\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
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
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 * @PasswordConfirmationRequired
	 * @UseSession
	 * @NoCSRFRequired
	 */
	public function startRegistration(): JSONResponse {
		$this->logger->debug('Starting WebAuthn registration');

		$credentialOptions = $this->manager->startRegistration($this->userSession->getUser(), $this->request->getServerHost());

		// Set this in the session since we need it on finish
		$this->session->set(self::WEBAUTHN_REGISTRATION, $credentialOptions);

		return new JSONResponse($credentialOptions);
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 * @PasswordConfirmationRequired
	 * @UseSession
	 */
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
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 * @PasswordConfirmationRequired
	 */
	public function deleteRegistration(int $id): JSONResponse {
		$this->logger->debug('Finishing WebAuthn registration');

		$this->manager->deleteRegistration($this->userSession->getUser(), $id);

		return new JSONResponse([]);
	}
}
