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

	#[PublicPage]
	#[UseSession]
	#[FrontpageRoute(verb: 'POST', url: 'login/webauthn/start')]
	public function startAuthentication(?string $loginName = null): JSONResponse {
		$this->logger->debug('Starting WebAuthn login');

		$uid = null;
		if ($loginName !== null && $loginName !== '') {
			$this->logger->debug('Converting login name to UID');
			$uid = $loginName;
			Util::emitHook(
				'\OCA\Files_Sharing\API\Server2Server',
				'preLoginNameUsedAsUserName',
				['uid' => &$uid]
			);
			$this->logger->debug('Got UID: ' . $uid);
		}

		$publicKeyCredentialRequestOptions = $this->webAuthnManger->startAuthentication($uid, $this->request->getServerHost());
		$this->session->set(self::WEBAUTHN_LOGIN, json_encode($publicKeyCredentialRequestOptions));
		if ($uid !== null && $uid !== '') {
			$this->session->set(self::WEBAUTHN_LOGIN_UID, $uid);
		} else {
			$this->session->remove(self::WEBAUTHN_LOGIN_UID);
		}

		return new JSONResponse($publicKeyCredentialRequestOptions);
	}

	#[PublicPage]
	#[UseSession]
	#[FrontpageRoute(verb: 'POST', url: 'login/webauthn/finish')]
	public function finishAuthentication(string $data): JSONResponse {
		$this->logger->debug('Validating WebAuthn login');

		if (!$this->session->exists(self::WEBAUTHN_LOGIN)) {
			$this->logger->debug('Trying to finish WebAuthn login without session data');
			return new JSONResponse([], Http::STATUS_BAD_REQUEST);
		}

		// Obtain the publicKeyCredentialOptions from when we started the registration
		$publicKeyCredentialRequestOptions = PublicKeyCredentialRequestOptions::createFromString($this->session->get(self::WEBAUTHN_LOGIN));
		$uidFromSession = $this->session->get(self::WEBAUTHN_LOGIN_UID);
		$this->session->remove(self::WEBAUTHN_LOGIN);
		$this->session->remove(self::WEBAUTHN_LOGIN_UID);
		$publicKeyCredentialSource = $this->webAuthnManger->finishAuthentication($publicKeyCredentialRequestOptions, $data, $uidFromSession);
		$uid = $uidFromSession ?? $publicKeyCredentialSource->getUserHandle();

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
