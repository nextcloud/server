<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\AppFramework;

use Closure;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;

/**
 * Base class to inherit your controllers from
 * @since 6.0.0
 */
abstract class Controller {
	/**
	 * app name
	 * @var string
	 * @since 7.0.0
	 */
	protected $appName;

	/**
	 * current request
	 * @var \OCP\IRequest
	 * @since 6.0.0
	 */
	protected $request;

	/**
	 * @var array<string, Closure>
	 * @since 7.0.0
	 */
	private $responders;

	/**
	 * constructor of the controller
	 * @param string $appName the name of the app
	 * @param IRequest $request an instance of the request
	 * @since 6.0.0 - parameter $appName was added in 7.0.0 - parameter $app was removed in 7.0.0
	 */
	public function __construct($appName,
		IRequest $request) {
		$this->appName = $appName;
		$this->request = $request;

		// default responders
		$this->responders = [
			'json' => function ($data) {
				if ($data instanceof DataResponse) {
					$response = new JSONResponse(
						$data->getData(),
						$data->getStatus()
					);
					$dataHeaders = $data->getHeaders();
					$headers = $response->getHeaders();
					// do not overwrite Content-Type if it already exists
					if (isset($dataHeaders['Content-Type'])) {
						unset($headers['Content-Type']);
					}
					$response->setHeaders(array_merge($dataHeaders, $headers));

					if ($data->getETag() !== null) {
						$response->setETag($data->getETag());
					}
					if ($data->getLastModified() !== null) {
						$response->setLastModified($data->getLastModified());
					}
					if ($data->isThrottled()) {
						$response->throttle($data->getThrottleMetadata());
					}

					return $response;
				}
				return new JSONResponse($data);
			}
		];
	}


	/**
	 * Parses an HTTP accept header and returns the supported responder type
	 * @param string $acceptHeader
	 * @param string $default
	 * @return string the responder type
	 * @since 7.0.0
	 * @since 9.1.0 Added default parameter
	 * @deprecated 33.0.0 Use {@see \OCP\IRequest::getFormat} instead
	 */
	public function getResponderByHTTPHeader($acceptHeader, $default = 'json') {
		$headers = explode(',', $acceptHeader);

		// return the first matching responder
		foreach ($headers as $header) {
			$header = strtolower(trim($header));

			$responder = str_replace('application/', '', $header);

			if (array_key_exists($responder, $this->responders)) {
				return $responder;
			}
		}

		// no matching header return default
		return $default;
	}


	/**
	 * Registers a formatter for a type
	 * @param string $format
	 * @param Closure $responder
	 * @since 7.0.0
	 */
	protected function registerResponder($format, Closure $responder) {
		$this->responders[$format] = $responder;
	}


	/**
	 * Serializes and formats a response
	 * @param mixed $response the value that was returned from a controller and
	 *                        is not a Response instance
	 * @param string $format the format for which a formatter has been registered
	 * @throws \DomainException if format does not match a registered formatter
	 * @return Response
	 * @since 7.0.0
	 */
	public function buildResponse($response, $format = 'json') {
		if (array_key_exists($format, $this->responders)) {
			$responder = $this->responders[$format];

			return $responder($response);
		}
		throw new \DomainException('No responder registered for format '
			. $format . '!');
	}

	public function isResponderRegistered(string $responder): bool {
		return isset($this->responders[$responder]);
	}
}
