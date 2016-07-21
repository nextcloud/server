<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

/**
 * Public interface of ownCloud for apps to use.
 * AppFramework\Controller class
 */

namespace OCP\AppFramework;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\DataResponse;
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
	 * @var array
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
		$this->responders = array(
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
					return $response;
				} else {
					return new JSONResponse($data);
				}
			}
		);
	}


	/**
	 * Parses an HTTP accept header and returns the supported responder type
	 * @param string $acceptHeader
	 * @return string the responder type
	 * @since 7.0.0
	 * @since 9.1.0 Added default parameter
	 */
	public function getResponderByHTTPHeader($acceptHeader, $default='json') {
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
	 * @param \Closure $responder
	 * @since 7.0.0
	 */
	protected function registerResponder($format, \Closure $responder) {
		$this->responders[$format] = $responder;
	}


	/**
	 * Serializes and formats a response
	 * @param mixed $response the value that was returned from a controller and
	 * is not a Response instance
	 * @param string $format the format for which a formatter has been registered
	 * @throws \DomainException if format does not match a registered formatter
	 * @return Response
	 * @since 7.0.0
	 */
	public function buildResponse($response, $format='json') {
		if(array_key_exists($format, $this->responders)) {

			$responder = $this->responders[$format];

			return $responder($response);

		} else {
			throw new \DomainException('No responder registered for format ' .
				$format . '!');
		}
	}


	/**
	 * Lets you access post and get parameters by the index
	 * @deprecated 7.0.0 write your parameters as method arguments instead
	 * @param string $key the key which you want to access in the URL Parameter
	 *                     placeholder, $_POST or $_GET array.
	 *                     The priority how they're returned is the following:
	 *                     1. URL parameters
	 *                     2. POST parameters
	 *                     3. GET parameters
	 * @param string $default If the key is not found, this value will be returned
	 * @return mixed the content of the array
	 * @since 6.0.0
	 */
	public function params($key, $default=null){
		return $this->request->getParam($key, $default);
	}


	/**
	 * Returns all params that were received, be it from the request
	 * (as GET or POST) or through the URL by the route
	 * @deprecated 7.0.0 use $this->request instead
	 * @return array the array with all parameters
	 * @since 6.0.0
	 */
	public function getParams() {
		return $this->request->getParams();
	}


	/**
	 * Returns the method of the request
	 * @deprecated 7.0.0 use $this->request instead
	 * @return string the method of the request (POST, GET, etc)
	 * @since 6.0.0
	 */
	public function method() {
		return $this->request->getMethod();
	}


	/**
	 * Shortcut for accessing an uploaded file through the $_FILES array
	 * @deprecated 7.0.0 use $this->request instead
	 * @param string $key the key that will be taken from the $_FILES array
	 * @return array the file in the $_FILES element
	 * @since 6.0.0
	 */
	public function getUploadedFile($key) {
		return $this->request->getUploadedFile($key);
	}


	/**
	 * Shortcut for getting env variables
	 * @deprecated 7.0.0 use $this->request instead
	 * @param string $key the key that will be taken from the $_ENV array
	 * @return array the value in the $_ENV element
	 * @since 6.0.0
	 */
	public function env($key) {
		return $this->request->getEnv($key);
	}


	/**
	 * Shortcut for getting cookie variables
	 * @deprecated 7.0.0 use $this->request instead
	 * @param string $key the key that will be taken from the $_COOKIE array
	 * @return array the value in the $_COOKIE element
	 * @since 6.0.0
	 */
	public function cookie($key) {
		return $this->request->getCookie($key);
	}


	/**
	 * Shortcut for rendering a template
	 * @deprecated 7.0.0 return a template response instead
	 * @param string $templateName the name of the template
	 * @param array $params the template parameters in key => value structure
	 * @param string $renderAs user renders a full page, blank only your template
	 *                          admin an entry in the admin settings
	 * @param string[] $headers set additional headers in name/value pairs
	 * @return \OCP\AppFramework\Http\TemplateResponse containing the page
	 * @since 6.0.0
	 */
	public function render($templateName, array $params=array(),
							$renderAs='user', array $headers=array()){
		$response = new TemplateResponse($this->appName, $templateName);
		$response->setParams($params);
		$response->renderAs($renderAs);

		foreach($headers as $name => $value){
			$response->addHeader($name, $value);
		}

		return $response;
	}


}
