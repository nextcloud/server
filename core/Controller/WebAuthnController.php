<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
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
namespace OC\Core\Controller;

use OC\Authentication\Login\LoginData;
use OC\Authentication\Login\WebAuthnChain;
use OC\Authentication\WebAuthn\Manager;
use OC\URLGenerator;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\ISession;
use OCP\Util;
use Psr\Log\LoggerInterface;
use Webauthn\PublicKeyCredentialRequestOptions;

class WebAuthnController extends Controller {
	private const WEBAUTHN_LOGIN = 'webauthn_login';
	private const WEBAUTHN_LOGIN_UID = 'webauthn_login_uid';

	public function __construct(
		string $appName,
		IRequest $request,
		private Manager $webAuthnManger,
		private ISession $session,
		private LoggerInterface $logger,
		private WebAuthnChain $webAuthnChain,
		private URLGenerator $urlGenerator,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 * @PublicPage
	 */
	#[UseSession]
	#[FrontpageRoute(verb: 'POST', url: 'login/webauthn/start')]
	public function startAuthentication(string $loginName): JSONResponse {
		$this->logger->debug('Starting WebAuthn login');

		$this->logger->debug('Converting login name to UID');
		$uid = $loginName;
		Util::emitHook(
			'\OCA\Files_Sharing\API\Server2Server',
			'preLoginNameUsedAsUserName',
			['uid' => &$uid]
		);
		$this->logger->debug('Got UID: ' . $uid);

		$publicKeyCredentialRequestOptions = $this->webAuthnManger->startAuthentication($uid, $this->request->getServerHost());
		$this->session->set(self::WEBAUTHN_LOGIN, json_encode($publicKeyCredentialRequestOptions));
		$this->session->set(self::WEBAUTHN_LOGIN_UID, $uid);

		return new JSONResponse($publicKeyCredentialRequestOptions);
	}

	/**
	 * @NoAdminRequired
	 * @PublicPage
	 */
	#[UseSession]
	#[FrontpageRoute(verb: 'POST', url: 'login/webauthn/finish')]
	public function finishAuthentication(string $data): JSONResponse {
		$this->logger->debug('Validating WebAuthn login');

		if (!$this->session->exists(self::WEBAUTHN_LOGIN) || !$this->session->exists(self::WEBAUTHN_LOGIN_UID)) {
			$this->logger->debug('Trying to finish WebAuthn login without session data');
			return new JSONResponse([], Http::STATUS_BAD_REQUEST);
		}

		// Obtain the publicKeyCredentialOptions from when we started the registration
		$publicKeyCredentialRequestOptions = PublicKeyCredentialRequestOptions::createFromString($this->session->get(self::WEBAUTHN_LOGIN));
		$uid = $this->session->get(self::WEBAUTHN_LOGIN_UID);
		$this->webAuthnManger->finishAuthentication($publicKeyCredentialRequestOptions, $data, $uid);

		//TODO: add other parameters
		$loginData = new LoginData(
			$this->request,
			$uid,
			''
		);
		$this->webAuthnChain->process($loginData);

		return new JSONResponse([
			'defaultRedirectUrl' => $this->urlGenerator->linkToDefaultPageUrl(),
		]);
	}
}
