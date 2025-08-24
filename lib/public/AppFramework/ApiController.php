<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\AppFramework;

use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;

/**
 * Base class to inherit your controllers from that are used for RESTful APIs
 * @since 7.0.0
 */
abstract class ApiController extends Controller {
	private $corsMethods;
	private $corsAllowedHeaders;
	private $corsMaxAge;

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
	 * @since 7.0.0
	 */
	public function __construct($appName,
		IRequest $request,
		$corsMethods = 'PUT, POST, GET, DELETE, PATCH',
		$corsAllowedHeaders = 'Authorization, Content-Type, Accept',
		$corsMaxAge = 1728000) {
		parent::__construct($appName, $request);
		$this->corsMethods = $corsMethods;
		$this->corsAllowedHeaders = $corsAllowedHeaders;
		$this->corsMaxAge = $corsMaxAge;
	}


	/**
	 * This method implements a preflighted cors response for you that you can
	 * link to for the options request
	 *
	 * @since 7.0.0
	 */
	#[NoCSRFRequired]
	#[PublicPage]
	#[NoAdminRequired]
	public function preflightedCors() {
		$origin = $this->request->getHeader('origin');
		if ($origin === '') {
			$origin = '*';
		}

		$response = new Response();
		$response->addHeader('Access-Control-Allow-Origin', $origin);
		$response->addHeader('Access-Control-Allow-Methods', $this->corsMethods);
		$response->addHeader('Access-Control-Max-Age', (string)$this->corsMaxAge);
		$response->addHeader('Access-Control-Allow-Headers', $this->corsAllowedHeaders);
		$response->addHeader('Access-Control-Allow-Credentials', 'false');
		return $response;
	}
}
