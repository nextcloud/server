<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
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
	 */
	public function __construct($appName,
								IRequest $request,
								IUserSession $userSession,
								IL10N $l10n,
								Defaults $defaults,
								ISession $session,
								IProvider $tokenProvider,
								ISecureRandom $random,
								IURLGenerator $urlGenerator) {
		parent::__construct($appName, $request);
		$this->userSession = $userSession;
		$this->l10n = $l10n;
		$this->defaults = $defaults;
		$this->session = $session;
		$this->tokenProvider = $tokenProvider;
		$this->random = $random;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @return string
	 */
	private function getClientName() {
		return $this->request->getHeader('USER_AGENT') !== null ? $this->request->getHeader('USER_AGENT') : 'unknown';
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
	 * @return TemplateResponse
	 */
	public function showAuthPickerPage() {
		if($this->userSession->isLoggedIn()) {
			return new TemplateResponse(
				$this->appName,
				'403',
				[
					'file' => $this->l10n->t('Auth flow can only be started unauthenticated.'),
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
				'client' => $this->getClientName(),
				'instanceName' => $this->defaults->getName(),
				'urlGenerator' => $this->urlGenerator,
				'stateToken' => $stateToken,
				'serverHost' => $this->request->getServerHost(),
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
	 * @return TemplateResponse
	 */
	public function redirectPage($stateToken = '') {
		if(!$this->isValidToken($stateToken)) {
			return $this->stateTokenForbiddenResponse();
		}

		return new TemplateResponse(
			$this->appName,
			'loginflow/redirect',
			[
				'urlGenerator' => $this->urlGenerator,
				'stateToken' => $stateToken,
			],
			'empty'
		);
	}

	/**
	 * @NoAdminRequired
	 * @UseSession
	 *
	 * @param string $stateToken
	 * @return Http\RedirectResponse|Response
	 */
	public function generateAppPassword($stateToken) {
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

		$token = $this->random->generate(72);
		$this->tokenProvider->generateToken(
			$token,
			$this->userSession->getUser()->getUID(),
			$loginName,
			$password,
			$this->getClientName(),
			IToken::PERMANENT_TOKEN,
			IToken::DO_NOT_REMEMBER
		);

		return new Http\RedirectResponse('nc://' . urlencode($loginName) . ':' . urlencode($token) . '@' . $this->request->getServerHost());
	}

}
