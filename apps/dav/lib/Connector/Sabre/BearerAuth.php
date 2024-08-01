<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Connector\Sabre;

use OCP\IRequest;
use OCP\ISession;
use OCP\IUserSession;
use Sabre\DAV\Auth\Backend\AbstractBearer;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class BearerAuth extends AbstractBearer {
	private IUserSession $userSession;
	private ISession $session;
	private IRequest $request;
	private string $principalPrefix;

	public function __construct(IUserSession $userSession,
		ISession $session,
		IRequest $request,
		$principalPrefix = 'principals/users/') {
		$this->userSession = $userSession;
		$this->session = $session;
		$this->request = $request;
		$this->principalPrefix = $principalPrefix;

		// setup realm
		$defaults = new \OCP\Defaults();
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
	public function validateBearerToken($bearerToken) {
		\OC_Util::setupFS();

		if (!$this->userSession->isLoggedIn()) {
			$this->userSession->tryTokenLogin($this->request);
		}
		if ($this->userSession->isLoggedIn()) {
			return $this->setupUserFs($this->userSession->getUser()->getUID());
		}

		return false;
	}

	/**
	 * \Sabre\DAV\Auth\Backend\AbstractBearer::challenge sets an WWW-Authenticate
	 * header which some DAV clients can't handle. Thus we override this function
	 * and make it simply return a 401.
	 *
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 */
	public function challenge(RequestInterface $request, ResponseInterface $response): void {
		$response->setStatus(401);
	}
}
