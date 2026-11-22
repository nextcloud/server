<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Connector\Sabre;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use OC\OCM\OCMSignatoryManager;
use OCP\AppFramework\Http;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserSession;
use OCP\Security\Signature\Model\Signatory;
use OCP\Server;
use OCP\Share\IManager;
use OCP\Share\IShare;
use Sabre\DAV\Auth\Backend\AbstractBearer;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class BearerAuth extends AbstractBearer {
	public function __construct(
		private IUserSession $userSession,
		private ISession $session,
		private IRequest $request,
		private IConfig $config,
		private string $principalPrefix = 'principals/users/',
		private string $token = '',
		private bool $allowOcmAccessToken = false,
		private ?IManager $shareManager = null,
		private ?OCMSignatoryManager $ocmSignatoryManager = null,
	) {
		// setup realm
		$defaults = new Defaults();
		$this->realm = $defaults->getName() ?: 'Nextcloud';
	}

	private function setupUserFs($userId) {
		\OC_Util::setupFS($userId);
		$this->session->close();
		return $this->principalPrefix . $userId;
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function validateBearerToken($bearerToken) {
		\OC_Util::setupFS();
		$this->token = $bearerToken;
		if ($this->allowOcmAccessToken) {
			$sharedSecret = $this->resolveOcmSharedSecret($bearerToken);
			if ($sharedSecret !== null) {
				$this->token = $sharedSecret;
				$this->userSession->setIncognitoMode(true);
				return $this->principalPrefix . $sharedSecret;
			}
		}

		// public.php sets incognito mode for anonymous share access, which makes
		// Session::getUser() return null and consequently Session::isLoggedIn()
		// return false even after a successful token login. Disable it here so
		// the logged-in user is visible for the rest of the request. If the
		// bearer token is invalid and Sabre falls back to one of the public
		// auth backends, that backend will re-enable incognito mode itself.
		$this->userSession->setIncognitoMode(false);

		if ($this->userSession->tryTokenLogin($this->request)) {
			return $this->setupUserFs($this->userSession->getUser()->getUID());
		}

		return false;
	}

	private function resolveOcmSharedSecret(string $accessToken): ?string {
		if ($this->shareManager === null || $this->ocmSignatoryManager === null) {
			return null;
		}

		try {
			$jwks = $this->ocmSignatoryManager->getLocalJwks();
			$headers = new \stdClass();
			$claims = JWT::decode($accessToken, JWK::parseKeySet(['keys' => $jwks]), $headers);
			if (($headers->typ ?? null) !== 'at+jwt' || !is_string($headers->kid ?? null)) {
				return null;
			}

			$issuer = parse_url($headers->kid, PHP_URL_SCHEME)
				. '://' . Signatory::extractIdentityFromUri($headers->kid);
			if (!is_string($claims->iss ?? null)
				|| !hash_equals($issuer, $claims->iss)
				|| !is_string($claims->sub ?? null)
				|| !is_string($claims->aud ?? null)
				|| !is_string($claims->client_id ?? null)
				|| !isset($claims->iat, $claims->exp)
			) {
				return null;
			}

			$share = $this->shareManager->getShareById('ocFederatedSharing:' . $claims->client_id);
			if (!in_array($share->getShareType(), [IShare::TYPE_REMOTE, IShare::TYPE_REMOTE_GROUP], true)
				|| !hash_equals($share->getShareOwner(), $claims->sub)
				|| !hash_equals((string)$share->getSharedWith(), $claims->aud)
			) {
				return null;
			}

			$sharedSecret = $share->getToken();
			if ($sharedSecret === null || $sharedSecret === '') {
				return null;
			}

			$shareByToken = $this->shareManager->getShareByToken($sharedSecret);
			if (!hash_equals($share->getFullId(), $shareByToken->getFullId())) {
				return null;
			}

			return $sharedSecret;
		} catch (\Throwable) {
			return null;
		}
	}

	public function getShare(): IShare {
		$shareManager = $this->shareManager ?? Server::get(IManager::class);
		$share = $shareManager->getShareByToken($this->token);
		return $share;
	}

	/**
	 * \Sabre\DAV\Auth\Backend\AbstractBearer::challenge sets an WWW-Authenticate
	 * header which some DAV clients can't handle. Thus we override this function
	 * and make it simply return a 401.
	 *
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 */
	#[\Override]
	public function challenge(RequestInterface $request, ResponseInterface $response): void {
		// Legacy ownCloud clients still authenticate via OAuth2
		$enableOcClients = $this->config->getSystemValueBool('oauth2.enable_oc_clients', false);
		$userAgent = $request->getHeader('User-Agent');
		if ($enableOcClients && $userAgent !== null && str_contains($userAgent, 'mirall')) {
			parent::challenge($request, $response);
			return;
		}

		$response->setStatus(Http::STATUS_UNAUTHORIZED);
	}
}
