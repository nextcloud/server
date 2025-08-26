<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Controller;

use OC\Core\Db\LoginFlowV2;
use OC\Core\Exception\LoginFlowV2ClientForbiddenException;
use OC\Core\Exception\LoginFlowV2NotFoundException;
use OC\Core\ResponseDefinitions;
use OC\Core\Service\LoginFlowV2Service;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\StandaloneTemplateResponse;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\Defaults;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Security\ISecureRandom;
use OCP\Server;

/**
 * @psalm-import-type CoreLoginFlowV2Credentials from ResponseDefinitions
 * @psalm-import-type CoreLoginFlowV2 from ResponseDefinitions
 */
class ClientFlowLoginV2Controller extends Controller {
	public const TOKEN_NAME = 'client.flow.v2.login.token';
	public const STATE_NAME = 'client.flow.v2.state.token';
	// Denotes that the session was created for the login flow and should therefore be ephemeral.
	public const EPHEMERAL_NAME = 'client.flow.v2.state.ephemeral';

	public function __construct(
		string $appName,
		IRequest $request,
		private LoginFlowV2Service $loginFlowV2Service,
		private IURLGenerator $urlGenerator,
		private ISession $session,
		private IUserSession $userSession,
		private ISecureRandom $random,
		private Defaults $defaults,
		private ?string $userId,
		private IL10N $l10n,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Poll the login flow credentials
	 *
	 * @param string $token Token of the flow
	 * @return JSONResponse<Http::STATUS_OK, CoreLoginFlowV2Credentials, array{}>|JSONResponse<Http::STATUS_NOT_FOUND, list<empty>, array{}>
	 *
	 * 200: Login flow credentials returned
	 * 404: Login flow not found or completed
	 */
	#[NoCSRFRequired]
	#[PublicPage]
	#[FrontpageRoute(verb: 'POST', url: '/login/v2/poll')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
	public function poll(string $token): JSONResponse {
		try {
			$creds = $this->loginFlowV2Service->poll($token);
		} catch (LoginFlowV2NotFoundException $e) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}

		return new JSONResponse($creds->jsonSerialize());
	}

	#[NoCSRFRequired]
	#[PublicPage]
	#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
	#[UseSession]
	#[FrontpageRoute(verb: 'GET', url: '/login/v2/flow/{token}')]
	public function landing(string $token, $user = '', int $direct = 0): Response {
		if (!$this->loginFlowV2Service->startLoginFlow($token)) {
			return $this->loginTokenForbiddenResponse();
		}

		$this->session->set(self::TOKEN_NAME, $token);

		return new RedirectResponse(
			$this->urlGenerator->linkToRouteAbsolute('core.ClientFlowLoginV2.showAuthPickerPage', ['user' => $user, 'direct' => $direct])
		);
	}

	#[NoCSRFRequired]
	#[PublicPage]
	#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
	#[UseSession]
	#[FrontpageRoute(verb: 'GET', url: '/login/v2/flow')]
	public function showAuthPickerPage(string $user = '', int $direct = 0): StandaloneTemplateResponse {
		try {
			$flow = $this->getFlowByLoginToken();
		} catch (LoginFlowV2NotFoundException $e) {
			return $this->loginTokenForbiddenResponse();
		} catch (LoginFlowV2ClientForbiddenException $e) {
			return $this->loginTokenForbiddenClientResponse();
		}

		$stateToken = $this->random->generate(
			64,
			ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_DIGITS
		);
		$this->session->set(self::STATE_NAME, $stateToken);

		return new StandaloneTemplateResponse(
			$this->appName,
			'loginflowv2/authpicker',
			[
				'client' => $flow->getClientName(),
				'instanceName' => $this->defaults->getName(),
				'urlGenerator' => $this->urlGenerator,
				'stateToken' => $stateToken,
				'user' => $user,
				'direct' => $direct,
			],
			'guest'
		);
	}

	/**
	 * @NoSameSiteCookieRequired
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
	#[UseSession]
	#[FrontpageRoute(verb: 'GET', url: '/login/v2/grant')]
	public function grantPage(?string $stateToken, int $direct = 0): StandaloneTemplateResponse {
		if ($stateToken === null) {
			return $this->stateTokenMissingResponse();
		}
		if (!$this->isValidStateToken($stateToken)) {
			return $this->stateTokenForbiddenResponse();
		}

		try {
			$flow = $this->getFlowByLoginToken();
		} catch (LoginFlowV2NotFoundException $e) {
			return $this->loginTokenForbiddenResponse();
		} catch (LoginFlowV2ClientForbiddenException $e) {
			return $this->loginTokenForbiddenClientResponse();
		}

		/** @var IUser $user */
		$user = $this->userSession->getUser();

		return new StandaloneTemplateResponse(
			$this->appName,
			'loginflowv2/grant',
			[
				'userId' => $user->getUID(),
				'userDisplayName' => $user->getDisplayName(),
				'client' => $flow->getClientName(),
				'instanceName' => $this->defaults->getName(),
				'urlGenerator' => $this->urlGenerator,
				'stateToken' => $stateToken,
				'direct' => $direct,
			],
			'guest'
		);
	}

	#[PublicPage]
	#[FrontpageRoute(verb: 'POST', url: '/login/v2/apptoken')]
	public function apptokenRedirect(?string $stateToken, string $user, string $password) {
		if ($stateToken === null) {
			return $this->stateTokenMissingResponse();
		}

		if (!$this->isValidStateToken($stateToken)) {
			return $this->stateTokenForbiddenResponse();
		}

		try {
			$this->getFlowByLoginToken();
		} catch (LoginFlowV2NotFoundException $e) {
			return $this->loginTokenForbiddenResponse();
		} catch (LoginFlowV2ClientForbiddenException $e) {
			return $this->loginTokenForbiddenClientResponse();
		}

		$loginToken = $this->session->get(self::TOKEN_NAME);

		// Clear session variables
		$this->session->remove(self::TOKEN_NAME);
		$this->session->remove(self::STATE_NAME);

		try {
			$token = Server::get(\OC\Authentication\Token\IProvider::class)->getToken($password);
			if ($token->getLoginName() !== $user) {
				throw new InvalidTokenException('login name does not match');
			}
		} catch (InvalidTokenException $e) {
			$response = new StandaloneTemplateResponse(
				$this->appName,
				'403',
				[
					'message' => $this->l10n->t('Invalid app password'),
				],
				'guest'
			);
			$response->setStatus(Http::STATUS_FORBIDDEN);
			return $response;
		}

		$result = $this->loginFlowV2Service->flowDoneWithAppPassword($loginToken, $this->getServerPath(), $token->getLoginName(), $password);
		return $this->handleFlowDone($result);
	}

	#[NoAdminRequired]
	#[UseSession]
	#[PasswordConfirmationRequired(strict: false)]
	#[FrontpageRoute(verb: 'POST', url: '/login/v2/grant')]
	public function generateAppPassword(?string $stateToken): Response {
		if ($stateToken === null) {
			return $this->stateTokenMissingResponse();
		}
		if (!$this->isValidStateToken($stateToken)) {
			return $this->stateTokenForbiddenResponse();
		}

		try {
			$this->getFlowByLoginToken();
		} catch (LoginFlowV2NotFoundException $e) {
			return $this->loginTokenForbiddenResponse();
		} catch (LoginFlowV2ClientForbiddenException $e) {
			return $this->loginTokenForbiddenClientResponse();
		}

		$loginToken = $this->session->get(self::TOKEN_NAME);

		// Clear session variables
		$this->session->remove(self::TOKEN_NAME);
		$this->session->remove(self::STATE_NAME);
		$sessionId = $this->session->getId();

		$result = $this->loginFlowV2Service->flowDone($loginToken, $sessionId, $this->getServerPath(), $this->userId);
		return $this->handleFlowDone($result);
	}

	private function handleFlowDone(bool $result): StandaloneTemplateResponse {
		if ($result) {
			return new StandaloneTemplateResponse(
				$this->appName,
				'loginflowv2/done',
				[],
				'guest'
			);
		}

		$response = new StandaloneTemplateResponse(
			$this->appName,
			'403',
			[
				'message' => $this->l10n->t('Could not complete login'),
			],
			'guest'
		);
		$response->setStatus(Http::STATUS_FORBIDDEN);
		return $response;
	}

	/**
	 * Init a login flow
	 *
	 * @return JSONResponse<Http::STATUS_OK, CoreLoginFlowV2, array{}>
	 *
	 * 200: Login flow init returned
	 */
	#[NoCSRFRequired]
	#[PublicPage]
	#[FrontpageRoute(verb: 'POST', url: '/login/v2')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
	public function init(): JSONResponse {
		// Get client user agent
		$userAgent = $this->request->getHeader('user-agent');

		$tokens = $this->loginFlowV2Service->createTokens($userAgent);

		$data = [
			'poll' => [
				'token' => $tokens->getPollToken(),
				'endpoint' => $this->urlGenerator->linkToRouteAbsolute('core.ClientFlowLoginV2.poll')
			],
			'login' => $this->urlGenerator->linkToRouteAbsolute('core.ClientFlowLoginV2.landing', ['token' => $tokens->getLoginToken()]),
		];

		return new JSONResponse($data);
	}

	private function isValidStateToken(string $stateToken): bool {
		$currentToken = $this->session->get(self::STATE_NAME);
		if (!is_string($stateToken) || !is_string($currentToken)) {
			return false;
		}
		return hash_equals($currentToken, $stateToken);
	}

	private function stateTokenMissingResponse(): StandaloneTemplateResponse {
		$response = new StandaloneTemplateResponse(
			$this->appName,
			'403',
			[
				'message' => $this->l10n->t('State token missing'),
			],
			'guest'
		);
		$response->setStatus(Http::STATUS_FORBIDDEN);
		return $response;
	}

	private function stateTokenForbiddenResponse(): StandaloneTemplateResponse {
		$response = new StandaloneTemplateResponse(
			$this->appName,
			'403',
			[
				'message' => $this->l10n->t('State token does not match'),
			],
			'guest'
		);
		$response->setStatus(Http::STATUS_FORBIDDEN);
		return $response;
	}

	/**
	 * @return LoginFlowV2
	 * @throws LoginFlowV2NotFoundException
	 * @throws LoginFlowV2ClientForbiddenException
	 */
	private function getFlowByLoginToken(): LoginFlowV2 {
		$currentToken = $this->session->get(self::TOKEN_NAME);
		if (!is_string($currentToken)) {
			throw new LoginFlowV2NotFoundException('Login token not set in session');
		}

		return $this->loginFlowV2Service->getByLoginToken($currentToken);
	}

	private function loginTokenForbiddenResponse(): StandaloneTemplateResponse {
		$response = new StandaloneTemplateResponse(
			$this->appName,
			'403',
			[
				'message' => $this->l10n->t('Your login token is invalid or has expired'),
			],
			'guest'
		);
		$response->setStatus(Http::STATUS_FORBIDDEN);
		return $response;
	}

	private function loginTokenForbiddenClientResponse(): StandaloneTemplateResponse {
		$response = new StandaloneTemplateResponse(
			$this->appName,
			'403',
			[
				'message' => $this->l10n->t('Please use original client'),
			],
			'guest'
		);
		$response->setStatus(Http::STATUS_FORBIDDEN);
		return $response;
	}

	private function getServerPath(): string {
		$serverPostfix = '';

		if (str_contains($this->request->getRequestUri(), '/index.php')) {
			$serverPostfix = substr($this->request->getRequestUri(), 0, strpos($this->request->getRequestUri(), '/index.php'));
		} elseif (str_contains($this->request->getRequestUri(), '/login/v2')) {
			$serverPostfix = substr($this->request->getRequestUri(), 0, strpos($this->request->getRequestUri(), '/login/v2'));
		}

		$protocol = $this->request->getServerProtocol();
		return $protocol . '://' . $this->request->getServerHost() . $serverPostfix;
	}
}
