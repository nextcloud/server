<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Controller;

use OC\Authentication\Login\LoginData;
use OC\Authentication\Login\WebAuthnChain;
use OC\Authentication\WebAuthn\Manager;
use OC\URLGenerator;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserManager;
use OCP\Util;
use Psr\Log\LoggerInterface;
use Webauthn\PublicKeyCredentialRequestOptions;

class WebAuthnController extends Controller {
	private const string WEBAUTHN_LOGIN = 'webauthn_login';
	private const string WEBAUTHN_LOGIN_UID = 'webauthn_login_uid';
	private const string WEBAUTHN_LOGIN_NAME = 'webauthn_login_name';

	public function __construct(
		string $appName,
		IRequest $request,
		private Manager $webAuthnManger,
		private ISession $session,
		private LoggerInterface $logger,
		private WebAuthnChain $webAuthnChain,
		private URLGenerator $urlGenerator,
		private IUserManager $userManager,
	) {
		parent::__construct($appName, $request);
	}

	#[PublicPage]
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
		$this->session->set(self::WEBAUTHN_LOGIN_NAME, $loginName);

		return new JSONResponse($publicKeyCredentialRequestOptions);
	}

	#[PublicPage]
	#[UseSession]
	#[FrontpageRoute(verb: 'POST', url: 'login/webauthn/finish')]
	public function finishAuthentication(string $data): JSONResponse {
		$this->logger->debug('Validating WebAuthn login');

		if (!$this->session->exists(self::WEBAUTHN_LOGIN)
			|| !$this->session->exists(self::WEBAUTHN_LOGIN_UID)
			|| !$this->session->exists(self::WEBAUTHN_LOGIN_NAME)
		) {
			$this->logger->debug('Trying to finish WebAuthn login without session data');
			return new JSONResponse([], Http::STATUS_BAD_REQUEST);
		}

		// Obtain the publicKeyCredentialOptions from when we started the registration
		$publicKeyCredentialRequestOptions = PublicKeyCredentialRequestOptions::createFromString($this->session->get(self::WEBAUTHN_LOGIN));
		$uid = $this->session->get(self::WEBAUTHN_LOGIN_UID);
		$loginName = $this->session->get(self::WEBAUTHN_LOGIN_NAME);
		$authenticatorData = $this->webAuthnManger->finishAuthentication($publicKeyCredentialRequestOptions, $data, $uid);

		//TODO: add other parameters
		$loginData = new LoginData(
			$this->request,
			$loginName,
			''
		);
		$loginData->setUser($this->userManager->get($uid));
		$loginData->setWebAuthnUserVerified($authenticatorData->isUserVerified());
		$this->webAuthnChain->process($loginData);

		return new JSONResponse([
			'defaultRedirectUrl' => $this->urlGenerator->linkToDefaultPageUrl(),
		]);
	}
}
