<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Middleware;

use OC\AppFramework\Http;
use OC\AppFramework\OCS\BaseResponse;
use OC\AppFramework\OCS\V1Response;
use OC\AppFramework\OCS\V2Response;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class OCSMiddleware extends Middleware {
	/** @var IRequest */
	private $request;

	/** @var int */
	private $ocsVersion;

	/**
	 * @param IRequest $request
	 */
	public function __construct(IRequest $request) {
		$this->request = $request;
	}

	/**
	 * @param Controller $controller
	 * @param string $methodName
	 */
	public function beforeController($controller, $methodName) {
		if ($controller instanceof OCSController) {
			if (substr_compare($this->request->getScriptName(), '/ocs/v2.php', -strlen('/ocs/v2.php')) === 0) {
				$this->ocsVersion = 2;
			} else {
				$this->ocsVersion = 1;
			}
			$controller->setOCSVersion($this->ocsVersion);
		}
	}

	/**
	 * @param Controller $controller
	 * @param string $methodName
	 * @param \Exception $exception
	 * @throws \Exception
	 * @return BaseResponse
	 */
	public function afterException($controller, $methodName, \Exception $exception) {
		if ($controller instanceof OCSController && $exception instanceof OCSException) {
			$code = $exception->getCode();
			if ($code === 0) {
				$code = \OCP\AppFramework\OCSController::RESPOND_UNKNOWN_ERROR;
			}

			return $this->buildNewResponse($controller, $code, $exception->getMessage());
		}

		throw $exception;
	}

	/**
	 * @param Controller $controller
	 * @param string $methodName
	 * @param Response $response
	 * @return \OCP\AppFramework\Http\Response
	 */
	public function afterController($controller, $methodName, Response $response) {
		/*
		 * If a different middleware has detected that a request unauthorized or forbidden
		 * we need to catch the response and convert it to a proper OCS response.
		 */
		if ($controller instanceof OCSController && !($response instanceof BaseResponse)) {
			if ($response->getStatus() === Http::STATUS_UNAUTHORIZED) {
				$message = '';
				if ($response instanceof JSONResponse) {
					/** @var DataResponse $response */
					$message = $response->getData()['message'];
				}

				return $this->buildNewResponse($controller, OCSController::RESPOND_UNAUTHORISED, $message);
			}
			if ($response->getStatus() === Http::STATUS_FORBIDDEN) {
				$message = '';
				if ($response instanceof JSONResponse) {
					/** @var DataResponse $response */
					$message = $response->getData()['message'];
				}

				return $this->buildNewResponse($controller, Http::STATUS_FORBIDDEN, $message);
			}
		}

		return $response;
	}

	/**
	 * @param Controller $controller
	 * @param int $code
	 * @param string $message
	 * @return V1Response|V2Response
	 */
	private function buildNewResponse(Controller $controller, $code, $message) {
		$format = $this->request->getFormat();
		if ($format === null || !$controller->isResponderRegistered($format)) {
			$format = 'xml';
		}

		$data = new DataResponse();
		$data->setStatus($code);
		if ($this->ocsVersion === 1) {
			$response = new V1Response($data, $format, $message);
		} else {
			$response = new V2Response($data, $format, $message);
		}

		return $response;
	}
}
