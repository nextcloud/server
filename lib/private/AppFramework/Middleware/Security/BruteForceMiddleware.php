<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Middleware\Security;

use OC\AppFramework\Utility\ControllerMethodReflector;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TooManyRequestsResponse;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Security\Bruteforce\MaxDelayReached;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

/**
 * Class BruteForceMiddleware performs the bruteforce protection for controllers
 * that are annotated with @BruteForceProtection(action=$action) whereas $action
 * is the action that should be logged within the database.
 *
 * @package OC\AppFramework\Middleware\Security
 */
class BruteForceMiddleware extends Middleware {
	private int $delaySlept = 0;

	public function __construct(
		protected ControllerMethodReflector $reflector,
		protected IThrottler $throttler,
		protected IRequest $request,
		protected LoggerInterface $logger,
	) {
	}

	/**
	 * {@inheritDoc}
	 */
	public function beforeController($controller, $methodName) {
		parent::beforeController($controller, $methodName);

		if ($this->reflector->hasAnnotation('BruteForceProtection')) {
			$action = $this->reflector->getAnnotationParameter('BruteForceProtection', 'action');
			$this->delaySlept += $this->throttler->sleepDelayOrThrowOnMax($this->request->getRemoteAddress(), $action);
		} else {
			$reflectionMethod = new ReflectionMethod($controller, $methodName);
			$attributes = $reflectionMethod->getAttributes(BruteForceProtection::class);

			if (!empty($attributes)) {
				$remoteAddress = $this->request->getRemoteAddress();

				foreach ($attributes as $attribute) {
					/** @var BruteForceProtection $protection */
					$protection = $attribute->newInstance();
					$action = $protection->getAction();
					$this->delaySlept += $this->throttler->sleepDelayOrThrowOnMax($remoteAddress, $action);
				}
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function afterController($controller, $methodName, Response $response) {
		if ($response->isThrottled()) {
			try {
				if ($this->reflector->hasAnnotation('BruteForceProtection')) {
					$action = $this->reflector->getAnnotationParameter('BruteForceProtection', 'action');
					$ip = $this->request->getRemoteAddress();
					$this->throttler->registerAttempt($action, $ip, $response->getThrottleMetadata());
					$this->delaySlept += $this->throttler->sleepDelayOrThrowOnMax($ip, $action);
				} else {
					$reflectionMethod = new ReflectionMethod($controller, $methodName);
					$attributes = $reflectionMethod->getAttributes(BruteForceProtection::class);

					if (!empty($attributes)) {
						$ip = $this->request->getRemoteAddress();
						$metaData = $response->getThrottleMetadata();

						foreach ($attributes as $attribute) {
							/** @var BruteForceProtection $protection */
							$protection = $attribute->newInstance();
							$action = $protection->getAction();

							if (!isset($metaData['action']) || $metaData['action'] === $action) {
								$this->throttler->registerAttempt($action, $ip, $metaData);
								$this->delaySlept += $this->throttler->sleepDelayOrThrowOnMax($ip, $action);
							}
						}
					} else {
						$this->logger->debug('Response for ' . get_class($controller) . '::' . $methodName . ' got bruteforce throttled but has no annotation nor attribute defined.');
					}
				}
			} catch (MaxDelayReached $e) {
				if ($controller instanceof OCSController) {
					throw new OCSException($e->getMessage(), Http::STATUS_TOO_MANY_REQUESTS);
				}

				return new TooManyRequestsResponse();
			}
		}

		if ($this->delaySlept) {
			$response->addHeader('X-Nextcloud-Bruteforce-Throttled', $this->delaySlept . 'ms');
		}

		return parent::afterController($controller, $methodName, $response);
	}

	/**
	 * @param Controller $controller
	 * @param string $methodName
	 * @param \Exception $exception
	 * @throws \Exception
	 * @return Response
	 */
	public function afterException($controller, $methodName, \Exception $exception): Response {
		if ($exception instanceof MaxDelayReached) {
			if ($controller instanceof OCSController) {
				throw new OCSException($exception->getMessage(), Http::STATUS_TOO_MANY_REQUESTS);
			}

			return new TooManyRequestsResponse();
		}

		throw $exception;
	}
}
