<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Middleware\Security;

use OC\AppFramework\Http\Request;
use OC\AppFramework\Middleware\Security\Exceptions\LaxSameSiteCookieFailedException;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;

class SameSiteCookieMiddleware extends Middleware {
	/** @var Request */
	private $request;

	/** @var ControllerMethodReflector */
	private $reflector;

	public function __construct(Request $request,
		ControllerMethodReflector $reflector) {
		$this->request = $request;
		$this->reflector = $reflector;
	}

	public function beforeController($controller, $methodName) {
		$requestUri = $this->request->getScriptName();
		$processingScript = explode('/', $requestUri);
		$processingScript = $processingScript[count($processingScript) - 1];

		if ($processingScript !== 'index.php') {
			return;
		}

		$noSSC = $this->reflector->hasAnnotation('NoSameSiteCookieRequired');
		if ($noSSC) {
			return;
		}

		if (!$this->request->passesLaxCookieCheck()) {
			throw new LaxSameSiteCookieFailedException();
		}
	}

	public function afterException($controller, $methodName, \Exception $exception) {
		if ($exception instanceof LaxSameSiteCookieFailedException) {
			$response = new Response();
			$response->setStatus(Http::STATUS_FOUND);
			$response->addHeader('Location', $this->request->getRequestUri());

			$this->setSameSiteCookie();

			return $response;
		}

		throw $exception;
	}

	protected function setSameSiteCookie() {
		$cookieParams = $this->request->getCookieParams();
		$secureCookie = ($cookieParams['secure'] === true) ? 'secure; ' : '';
		$policies = [
			'lax',
			'strict',
		];

		// Append __Host to the cookie if it meets the requirements
		$cookiePrefix = '';
		if ($cookieParams['secure'] === true && $cookieParams['path'] === '/') {
			$cookiePrefix = '__Host-';
		}

		foreach ($policies as $policy) {
			header(
				sprintf(
					'Set-Cookie: %snc_sameSiteCookie%s=true; path=%s; httponly;' . $secureCookie . 'expires=Fri, 31-Dec-2100 23:59:59 GMT; SameSite=%s',
					$cookiePrefix,
					$policy,
					$cookieParams['path'],
					$policy
				),
				false
			);
		}
	}
}
