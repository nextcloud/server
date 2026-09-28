<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Middleware;

use OCA\Files_Sharing\Controller\ShareAPIController;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\IL10N;
use OCP\Share\IManager;

class OCSShareAPIMiddleware extends Middleware {
	public function __construct(
		private IManager $shareManager,
		private IL10N $l,
	) {
	}

	/**
	 * @param Controller $controller
	 * @param string $methodName
	 *
	 * @throws OCSNotFoundException
	 */
	public function beforeController($controller, $methodName) {
		if ($controller instanceof ShareAPIController) {
			if (!$this->shareManager->shareApiEnabled()) {
				throw new OCSNotFoundException($this->l->t('Share API is disabled'));
			}
		}
	}

	/**
	 * @param Controller $controller
	 * @param string $methodName
	 * @param Response $response
	 * @return Response
	 */
	public function afterController($controller, $methodName, Response $response) {
		if ($controller instanceof ShareAPIController) {
			/** @var ShareAPIController $controller */
			$controller->cleanup();
		}

		return $response;
	}
}
