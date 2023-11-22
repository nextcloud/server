<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Mario Danic <mario@lovelyhq.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author RussellAult <RussellAult@users.noreply.github.com>
 * @author Sergej Nikolaev <kinolaev@gmail.com>
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
namespace OC\Core\Controller;

use OC\Authentication\Events\AppPasswordCreatedEvent;
use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Exceptions\PasswordlessTokenException;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OCA\OAuth2\Db\AccessToken;
use OCA\OAuth2\Db\AccessTokenMapper;
use OCA\OAuth2\Db\ClientMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\IgnoreOpenAPI;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\StandaloneTemplateResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Defaults;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;
use OCP\Session\Exceptions\SessionNotAvailableException;

#[IgnoreOpenAPI]
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
	) {
		parent::__construct($appName, $request);
	}

	private function getClientName(): string {
		$userAgent = $this->request->getHeader('USER_AGENT');
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

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	#[UseSession]
	public function showAuthPickerPage(string $clientIdentifier = '', string $user = '', int $direct = 0): StandaloneTemplateResponse {
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
			ISecureRandom::CHAR_LOWER.ISecureRandom::CHAR_UPPER.ISecureRandom::CHAR_DIGITS
		);
		$this->session->set(self::STATE_NAME, $stateToken);

		$csp = new Http\ContentSecurityPolicy();
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
			],
			'guest'
		);

		$response->setContentSecurityPolicy($csp);
		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @NoSameSiteCookieRequired
	 */
	#[UseSession]
	public function grantPage(string $stateToken = '',
				  string $clientIdentifier = '',
				  int $direct = 0): StandaloneTemplateResponse {
		if (!$this->isValidToken($stateToken)) {
			return $this->stateTokenForbiddenResponse();
		}

		$clientName = $this->getClientName();
		$client = null;
		if ($clientIdentifier !== '') {
			$client = $this->clientMapper->getByIdentifier($clientIdentifier);
			$clientName = $client->getName();
		}

		$csp = new Http\ContentSecurityPolicy();
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
			],
			'guest'
		);

		$response->setContentSecurityPolicy($csp);
		return $response;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return Http\RedirectResponse|Response
	 */
	#[UseSession]
	public function generateAppPassword(string $stateToken,
										string $clientIdentifier = '') {
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

		$token = $this->random->generate(72, ISecureRandom::CHAR_UPPER.ISecureRandom::CHAR_LOWER.ISecureRandom::CHAR_DIGITS);
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
			$code = $this->random->generate(128, ISecureRandom::CHAR_UPPER.ISecureRandom::CHAR_LOWER.ISecureRandom::CHAR_DIGITS);
			$accessToken = new AccessToken();
			$accessToken->setClientId($client->getId());
			$accessToken->setEncryptedToken($this->crypto->encrypt($token, $code));
			$accessToken->setHashedCode(hash('sha512', $code));
			$accessToken->setTokenId($generatedToken->getId());
			$accessToken->setCodeCreatedAt($this->timeFactory->now()->getTimestamp());
			$this->accessTokenMapper->insert($accessToken);

			$redirectUri = $client->getRedirectUri();

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

		return new Http\RedirectResponse($redirectUri);
	}

	/**
	 * @PublicPage
	 */
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
		return new Http\RedirectResponse($redirectUri);
	}

	private function getServerPath(): string {
		$serverPostfix = '';

		if (str_contains($this->request->getRequestUri(), '/index.php')) {
			$serverPostfix = substr($this->request->getRequestUri(), 0, strpos($this->request->getRequestUri(), '/index.php'));
		} elseif (str_contains($this->request->getRequestUri(), '/login/flow')) {
			$serverPostfix = substr($this->request->getRequestUri(), 0, strpos($this->request->getRequestUri(), '/login/flow'));
		}

		$protocol = $this->request->getServerProtocol();

		if ($protocol !== "https") {
			$xForwardedProto = $this->request->getHeader('X-Forwarded-Proto');
			$xForwardedSSL = $this->request->getHeader('X-Forwarded-Ssl');
			if ($xForwardedProto === 'https' || $xForwardedSSL === 'on') {
				$protocol = 'https';
			}
		}

		return $protocol . "://" . $this->request->getServerHost() . $serverPostfix;
	}
}
