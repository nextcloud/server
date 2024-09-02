<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\AppFramework\OCS;

use OC\OCS\Result;
use OCP\AppFramework\Http;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\Server;
use XMLWriter;

class ApiHelper {
	/**
	 * respond to a call
	 * @param \OC\OCS\Result $result
	 * @param string $format the format xml|json
	 * @psalm-taint-escape html
	 */
	public static function respond(Result $result, string $format = 'xml'): void {
		$request = Server::get(IRequest::class);

		// Send 401 headers if unauthorised
		if ($result->getStatusCode() === OCSController::RESPOND_UNAUTHORISED) {
			// If request comes from JS return dummy auth request
			if ($request->getHeader('X-Requested-With') === 'XMLHttpRequest') {
				header('WWW-Authenticate: DummyBasic realm="Authorisation Required"');
			} else {
				header('WWW-Authenticate: Basic realm="Authorisation Required"');
			}
			http_response_code(401);
		}

		foreach ($result->getHeaders() as $name => $value) {
			header($name . ': ' . $value);
		}

		$meta = $result->getMeta();
		$data = $result->getData();
		if (self::isV2($request)) {
			$statusCode = self::mapStatusCodes($result->getStatusCode());
			if (!is_null($statusCode)) {
				$meta['statuscode'] = $statusCode;
				http_response_code($statusCode);
			}
		}

		self::setContentType($format);
		$body = self::renderResult($format, $meta, $data);
		echo $body;
	}

	private static function toXML(iterable $array, XMLWriter $writer): void {
		foreach ($array as $k => $v) {
			if ($k[0] === '@') {
				$writer->writeAttribute(substr($k, 1), $v);
				continue;
			} elseif (is_numeric($k)) {
				$k = 'element';
			}
			if (is_array($v)) {
				$writer->startElement($k);
				self::toXML($v, $writer);
				$writer->endElement();
			} else {
				$writer->writeElement($k, $v);
			}
		}
	}

	public static function requestedFormat(): string {
		$formats = ['json', 'xml'];

		$format = (isset($_GET['format']) && is_string($_GET['format']) && in_array($_GET['format'], $formats)) ? $_GET['format'] : 'xml';
		return $format;
	}

	/**
	 * Based on the requested format the response content type is set
	 */
	public static function setContentType(?string $format = null): void {
		$format = is_null($format) ? self::requestedFormat() : $format;
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

	public static function mapStatusCodes(int $sc): ?int {
		switch ($sc) {
			case OCSController::RESPOND_NOT_FOUND:
				return Http::STATUS_NOT_FOUND;
			case OCSController::RESPOND_SERVER_ERROR:
				return Http::STATUS_INTERNAL_SERVER_ERROR;
			case OCSController::RESPOND_UNKNOWN_ERROR:
				return Http::STATUS_INTERNAL_SERVER_ERROR;
			case OCSController::RESPOND_UNAUTHORISED:
				// already handled for v1
				return null;
			case 100:
				return Http::STATUS_OK;
		}
		// any 2xx, 4xx and 5xx will be used as is
		if ($sc >= 200 && $sc < 600) {
			return $sc;
		}

		return Http::STATUS_BAD_REQUEST;
	}

	/**
	 * @param string $format
	 * @return string
	 */
	public static function renderResult(string $format, $meta, $data): string {
		$response = [
			'ocs' => [
				'meta' => $meta,
				'data' => $data,
			],
		];
		if ($format == 'json') {
			return json_encode($response, JSON_HEX_TAG);
		}

		$writer = new XMLWriter();
		$writer->openMemory();
		$writer->setIndent(true);
		$writer->startDocument();
		self::toXML($response, $writer);
		$writer->endDocument();
		return $writer->outputMemory(true);
	}
}
