<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Middleware\Security;

use OC\AppFramework\Http\Request;
use OC\AppFramework\Middleware\Security\Exceptions\LaxSameSiteCookieFailedException;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoSameSiteCookieRequired;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

class SameSiteCookieMiddleware extends Middleware {
	public function __construct(
		private readonly Request $request,
		private readonly ControllerMethodReflector $reflector,
		private readonly LoggerInterface $logger,
	) {
	}

	public function beforeController($controller, $methodName) {
		$requestUri = $this->request->getScriptName();
		$processingScript = explode('/', $requestUri);
		$processingScript = $processingScript[count($processingScript) - 1];

		if ($processingScript !== 'index.php') {
			return;
		}

		$reflectionMethod = new ReflectionMethod($controller, $methodName);
		$noSSC = $this->hasAnnotationOrAttribute($reflectionMethod, 'NoSameSiteCookieRequired', NoSameSiteCookieRequired::class);
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

	protected function setSameSiteCookie(): void {
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

	/**
	 * @template T
	 *
	 * @param ReflectionMethod $reflectionMethod
	 * @param ?string $annotationName
	 * @param class-string<T> $attributeClass
	 * @return boolean
	 */
	protected function hasAnnotationOrAttribute(ReflectionMethod $reflectionMethod, ?string $annotationName, string $attributeClass): bool {
		if (!empty($reflectionMethod->getAttributes($attributeClass))) {
			return true;
		}

		if ($annotationName && $this->reflector->hasAnnotation($annotationName)) {
			$this->logger->debug($reflectionMethod->getDeclaringClass()->getName() . '::' . $reflectionMethod->getName() . ' uses the @' . $annotationName . ' annotation and should use the #[' . $attributeClass . '] attribute instead');
			return true;
		}

		return false;
	}
}
