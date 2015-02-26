<?php
/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2012, 2014 Bernhard Posselt <dev@bernhard-posselt.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
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
use OCP\IRequest;


/**
 * Base class to inherit your controllers from
 */
abstract class Controller {

	/**
	 * app name
	 * @var string
	 */
	protected $appName;

	/**
	 * current request
	 * @var \OCP\IRequest
	 */
	protected $request;

	private $responders;

	/**
	 * constructor of the controller
	 * @param string $appName the name of the app
	 * @param IRequest $request an instance of the request
	 */
	public function __construct($appName,
	                            IRequest $request){
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
					$response->setHeaders(array_merge($data->getHeaders(), $response->getHeaders()));
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
	 */
	public function getResponderByHTTPHeader($acceptHeader) {
		$headers = explode(',', $acceptHeader);

		// return the first matching responder
		foreach ($headers as $header) {
			$header = strtolower(trim($header));

			$responder = str_replace('application/', '', $header);

			if (array_key_exists($responder, $this->responders)) {
				return $responder;
			}
		}

		// no matching header defaults to json
		return 'json';
	}


	/**
	 * Registers a formatter for a type
	 * @param string $format
	 * @param \Closure $responder
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
	 * @deprecated write your parameters as method arguments instead
	 * @param string $key the key which you want to access in the URL Parameter
	 *                     placeholder, $_POST or $_GET array.
	 *                     The priority how they're returned is the following:
	 *                     1. URL parameters
	 *                     2. POST parameters
	 *                     3. GET parameters
	 * @param string $default If the key is not found, this value will be returned
	 * @return mixed the content of the array
	 */
	public function params($key, $default=null){
		return $this->request->getParam($key, $default);
	}


	/**
	 * Returns all params that were received, be it from the request
	 * (as GET or POST) or through the URL by the route
	 * @deprecated use $this->request instead
	 * @return array the array with all parameters
	 */
	public function getParams() {
		return $this->request->getParams();
	}


	/**
	 * Returns the method of the request
	 * @deprecated use $this->request instead
	 * @return string the method of the request (POST, GET, etc)
	 */
	public function method() {
		return $this->request->getMethod();
	}


	/**
	 * Shortcut for accessing an uploaded file through the $_FILES array
	 * @deprecated use $this->request instead
	 * @param string $key the key that will be taken from the $_FILES array
	 * @return array the file in the $_FILES element
	 */
	public function getUploadedFile($key) {
		return $this->request->getUploadedFile($key);
	}


	/**
	 * Shortcut for getting env variables
	 * @deprecated use $this->request instead
	 * @param string $key the key that will be taken from the $_ENV array
	 * @return array the value in the $_ENV element
	 */
	public function env($key) {
		return $this->request->getEnv($key);
	}


	/**
	 * Shortcut for getting cookie variables
	 * @deprecated use $this->request instead
	 * @param string $key the key that will be taken from the $_COOKIE array
	 * @return array the value in the $_COOKIE element
	 */
	public function cookie($key) {
		return $this->request->getCookie($key);
	}


	/**
	 * Shortcut for rendering a template
	 * @deprecated return a template response instead
	 * @param string $templateName the name of the template
	 * @param array $params the template parameters in key => value structure
	 * @param string $renderAs user renders a full page, blank only your template
	 *                          admin an entry in the admin settings
	 * @param string[] $headers set additional headers in name/value pairs
	 * @return \OCP\AppFramework\Http\TemplateResponse containing the page
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
