<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Controller;

use OC\Authentication\Events\AppPasswordCreatedEvent;
use OC\Authentication\Exceptions\PasswordlessTokenException;
use OC\Authentication\Token\IProvider;
use OCA\OAuth2\Db\AccessToken;
use OCA\OAuth2\Db\AccessTokenMapper;
use OCA\OAuth2\Db\ClientMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\StandaloneTemplateResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\Authentication\Token\IToken;
use OCP\Defaults;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;
use OCP\Session\Exceptions\SessionNotAvailableException;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class ClientFlowLoginController extends Controller {
	public const STATE_NAME = 'client.flow.state.token';

	public function __construct(
		string $appName,
		IRequest $request,
		private IUserSession $userSession,
		private IL10N $l10n,
		private Defaults $defaults,
		private ISession $session,
		private IProvider $tokenProvider,
		private ISecureRandom $random,
		private IURLGenerator $urlGenerator,
		private ClientMapper $clientMapper,
		private AccessTokenMapper $accessTokenMapper,
		private ICrypto $crypto,
		private IEventDispatcher $eventDispatcher,
		private ITimeFactory $timeFactory,
		private IConfig $config,
	) {
		parent::__construct($appName, $request);
	}

	private function getClientName(): string {
		$userAgent = $this->request->getHeader('user-agent');
		return $userAgent !== '' ? $userAgent : 'unknown';
	}

	private function isValidToken(string $stateToken): bool {
		$currentToken = $this->session->get(self::STATE_NAME);
		if (!is_string($currentToken)) {
			return false;
		}
		return hash_equals($currentToken, $stateToken);
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

	#[PublicPage]
	#[NoCSRFRequired]
	#[UseSession]
	#[FrontpageRoute(verb: 'GET', url: '/login/flow')]
	public function showAuthPickerPage(string $clientIdentifier = '', string $user = '', int $direct = 0, string $providedRedirectUri = ''): StandaloneTemplateResponse {
		$clientName = $this->getClientName();
		$client = null;
		if ($clientIdentifier !== '') {
			$client = $this->clientMapper->getByIdentifier($clientIdentifier);
			$clientName = $client->getName();
		}

		// No valid clientIdentifier given and no valid API Request (APIRequest header not set)
		$clientRequest = $this->request->getHeader('OCS-APIREQUEST');
		if ($clientRequest !== 'true' && $client === null) {
			return new StandaloneTemplateResponse(
				$this->appName,
				'error',
				[
					'errors' =>
					[
						[
							'error' => 'Access Forbidden',
							'hint' => 'Invalid request',
						],
					],
				],
				'guest'
			);
		}

		$stateToken = $this->random->generate(
			64,
			ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_DIGITS
		);
		$this->session->set(self::STATE_NAME, $stateToken);

		$csp = new ContentSecurityPolicy();
		if ($client) {
			$csp->addAllowedFormActionDomain($client->getRedirectUri());
		} else {
			$csp->addAllowedFormActionDomain('nc://*');
		}

		$response = new StandaloneTemplateResponse(
			$this->appName,
			'loginflow/authpicker',
			[
				'client' => $clientName,
				'clientIdentifier' => $clientIdentifier,
				'instanceName' => $this->defaults->getName(),
				'urlGenerator' => $this->urlGenerator,
				'stateToken' => $stateToken,
				'serverHost' => $this->getServerPath(),
				'oauthState' => $this->session->get('oauth.state'),
				'user' => $user,
				'direct' => $direct,
				'providedRedirectUri' => $providedRedirectUri,
			],
			'guest'
		);

		$response->setContentSecurityPolicy($csp);
		return $response;
	}

	/**
	 * @NoSameSiteCookieRequired
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[UseSession]
	#[FrontpageRoute(verb: 'GET', url: '/login/flow/grant')]
	public function grantPage(
		string $stateToken = '',
		string $clientIdentifier = '',
		int $direct = 0,
		string $providedRedirectUri = '',
	): Response {
		if (!$this->isValidToken($stateToken)) {
			return $this->stateTokenForbiddenResponse();
		}

		$clientName = $this->getClientName();
		$client = null;
		if ($clientIdentifier !== '') {
			$client = $this->clientMapper->getByIdentifier($clientIdentifier);
			$clientName = $client->getName();
		}

		$csp = new ContentSecurityPolicy();
		if ($client) {
			$csp->addAllowedFormActionDomain($client->getRedirectUri());
		} else {
			$csp->addAllowedFormActionDomain('nc://*');
		}

		/** @var IUser $user */
		$user = $this->userSession->getUser();

		$response = new StandaloneTemplateResponse(
			$this->appName,
			'loginflow/grant',
			[
				'userId' => $user->getUID(),
				'userDisplayName' => $user->getDisplayName(),
				'client' => $clientName,
				'clientIdentifier' => $clientIdentifier,
				'instanceName' => $this->defaults->getName(),
				'urlGenerator' => $this->urlGenerator,
				'stateToken' => $stateToken,
				'serverHost' => $this->getServerPath(),
				'oauthState' => $this->session->get('oauth.state'),
				'direct' => $direct,
				'providedRedirectUri' => $providedRedirectUri,
			],
			'guest'
		);

		$response->setContentSecurityPolicy($csp);
		return $response;
	}

	#[NoAdminRequired]
	#[UseSession]
	#[PasswordConfirmationRequired(strict: false)]
	#[FrontpageRoute(verb: 'POST', url: '/login/flow')]
	public function generateAppPassword(
		string $stateToken,
		string $clientIdentifier = '',
		string $providedRedirectUri = '',
	): Response {
		if (!$this->isValidToken($stateToken)) {
			$this->session->remove(self::STATE_NAME);
			return $this->stateTokenForbiddenResponse();
		}

		$this->session->remove(self::STATE_NAME);

		try {
			$sessionId = $this->session->getId();
		} catch (SessionNotAvailableException $ex) {
			$response = new Response();
			$response->setStatus(Http::STATUS_FORBIDDEN);
			return $response;
		}

		try {
			$sessionToken = $this->tokenProvider->getToken($sessionId);
			$loginName = $sessionToken->getLoginName();
			try {
				$password = $this->tokenProvider->getPassword($sessionToken, $sessionId);
			} catch (PasswordlessTokenException $ex) {
				$password = null;
			}
		} catch (InvalidTokenException $ex) {
			$response = new Response();
			$response->setStatus(Http::STATUS_FORBIDDEN);
			return $response;
		}

		$clientName = $this->getClientName();
		$client = false;
		if ($clientIdentifier !== '') {
			$client = $this->clientMapper->getByIdentifier($clientIdentifier);
			$clientName = $client->getName();
		}

		$token = $this->random->generate(72, ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS);
		$uid = $this->userSession->getUser()->getUID();
		$generatedToken = $this->tokenProvider->generateToken(
			$token,
			$uid,
			$loginName,
			$password,
			$clientName,
			IToken::PERMANENT_TOKEN,
			IToken::DO_NOT_REMEMBER
		);

		if ($client) {
			$code = $this->random->generate(128, ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS);
			$accessToken = new AccessToken();
			$accessToken->setClientId($client->getId());
			$accessToken->setEncryptedToken($this->crypto->encrypt($token, $code));
			$accessToken->setHashedCode(hash('sha512', $code));
			$accessToken->setTokenId($generatedToken->getId());
			$accessToken->setCodeCreatedAt($this->timeFactory->now()->getTimestamp());
			$this->accessTokenMapper->insert($accessToken);

			$enableOcClients = $this->config->getSystemValueBool('oauth2.enable_oc_clients', false);

			$redirectUri = $client->getRedirectUri();
			if ($enableOcClients && $redirectUri === 'http://localhost:*') {
				// Sanity check untrusted redirect URI provided by the client first
				if (!preg_match('/^http:\/\/localhost:[0-9]+$/', $providedRedirectUri)) {
					$response = new Response();
					$response->setStatus(Http::STATUS_FORBIDDEN);
					return $response;
				}

				$redirectUri = $providedRedirectUri;
			}

			if (parse_url($redirectUri, PHP_URL_QUERY)) {
				$redirectUri .= '&';
			} else {
				$redirectUri .= '?';
			}

			$redirectUri .= sprintf(
				'state=%s&code=%s',
				urlencode($this->session->get('oauth.state')),
				urlencode($code)
			);
			$this->session->remove('oauth.state');
		} else {
			$redirectUri = 'nc://login/server:' . $this->getServerPath() . '&user:' . urlencode($loginName) . '&password:' . urlencode($token);

			// Clear the token from the login here
			$this->tokenProvider->invalidateToken($sessionId);
		}

		$this->eventDispatcher->dispatchTyped(
			new AppPasswordCreatedEvent($generatedToken)
		);

		return new RedirectResponse($redirectUri);
	}

	#[PublicPage]
	#[FrontpageRoute(verb: 'POST', url: '/login/flow/apptoken')]
	public function apptokenRedirect(string $stateToken, string $user, string $password): Response {
		if (!$this->isValidToken($stateToken)) {
			return $this->stateTokenForbiddenResponse();
		}

		try {
			$token = $this->tokenProvider->getToken($password);
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

		$redirectUri = 'nc://login/server:' . $this->getServerPath() . '&user:' . urlencode($user) . '&password:' . urlencode($password);
		return new RedirectResponse($redirectUri);
	}

	private function getServerPath(): string {
		$serverPostfix = '';

		if (str_contains($this->request->getRequestUri(), '/index.php')) {
			$serverPostfix = substr($this->request->getRequestUri(), 0, strpos($this->request->getRequestUri(), '/index.php'));
		} elseif (str_contains($this->request->getRequestUri(), '/login/flow')) {
			$serverPostfix = substr($this->request->getRequestUri(), 0, strpos($this->request->getRequestUri(), '/login/flow'));
		}

		$protocol = $this->request->getServerProtocol();

		if ($protocol !== 'https') {
			$xForwardedProto = $this->request->getHeader('X-Forwarded-Proto');
			$xForwardedSSL = $this->request->getHeader('X-Forwarded-Ssl');
			if ($xForwardedProto === 'https' || $xForwardedSSL === 'on') {
				$protocol = 'https';
			}
		}

		return $protocol . '://' . $this->request->getServerHost() . $serverPostfix;
	}
}
