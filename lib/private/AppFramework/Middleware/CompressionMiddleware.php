<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework\Middleware;

use OC\AppFramework\OCS\BaseResponse;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Middleware;
use OCP\IRequest;

class CompressionMiddleware extends Middleware {
	private bool $useGZip = false;

	public function __construct(
		private IRequest $request,
	) {
	}

	#[\Override]
	public function afterController(Controller $controller, string $methodName, Response $response): Response {
		// By default, we do not gzip
		$allowGzip = false;

		// Only return gzipped content for 200 responses
		if ($response->getStatus() !== Http::STATUS_OK) {
			return $response;
		}

		// Check if we are even asked for gzip
		$header = $this->request->getHeader('Accept-Encoding');
		if (!str_contains($header, 'gzip')) {
			return $response;
		}

		// We only allow gzip in some cases
		if ($response instanceof BaseResponse) {
			$allowGzip = true;
		}
		if ($response instanceof JSONResponse) {
			$allowGzip = true;
		}
		if ($response instanceof TemplateResponse) {
			$allowGzip = true;
		}

		if ($allowGzip) {
			$this->useGZip = true;
			$response->addHeader('Content-Encoding', 'gzip');
		}

		return $response;
	}

	#[\Override]
	public function beforeOutput(Controller $controller, string $methodName, string $output): string {
		if (!$this->useGZip) {
			return $output;
		}

		return gzencode($output);
	}
}
