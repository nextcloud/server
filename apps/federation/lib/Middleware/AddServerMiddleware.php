<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Federation\Middleware;

use OCA\Federation\Controller\SettingsController;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Middleware;
use OCP\HintException;
use OCP\IL10N;
use Psr\Log\LoggerInterface;

class AddServerMiddleware extends Middleware {
	protected string $appName;
	protected IL10N $l;
	protected LoggerInterface $logger;

	public function __construct(string $appName, IL10N $l, LoggerInterface $logger) {
		$this->appName = $appName;
		$this->l = $l;
		$this->logger = $logger;
	}

	/**
	 * Log error message and return a response which can be displayed to the user
	 *
	 * @param Controller $controller
	 * @param string $methodName
	 * @param \Exception $exception
	 * @return JSONResponse
	 * @throws \Exception
	 */
	public function afterException($controller, $methodName, \Exception $exception) {
		if (($controller instanceof SettingsController) === false) {
			throw $exception;
		}
		$this->logger->error($exception->getMessage(), [
			'app' => $this->appName,
			'exception' => $exception,
		]);
		if ($exception instanceof HintException) {
			$message = $exception->getHint();
		} else {
			$message = $exception->getMessage();
		}

		return new JSONResponse(
			['message' => $message],
			Http::STATUS_BAD_REQUEST
		);
	}
}
