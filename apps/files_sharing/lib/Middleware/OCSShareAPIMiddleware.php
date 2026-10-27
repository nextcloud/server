<?php

declare(strict_types=1);

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
		private readonly IManager $shareManager,
		private readonly IL10N $l,
	) {
	}

	/**
	 * @throws OCSNotFoundException
	 */
	#[\Override]
	public function beforeController(Controller $controller, string $methodName): void {
		if ($controller instanceof ShareAPIController) {
			if (!$this->shareManager->shareApiEnabled()) {
				throw new OCSNotFoundException($this->l->t('Share API is disabled'));
			}
		}
	}

	#[\Override]
	public function afterController(Controller $controller, string $methodName, Response $response): Response {
		if ($controller instanceof ShareAPIController) {
			/** @var ShareAPIController $controller */
			$controller->cleanup();
		}

		return $response;
	}
}
