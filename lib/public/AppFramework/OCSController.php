<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\AppFramework;

use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;

/**
 * Base class to inherit your controllers from that are used for RESTful APIs
 * @since 8.1.0
 */
abstract class OCSController extends ApiController {
	/**
	 * @since 22.0.0
	 */
	public const RESPOND_UNAUTHORISED = 997;

	/**
	 * @since 22.0.0
	 */
	public const RESPOND_SERVER_ERROR = 996;

	/**
	 * @since 22.0.0
	 */
	public const RESPOND_NOT_FOUND = 998;

	/**
	 * @since 22.0.0
	 */
	public const RESPOND_UNKNOWN_ERROR = 999;

	/** @var int */
	private $ocsVersion;

	/**
	 * constructor of the controller
	 * @param string $appName the name of the app
	 * @param IRequest $request an instance of the request
	 * @param string $corsMethods comma separated string of HTTP verbs which
	 *                            should be allowed for websites or webapps when calling your API, defaults to
	 *                            'PUT, POST, GET, DELETE, PATCH'
	 * @param string $corsAllowedHeaders comma separated string of HTTP headers
	 *                                   which should be allowed for websites or webapps when calling your API,
	 *                                   defaults to 'Authorization, Content-Type, Accept'
	 * @param int $corsMaxAge number in seconds how long a preflighted OPTIONS
	 *                        request should be cached, defaults to 1728000 seconds
	 * @since 8.1.0
	 */
	public function __construct($appName,
		IRequest $request,
		$corsMethods = 'PUT, POST, GET, DELETE, PATCH',
		$corsAllowedHeaders = 'Authorization, Content-Type, Accept, OCS-APIRequest',
		$corsMaxAge = 1728000) {
		parent::__construct($appName, $request, $corsMethods,
			$corsAllowedHeaders, $corsMaxAge);
		$this->registerResponder('json', function ($data) {
			return $this->buildOCSResponse('json', $data);
		});
		$this->registerResponder('xml', function ($data) {
			return $this->buildOCSResponse('xml', $data);
		});
	}

	/**
	 * @param int $version
	 * @since 11.0.0
	 * @internal
	 */
	public function setOCSVersion($version) {
		$this->ocsVersion = $version;
	}

	/**
	 * Since the OCS endpoints default to XML we need to find out the format
	 * again
	 * @param mixed $response the value that was returned from a controller and
	 *                        is not a Response instance
	 * @param string $format the format for which a formatter has been registered
	 * @throws \DomainException if format does not match a registered formatter
	 * @return Response
	 * @since 9.1.0
	 */
	public function buildResponse($response, $format = 'xml') {
		return parent::buildResponse($response, $format);
	}

	/**
	 * Unwrap data and build ocs response
	 * @param string $format json or xml
	 * @param DataResponse $data the data which should be transformed
	 * @since 8.1.0
	 * @return \OC\AppFramework\OCS\BaseResponse
	 */
	private function buildOCSResponse($format, DataResponse $data) {
		if ($this->ocsVersion === 1) {
			return new \OC\AppFramework\OCS\V1Response($data, $format);
		}
		return new \OC\AppFramework\OCS\V2Response($data, $format);
	}
}
