<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Middleware;

use OC\AppFramework\OCS\BaseResponse;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Middleware;
use OCP\IRequest;

class CompressionMiddleware extends Middleware {
	/** @var bool */
	private $useGZip;

	public function __construct(
		private IRequest $request,
	) {
		$this->useGZip = false;
	}

	public function afterController($controller, $methodName, Response $response) {
		// By default we do not gzip
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

	public function beforeOutput($controller, $methodName, $output) {
		if (!$this->useGZip) {
			return $output;
		}

		return gzencode($output);
	}
}
