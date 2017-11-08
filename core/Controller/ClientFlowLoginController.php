<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Core\Controller;

use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Exceptions\PasswordlessTokenException;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OCA\OAuth2\Db\AccessToken;
use OCA\OAuth2\Db\AccessTokenMapper;
use OCA\OAuth2\Db\ClientMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Defaults;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;
use OCP\Session\Exceptions\SessionNotAvailableException;

class ClientFlowLoginController extends Controller {
	/** @var IUserSession */
	private $userSession;
	/** @var IL10N */
	private $l10n;
	/** @var Defaults */
	private $defaults;
	/** @var ISession */
	private $session;
	/** @var IProvider */
	private $tokenProvider;
	/** @var ISecureRandom */
	private $random;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var ClientMapper */
	private $clientMapper;
	/** @var AccessTokenMapper */
	private $accessTokenMapper;
	/** @var ICrypto */
	private $crypto;

	const stateName = 'client.flow.state.token';

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IUserSession $userSession
	 * @param IL10N $l10n
	 * @param Defaults $defaults
	 * @param ISession $session
	 * @param IProvider $tokenProvider
	 * @param ISecureRandom $random
	 * @param IURLGenerator $urlGenerator
	 * @param ClientMapper $clientMapper
	 * @param AccessTokenMapper $accessTokenMapper
	 * @param ICrypto $crypto
	 */
	public function __construct($appName,
								IRequest $request,
								IUserSession $userSession,
								IL10N $l10n,
								Defaults $defaults,
								ISession $session,
								IProvider $tokenProvider,
								ISecureRandom $random,
								IURLGenerator $urlGenerator,
								ClientMapper $clientMapper,
								AccessTokenMapper $accessTokenMapper,
								ICrypto $crypto) {
		parent::__construct($appName, $request);
		$this->userSession = $userSession;
		$this->l10n = $l10n;
		$this->defaults = $defaults;
		$this->session = $session;
		$this->tokenProvider = $tokenProvider;
		$this->random = $random;
		$this->urlGenerator = $urlGenerator;
		$this->clientMapper = $clientMapper;
		$this->accessTokenMapper = $accessTokenMapper;
		$this->crypto = $crypto;
	}

	/**
	 * @return string
	 */
	private function getClientName() {
		$userAgent = $this->request->getHeader('USER_AGENT');
		return $userAgent !== null ? $userAgent : 'unknown';
	}

	/**
	 * @param string $stateToken
	 * @return bool
	 */
	private function isValidToken($stateToken) {
		$currentToken = $this->session->get(self::stateName);
		if(!is_string($stateToken) || !is_string($currentToken)) {
			return false;
		}
		return hash_equals($currentToken, $stateToken);
	}

	/**
	 * @return TemplateResponse
	 */
	private function stateTokenForbiddenResponse() {
		$response = new TemplateResponse(
			$this->appName,
			'403',
			[
				'file' => $this->l10n->t('State token does not match'),
			],
			'guest'
		);
		$response->setStatus(Http::STATUS_FORBIDDEN);
		return $response;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @UseSession
	 *
	 * @param string $clientIdentifier
	 *
	 * @return TemplateResponse
	 */
	public function showAuthPickerPage($clientIdentifier = '') {
		$clientName = $this->getClientName();
		$client = null;
		if($clientIdentifier !== '') {
			$client = $this->clientMapper->getByIdentifier($clientIdentifier);
			$clientName = $client->getName();
		}

		// No valid clientIdentifier given and no valid API Request (APIRequest header not set)
		$clientRequest = $this->request->getHeader('OCS-APIREQUEST');
		if ($clientRequest !== 'true' && $client === null) {
			return new TemplateResponse(
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
		$this->session->set(self::stateName, $stateToken);

		return new TemplateResponse(
			$this->appName,
			'loginflow/authpicker',
			[
				'client' => $clientName,
				'clientIdentifier' => $clientIdentifier,
				'instanceName' => $this->defaults->getName(),
				'urlGenerator' => $this->urlGenerator,
				'stateToken' => $stateToken,
				'serverHost' => $this->request->getServerHost(),
				'oauthState' => $this->session->get('oauth.state'),
			],
			'guest'
		);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @UseSession
	 *
	 * @param string $stateToken
	 * @param string $clientIdentifier
	 * @return TemplateResponse
	 */
	public function redirectPage($stateToken = '',
								 $clientIdentifier = '') {
		if(!$this->isValidToken($stateToken)) {
			return $this->stateTokenForbiddenResponse();
		}

		return new TemplateResponse(
			$this->appName,
			'loginflow/redirect',
			[
				'urlGenerator' => $this->urlGenerator,
				'stateToken' => $stateToken,
				'clientIdentifier' => $clientIdentifier,
				'oauthState' => $this->session->get('oauth.state'),
			],
			'guest'
		);
	}

	/**
	 * @NoAdminRequired
	 * @UseSession
	 *
	 * @param string $stateToken
	 * @param string $clientIdentifier
	 * @return Http\RedirectResponse|Response
	 */
	public function generateAppPassword($stateToken,
										$clientIdentifier = '') {
		if(!$this->isValidToken($stateToken)) {
			$this->session->remove(self::stateName);
			return $this->stateTokenForbiddenResponse();
		}

		$this->session->remove(self::stateName);

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
		if($clientIdentifier !== '') {
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

		if($client) {
			$code = $this->random->generate(128);
			$accessToken = new AccessToken();
			$accessToken->setClientId($client->getId());
			$accessToken->setEncryptedToken($this->crypto->encrypt($token, $code));
			$accessToken->setHashedCode(hash('sha512', $code));
			$accessToken->setTokenId($generatedToken->getId());
			$this->accessTokenMapper->insert($accessToken);

			$redirectUri = sprintf(
				'%s?state=%s&code=%s',
				$client->getRedirectUri(),
				urlencode($this->session->get('oauth.state')),
				urlencode($code)
			);
			$this->session->remove('oauth.state');
		} else {
			$redirectUri = 'nc://login/server:' . $this->request->getServerHost() . '&user:' . urlencode($loginName) . '&password:' . urlencode($token);
		}

		return new Http\RedirectResponse($redirectUri);
	}
}
