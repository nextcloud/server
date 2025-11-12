<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\AppFramework\Http;

use OC\AppFramework\Http;
use OC\AppFramework\Middleware\MiddlewareDispatcher;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OC\DB\ConnectionAdapter;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\ParameterOutOfRangeException;
use OCP\AppFramework\Http\Response;
use OCP\Diagnostics\IEventLogger;
use OCP\IConfig;
use OCP\IRequest;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class to dispatch the request to the middleware dispatcher
 */
class Dispatcher {
	/** @var MiddlewareDispatcher */
	private $middlewareDispatcher;

	/** @var Http */
	private $protocol;

	/** @var ControllerMethodReflector */
	private $reflector;

	/** @var IRequest */
	private $request;

	/** @var IConfig */
	private $config;

	/** @var ConnectionAdapter */
	private $connection;

	/** @var LoggerInterface */
	private $logger;

	/** @var IEventLogger */
	private $eventLogger;

	private ContainerInterface $appContainer;

	/**
	 * @param Http $protocol the http protocol with contains all status headers
	 * @param MiddlewareDispatcher $middlewareDispatcher the dispatcher which
	 *                                                   runs the middleware
	 * @param ControllerMethodReflector $reflector the reflector that is used to inject
	 *                                             the arguments for the controller
	 * @param IRequest $request the incoming request
	 * @param IConfig $config
	 * @param ConnectionAdapter $connection
	 * @param LoggerInterface $logger
	 * @param IEventLogger $eventLogger
	 */
	public function __construct(
		Http $protocol,
		MiddlewareDispatcher $middlewareDispatcher,
		ControllerMethodReflector $reflector,
		IRequest $request,
		IConfig $config,
		ConnectionAdapter $connection,
		LoggerInterface $logger,
		IEventLogger $eventLogger,
		ContainerInterface $appContainer,
	) {
		$this->protocol = $protocol;
		$this->middlewareDispatcher = $middlewareDispatcher;
		$this->reflector = $reflector;
		$this->request = $request;
		$this->config = $config;
		$this->connection = $connection;
		$this->logger = $logger;
		$this->eventLogger = $eventLogger;
		$this->appContainer = $appContainer;
	}


	/**
	 * Handles a request and calls the dispatcher on the controller
	 * @param Controller $controller the controller which will be called
	 * @param string $methodName the method name which will be called on
	 *                           the controller
	 * @return array $array[0] contains the http status header as a string,
	 *               $array[1] contains response headers as an array,
	 *               $array[2] contains response cookies as an array,
	 *               $array[3] contains the response output as a string,
	 *               $array[4] contains the response object
	 * @throws \Exception
	 */
	public function dispatch(Controller $controller, string $methodName): array {
		$out = [null, [], null];

		try {
			// prefill reflector with everything that's needed for the
			// middlewares
			$this->reflector->reflect($controller, $methodName);

			$this->middlewareDispatcher->beforeController($controller,
				$methodName);

			$databaseStatsBefore = [];
			if ($this->config->getSystemValueBool('debug', false)) {
				$databaseStatsBefore = $this->connection->getInner()->getStats();
			}

			$response = $this->executeController($controller, $methodName);

			if (!empty($databaseStatsBefore)) {
				$databaseStatsAfter = $this->connection->getInner()->getStats();
				$numBuilt = $databaseStatsAfter['built'] - $databaseStatsBefore['built'];
				$numExecuted = $databaseStatsAfter['executed'] - $databaseStatsBefore['executed'];

				if ($numBuilt > 50) {
					$this->logger->debug('Controller {class}::{method} created {count} QueryBuilder objects, please check if they are created inside a loop by accident.', [
						'class' => get_class($controller),
						'method' => $methodName,
						'count' => $numBuilt,
					]);
				}

				if ($numExecuted > 100) {
					$this->logger->warning('Controller {class}::{method} executed {count} queries.', [
						'class' => get_class($controller),
						'method' => $methodName,
						'count' => $numExecuted,
					]);
				}
			}

			// if an exception appears, the middleware checks if it can handle the
			// exception and creates a response. If no response is created, it is
			// assumed that there's no middleware who can handle it and the error is
			// thrown again
		} catch (\Exception $exception) {
			$response = $this->middlewareDispatcher->afterException(
				$controller, $methodName, $exception);
		} catch (\Throwable $throwable) {
			$exception = new \Exception($throwable->getMessage() . ' in file \'' . $throwable->getFile() . '\' line ' . $throwable->getLine(), $throwable->getCode(), $throwable);
			$response = $this->middlewareDispatcher->afterException(
				$controller, $methodName, $exception);
		}

		$response = $this->middlewareDispatcher->afterController(
			$controller, $methodName, $response);

		// depending on the cache object the headers need to be changed
		$out[0] = $this->protocol->getStatusHeader($response->getStatus());
		$out[1] = array_merge($response->getHeaders());
		$out[2] = $response->getCookies();
		$out[3] = $this->middlewareDispatcher->beforeOutput(
			$controller, $methodName, $response->render()
		);
		$out[4] = $response;

		return $out;
	}


	/**
	 * Uses the reflected parameters, types and request parameters to execute
	 * the controller
	 * @param Controller $controller the controller to be executed
	 * @param string $methodName the method on the controller that should be executed
	 * @return Response
	 */
	private function executeController(Controller $controller, string $methodName): Response {
		$arguments = [];

		// valid types that will be cast
		$types = ['int', 'integer', 'bool', 'boolean', 'float', 'double'];

		foreach ($this->reflector->getParameters() as $param => $default) {
			// try to get the parameter from the request object and cast
			// it to the type annotated in the @param annotation
			$value = $this->request->getParam($param, $default);
			$type = $this->reflector->getType($param);

			// Converted the string `'false'` to false when the controller wants a boolean
			if ($value === 'false' && ($type === 'bool' || $type === 'boolean')) {
				$value = false;
			} elseif ($value !== null && \in_array($type, $types, true)) {
				settype($value, $type);
				$this->ensureParameterValueSatisfiesRange($param, $value);
			} elseif ($value === null && $type !== null && $this->appContainer->has($type)) {
				$value = $this->appContainer->get($type);
			}

			$arguments[] = $value;
		}

		$this->eventLogger->start('controller:' . get_class($controller) . '::' . $methodName, 'App framework controller execution');
		$response = \call_user_func_array([$controller, $methodName], $arguments);
		$this->eventLogger->end('controller:' . get_class($controller) . '::' . $methodName);

		if (!($response instanceof Response)) {
			$this->logger->debug($controller::class . '::' . $methodName . ' returned raw data. Please wrap it in a Response or one of it\'s inheritors.');
		}

		// format response
		if ($response instanceof DataResponse || !($response instanceof Response)) {
			$format = $this->request->getFormat();
			if ($format !== null && $controller->isResponderRegistered($format)) {
				$response = $controller->buildResponse($response, $format);
			} else {
				$response = $controller->buildResponse($response);
			}
		}

		return $response;
	}

	/**
	 * @psalm-param mixed $value
	 * @throws ParameterOutOfRangeException
	 */
	private function ensureParameterValueSatisfiesRange(string $param, $value): void {
		$rangeInfo = $this->reflector->getRange($param);
		if ($rangeInfo) {
			if ($value < $rangeInfo['min'] || $value > $rangeInfo['max']) {
				throw new ParameterOutOfRangeException(
					$param,
					$value,
					$rangeInfo['min'],
					$rangeInfo['max'],
				);
			}
		}
	}
}
