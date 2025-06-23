<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Middleware;

use OCA\Files_Sharing\Controller\ExternalSharesController;
use OCA\Files_Sharing\Exceptions\S2SException;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Share\IManager;

/**
 * Checks whether the "sharing check" is enabled
 *
 * @package OCA\Files_Sharing\Middleware
 */
class SharingCheckMiddleware extends Middleware {

	public function __construct(
		protected string $appName,
		protected IConfig $config,
		protected IAppManager $appManager,
		protected IControllerMethodReflector $reflector,
		protected IManager $shareManager,
		protected IRequest $request,
	) {
	}

	/**
	 * Check if sharing is enabled before the controllers is executed
	 *
	 * @param Controller $controller
	 * @param string $methodName
	 * @throws NotFoundException
	 * @throws S2SException
	 */
	public function beforeController($controller, $methodName): void {
		if (!$this->isSharingEnabled()) {
			throw new NotFoundException('Sharing is disabled.');
		}

		if ($controller instanceof ExternalSharesController
			&& !$this->externalSharesChecks()) {
			throw new S2SException('Federated sharing not allowed');
		}
	}

	/**
	 * Return 404 page in case of a not found exception
	 *
	 * @param Controller $controller
	 * @param string $methodName
	 * @param \Exception $exception
	 * @return Response
	 * @throws \Exception
	 */
	public function afterException($controller, $methodName, \Exception $exception): Response {
		if (is_a($exception, NotFoundException::class)) {
			return new NotFoundResponse();
		}

		if (is_a($exception, S2SException::class)) {
			return new JSONResponse($exception->getMessage(), 405);
		}

		throw $exception;
	}

	/**
	 * Checks for externalshares controller
	 * @return bool
	 */
	private function externalSharesChecks(): bool {
		if (!$this->reflector->hasAnnotation('NoIncomingFederatedSharingRequired')
			&& $this->config->getAppValue('files_sharing', 'incoming_server2server_share_enabled', 'yes') !== 'yes') {
			return false;
		}

		if (!$this->reflector->hasAnnotation('NoOutgoingFederatedSharingRequired')
			&& $this->config->getAppValue('files_sharing', 'outgoing_server2server_share_enabled', 'yes') !== 'yes') {
			return false;
		}

		return true;
	}

	/**
	 * Check whether sharing is enabled
	 * @return bool
	 */
	private function isSharingEnabled(): bool {
		// FIXME: This check is done here since the route is globally defined and not inside the files_sharing app
		// Check whether the sharing application is enabled
		if (!$this->appManager->isEnabledForUser($this->appName)) {
			return false;
		}

		return true;
	}
}
