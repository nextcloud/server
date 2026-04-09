<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\AppFramework;

use Exception;
use OCP\AppFramework\Http\Response;

/**
 * Middleware is used to provide hooks before or after controller methods and
 * deal with possible exceptions raised in the controller methods.
 * They're modeled after Django's middleware system:
 * https://docs.djangoproject.com/en/dev/topics/http/middleware/
 * @since 6.0.0
 */
abstract class Middleware {
	/**
	 * This is being run in normal order before the controller is being
	 * called which allows several modifications and checks
	 *
	 * @param Controller $controller the controller that is being called
	 * @param string $methodName the name of the method that will be called on
	 *                           the controller
	 * @return void
	 * @since 6.0.0
	 */
	public function beforeController(Controller $controller, string $methodName) {
	}


	/**
	 * This is being run when either the beforeController method or the
	 * controller method itself is throwing an exception. The middleware is
	 * asked in reverse order to handle the exception and to return a response.
	 * If the response is null, it is assumed that the exception could not be
	 * handled and the error will be thrown again
	 *
	 * @param Controller $controller the controller that is being called
	 * @param string $methodName the name of the method that will be called on
	 *                           the controller
	 * @param Exception $exception the thrown exception
	 * @throws Exception the passed in exception if it can't handle it
	 * @return Response a Response object in case that the exception was handled
	 * @since 6.0.0
	 */
	public function afterException(Controller $controller, string $methodName, Exception $exception) {
		throw $exception;
	}


	/**
	 * This is being run after a successful controllermethod call and allows
	 * the manipulation of a Response object. The middleware is run in reverse order
	 *
	 * @param Controller $controller the controller that is being called
	 * @param string $methodName the name of the method that will be called on
	 *                           the controller
	 * @param Response $response the generated response from the controller
	 * @return Response a Response object
	 * @since 6.0.0
	 */
	public function afterController(Controller $controller, string $methodName, Response $response) {
		return $response;
	}


	/**
	 * This is being run after the response object has been rendered and
	 * allows the manipulation of the output. The middleware is run in reverse order
	 *
	 * @param Controller $controller the controller that is being called
	 * @param string $methodName the name of the method that will be called on
	 *                           the controller
	 * @param string $output the generated output from a response
	 * @return string the output that should be printed
	 * @since 6.0.0
	 */
	public function beforeOutput(Controller $controller, string $methodName, string $output) {
		return $output;
	}
}
