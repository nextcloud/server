<?php

namespace OCA\Files_Sharing\Middleware;

use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\Files_Sharing\Controller\ShareInfoController;
use OCA\Files_Sharing\Exceptions\S2SException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\Share\IManager;

class ShareInfoMiddleware extends Middleware {
	/** @var IManager */
	private $shareManager;

	public function __construct(IManager $shareManager) {
		$this->shareManager = $shareManager;
	}

	/**
	 * @param Controller $controller
	 * @param string $methodName
	 * @throws S2SException
	 */
	public function beforeController(Controller $controller, $methodName) {
		if (!($controller instanceof ShareInfoController)) {
			return;
		}

		if (!$this->shareManager->outgoingServer2ServerSharesAllowed()) {
			throw new S2SException();
		}
	}

	/**
	 * @param Controller $controller
	 * @param string $methodName
	 * @param \Exception $exception
	 * @throws \Exception
	 * @return Response
	 */
	public function afterException(Controller $controller, $methodName, \Exception $exception) {
		if (!($controller instanceof ShareInfoController)) {
			throw $exception;
		}

		if ($exception instanceof S2SException) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * @param Controller $controller
	 * @param string $methodName
	 * @param Response $response
	 * @return Response
	 */
	public function afterController(Controller $controller, $methodName, Response $response) {
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
