<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Joas Schilling <coding@schilljs.com>
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
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
namespace OC\AppFramework\Middleware\Security;

use OC\AppFramework\Utility\ControllerMethodReflector;
use OC\Security\Bruteforce\Throttler;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TooManyRequestsResponse;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\Security\Bruteforce\MaxDelayReached;
use Psr\Log\LoggerInterface;

/**
 * Class BruteForceMiddleware performs the bruteforce protection for controllers
 * that are annotated with @BruteForceProtection(action=$action) whereas $action
 * is the action that should be logged within the database.
 *
 * @package OC\AppFramework\Middleware\Security
 */
class BruteForceMiddleware extends Middleware {
	public function __construct(
		protected ControllerMethodReflector $reflector,
		protected Throttler $throttler,
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
			$this->throttler->sleepDelayOrThrowOnMax($this->request->getRemoteAddress(), $action);
		} else {
			$attributes = $this->reflector->getAttributes(BruteForceProtection::class);

			if (!empty($attributes)) {
				$remoteAddress = $this->request->getRemoteAddress();
				foreach ($attributes as $protection) {
					$action = $protection->getAction();
					$this->throttler->sleepDelayOrThrowOnMax($remoteAddress, $action);
				}
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function afterController($controller, $methodName, Response $response) {
		if ($response->isThrottled()) {
			if ($this->reflector->hasAnnotation('BruteForceProtection')) {
				$action = $this->reflector->getAnnotationParameter('BruteForceProtection', 'action');
				$ip = $this->request->getRemoteAddress();
				$this->throttler->sleepDelay($ip, $action);
				$this->throttler->registerAttempt($action, $ip, $response->getThrottleMetadata());
			} else {
				$attributes = $this->reflector->getAttributes(BruteForceProtection::class);

				if (!empty($attributes)) {
					$ip = $this->request->getRemoteAddress();
					$metaData = $response->getThrottleMetadata();

					foreach ($attributes as $protection) {
						$action = $protection->getAction();

						if (!isset($metaData['action']) || $metaData['action'] === $action) {
							$this->throttler->sleepDelay($ip, $action);
							$this->throttler->registerAttempt($action, $ip, $metaData);
						}
					}
				} else {
					$this->logger->debug('Response for ' . get_class($controller) . '::' . $methodName . ' got bruteforce throttled but has no annotation nor attribute defined.');
				}
			}
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
