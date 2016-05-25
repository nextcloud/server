<?php

/**
 * @author Christoph Wurst <christoph@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Core\Controller;

use OC\Authentication\ClientLogin\IClientLoginCoordinator;
use OC\Authentication\Exceptions\ClientLoginPendingException;
use OC\Authentication\Exceptions\InvalidAccessTokenException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;

class AuthController extends Controller {

	/** @var IClientLoginCoordinator */
	private $coordinator;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IUserSession */
	private $userSession;

	public function __construct($appName, IRequest $request, IClientLoginCoordinator $coordinator, IURLGenerator $urlGenerator, IUserSession $userSession) {
		parent::__construct($appName, $request);
		$this->coordinator = $coordinator;
		$this->urlGenerator = $urlGenerator;
		$this->userSession = $userSession;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $name client name
	 * @return JSONResponse
	 */
	public function start($name = 'unknown client') {
		$token = $this->coordinator->startClientLogin($name);

		$url = $this->urlGenerator->linkToRoute('core.auth.check', [
			'accesstoken' => $token
		]);
		$fullUrl = $this->urlGenerator->getAbsoluteURL($url);
		return [
			'url' => $fullUrl,
			'accessToken' => $token,
		];
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $accesstoken
	 * @return TemplateResponse
	 */
	public function check($accesstoken) {
		try {
			$user = $this->userSession->getUser();
			$this->coordinator->finishClientLogin($accesstoken, $user);
		} catch (InvalidAccessTokenException $ex) {
			return new RedirectResponse($this->urlGenerator->linkToRoute('files.view.index'));
		}
		return new TemplateResponse('core', 'authsuccess', [], 'guest');
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $accesstoken
	 * @return JSONResponse
	 */
	public function status($accesstoken) {
		try {
			$token = $this->coordinator->getClientToken($accesstoken);
		} catch (ClientLoginPendingException $ex) {
			return [
				'status' => 0, // TODO: use text status instead?
				'token' => null,
			];
		} catch (InvalidAccessTokenException $ex) {
			$resp = new JSONResponse();
			$resp->setStatus(Http::STATUS_BAD_REQUEST);
			return $resp;
		}

		return [
			'status' => 1, // TODO: use text status instead?
			'token' => $token,
		];
	}

}
