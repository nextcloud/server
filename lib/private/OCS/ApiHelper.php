<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\OCS;

use OC\AppFramework\OCS\V1Response;
use OC\AppFramework\OCS\V2Response;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\Server;

class ApiHelper {
	/**
	 * Respond to a call
	 * @psalm-taint-escape html
	 * @param int $overrideHttpStatusCode force the HTTP status code, only used for the special case of maintenance mode which return 503 even for v1
	 */
	public static function respond(int $statusCode, string $statusMessage, array $headers = [], ?int $overrideHttpStatusCode = null): void {
		$request = Server::get(IRequest::class);
		$format = $request->getParam('format', 'xml');
		if (self::isV2($request)) {
			$response = new V2Response(new DataResponse([], $statusCode, $headers), $format, $statusMessage);
		} else {
			$response = new V1Response(new DataResponse([], $statusCode, $headers), $format, $statusMessage);
		}

		// Send 401 headers if unauthorised
		if ($response->getOCSStatus() === OCSController::RESPOND_UNAUTHORISED) {
			// If request comes from JS return dummy auth request
			if ($request->getHeader('X-Requested-With') === 'XMLHttpRequest') {
				header('WWW-Authenticate: DummyBasic realm="Authorisation Required"');
			} else {
				header('WWW-Authenticate: Basic realm="Authorisation Required"');
			}
			http_response_code(401);
		}

		foreach ($response->getHeaders() as $name => $value) {
			header($name . ': ' . $value);
		}

		http_response_code($overrideHttpStatusCode ?? $response->getStatus());

		self::setContentType($format);
		$body = $response->render();
		echo $body;
	}

	/**
	 * Based on the requested format the response content type is set
	 */
	public static function setContentType(?string $format = null): void {
		$format ??= Server::get(IRequest::class)->getParam('format', 'xml');
		if ($format === 'xml') {
			header('Content-type: text/xml; charset=UTF-8');
			return;
		}

		if ($format === 'json') {
			header('Content-Type: application/json; charset=utf-8');
			return;
		}

		header('Content-Type: application/octet-stream; charset=utf-8');
	}

	protected static function isV2(IRequest $request): bool {
		$script = $request->getScriptName();

		return str_ends_with($script, '/ocs/v2.php');
	}
}
