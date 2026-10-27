<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Middleware;

use OCA\Files_Sharing\Controller\ShareInfoController;
use OCA\Files_Sharing\Exceptions\S2SException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\Share\IManager;

class ShareInfoMiddleware extends Middleware {
	public function __construct(
		private IManager $shareManager,
	) {
	}

	#[\Override]
	public function beforeController(Controller $controller, string $methodName): void {
		if (!($controller instanceof ShareInfoController)) {
			return;
		}

		if (!$this->shareManager->outgoingServer2ServerSharesAllowed()) {
			throw new S2SException();
		}
	}

	#[\Override]
	public function afterException(Controller $controller, string $methodName, \Exception $exception): JSONResponse {
		if (!($controller instanceof ShareInfoController)) {
			throw $exception;
		}

		if ($exception instanceof S2SException) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}

		throw $exception;
	}

	#[\Override]
	public function afterController(Controller $controller, string $methodName, Response $response) {
		if (!($controller instanceof ShareInfoController)) {
			return $response;
		}

		if (!($response instanceof JSONResponse)) {
			return $response;
		}

		$data = $response->getData();
		$status = 'error';

		if ($response->getStatus() === Http::STATUS_OK) {
			$status = 'success';
		}

		$response->setData([
			'data' => $data,
			'status' => $status,
		]);

		return $response;
	}
}
