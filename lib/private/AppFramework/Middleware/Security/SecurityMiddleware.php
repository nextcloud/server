<?php
/**
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Stefan Weil <sw@weilnetz.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
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


namespace OC\AppFramework\Middleware\Security;

use OC\AppFramework\Middleware\Security\Exceptions\AppNotEnabledException;
use OC\AppFramework\Middleware\Security\Exceptions\CrossSiteRequestForgeryException;
use OC\AppFramework\Middleware\Security\Exceptions\NotAdminException;
use OC\AppFramework\Middleware\Security\Exceptions\NotLoggedInException;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OC\Security\CSP\ContentSecurityPolicyManager;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\JSONResponse;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IRequest;
use OCP\ILogger;
use OCP\AppFramework\Controller;
use OCP\Util;
use OC\AppFramework\Middleware\Security\Exceptions\SecurityException;

/**
 * Used to do all the authentication and checking stuff for a controller method
 * It reads out the annotations of a controller method and checks which if
 * security things should be checked and also handles errors in case a security
 * check fails
 */
class SecurityMiddleware extends Middleware {
	/** @var INavigationManager */
	private $navigationManager;
	/** @var IRequest */
	private $request;
	/** @var ControllerMethodReflector */
	private $reflector;
	/** @var string */
	private $appName;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var ILogger */
	private $logger;
	/** @var bool */
	private $isLoggedIn;
	/** @var bool */
	private $isAdminUser;
	/** @var ContentSecurityPolicyManager */
	private $contentSecurityPolicyManager;

	/**
	 * @param IRequest $request
	 * @param ControllerMethodReflector $reflector
	 * @param INavigationManager $navigationManager
	 * @param IURLGenerator $urlGenerator
	 * @param ILogger $logger
	 * @param string $appName
	 * @param bool $isLoggedIn
	 * @param bool $isAdminUser
	 * @param ContentSecurityPolicyManager $contentSecurityPolicyManager
	 */
	public function __construct(IRequest $request,
								ControllerMethodReflector $reflector,
								INavigationManager $navigationManager,
								IURLGenerator $urlGenerator,
								ILogger $logger,
								$appName,
								$isLoggedIn,
								$isAdminUser,
								ContentSecurityPolicyManager $contentSecurityPolicyManager) {
		$this->navigationManager = $navigationManager;
		$this->request = $request;
		$this->reflector = $reflector;
		$this->appName = $appName;
		$this->urlGenerator = $urlGenerator;
		$this->logger = $logger;
		$this->isLoggedIn = $isLoggedIn;
		$this->isAdminUser = $isAdminUser;
		$this->contentSecurityPolicyManager = $contentSecurityPolicyManager;
	}


	/**
	 * This runs all the security checks before a method call. The
	 * security checks are determined by inspecting the controller method
	 * annotations
	 * @param string $controller the controllername or string
	 * @param string $methodName the name of the method
	 * @throws SecurityException when a security check fails
	 */
	public function beforeController($controller, $methodName) {

		// this will set the current navigation entry of the app, use this only
		// for normal HTML requests and not for AJAX requests
		$this->navigationManager->setActiveEntry($this->appName);

		// security checks
		$isPublicPage = $this->reflector->hasAnnotation('PublicPage');
		if(!$isPublicPage) {
			if(!$this->isLoggedIn) {
				throw new NotLoggedInException();
			}

			if(!$this->reflector->hasAnnotation('NoAdminRequired')) {
				if(!$this->isAdminUser) {
					throw new NotAdminException();
				}
			}
		}

		// CSRF check - also registers the CSRF token since the session may be closed later
		Util::callRegister();
		if(!$this->reflector->hasAnnotation('NoCSRFRequired')) {
			if(!$this->request->passesCSRFCheck()) {
				throw new CrossSiteRequestForgeryException();
			}
		}

		/**
		 * FIXME: Use DI once available
		 * Checks if app is enabled (also includes a check whether user is allowed to access the resource)
		 * The getAppPath() check is here since components such as settings also use the AppFramework and
		 * therefore won't pass this check.
		 */
		if(\OC_App::getAppPath($this->appName) !== false && !\OC_App::isEnabled($this->appName)) {
			throw new AppNotEnabledException();
		}

	}

	/**
	 * Performs the default CSP modifications that may be injected by other
	 * applications
	 *
	 * @param Controller $controller
	 * @param string $methodName
	 * @param Response $response
	 * @return Response
	 */
	public function afterController($controller, $methodName, Response $response) {
		$policy = !is_null($response->getContentSecurityPolicy()) ? $response->getContentSecurityPolicy() : new ContentSecurityPolicy();

		$defaultPolicy = $this->contentSecurityPolicyManager->getDefaultPolicy();
		$defaultPolicy = $this->contentSecurityPolicyManager->mergePolicies($defaultPolicy, $policy);

		$response->setContentSecurityPolicy($defaultPolicy);

		return $response;
	}

	/**
	 * If an SecurityException is being caught, ajax requests return a JSON error
	 * response and non ajax requests redirect to the index
	 * @param Controller $controller the controller that is being called
	 * @param string $methodName the name of the method that will be called on
	 *                           the controller
	 * @param \Exception $exception the thrown exception
	 * @throws \Exception the passed in exception if it can't handle it
	 * @return Response a Response object or null in case that the exception could not be handled
	 */
	public function afterException($controller, $methodName, \Exception $exception) {
		if($exception instanceof SecurityException) {

			if (stripos($this->request->getHeader('Accept'),'html') === false) {
				$response = new JSONResponse(
					array('message' => $exception->getMessage()),
					$exception->getCode()
				);
			} else {
				if($exception instanceof NotLoggedInException) {
					$url = $this->urlGenerator->linkToRoute(
						'core.login.showLoginForm',
						[
							'redirect_url' => urlencode($this->request->server['REQUEST_URI']),
						]
					);
					$response = new RedirectResponse($url);
				} else {
					$response = new TemplateResponse('core', '403', ['file' => $exception->getMessage()], 'guest');
					$response->setStatus($exception->getCode());
				}
			}

			$this->logger->debug($exception->getMessage());
			return $response;
		}

		throw $exception;
	}

}
