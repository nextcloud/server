<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Middleware;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\IRequest;

class NotModifiedMiddleware extends Middleware {
	/** @var IRequest */
	private $request;

	public function __construct(IRequest $request) {
		$this->request = $request;
	}

	public function afterController($controller, $methodName, Response $response) {
		$etagHeader = $this->request->getHeader('IF_NONE_MATCH');
		if ($etagHeader !== '' && $response->getETag() !== null && trim($etagHeader) === '"' . $response->getETag() . '"') {
			$response->setStatus(Http::STATUS_NOT_MODIFIED);
			return $response;
		}

		$modifiedSinceHeader = $this->request->getHeader('IF_MODIFIED_SINCE');
		if ($modifiedSinceHeader !== '' && $response->getLastModified() !== null && trim($modifiedSinceHeader) === $response->getLastModified()->format(\DateTimeInterface::RFC7231)) {
			$response->setStatus(Http::STATUS_NOT_MODIFIED);
			return $response;
		}

		return $response;
	}
}
