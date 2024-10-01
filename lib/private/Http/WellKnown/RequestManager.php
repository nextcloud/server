<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Http\WellKnown;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\ServiceRegistration;
use OCP\AppFramework\QueryException;
use OCP\Http\WellKnown\IHandler;
use OCP\Http\WellKnown\IRequestContext;
use OCP\Http\WellKnown\IResponse;
use OCP\Http\WellKnown\JrdResponse;
use OCP\IRequest;
use OCP\IServerContainer;
use Psr\Log\LoggerInterface;
use RuntimeException;
use function array_reduce;

class RequestManager {
	/** @var Coordinator */
	private $coordinator;

	/** @var IServerContainer */
	private $container;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(Coordinator $coordinator,
		IServerContainer $container,
		LoggerInterface $logger) {
		$this->coordinator = $coordinator;
		$this->container = $container;
		$this->logger = $logger;
	}

	public function process(string $service, IRequest $request): ?IResponse {
		$handlers = $this->loadHandlers();
		$context = new class($request) implements IRequestContext {
			/** @var IRequest */
			private $request;

			public function __construct(IRequest $request) {
				$this->request = $request;
			}

			public function getHttpRequest(): IRequest {
				return $this->request;
			}
		};

		$subject = $request->getParam('resource');
		$initialResponse = new JrdResponse($subject ?? '');
		$finalResponse = array_reduce($handlers, function (?IResponse $previousResponse, IHandler $handler) use ($context, $service) {
			return $handler->handle($service, $context, $previousResponse);
		}, $initialResponse);

		if ($finalResponse instanceof JrdResponse && $finalResponse->isEmpty()) {
			return null;
		}

		return $finalResponse;
	}

	/**
	 * @return IHandler[]
	 */
	private function loadHandlers(): array {
		$context = $this->coordinator->getRegistrationContext();

		if ($context === null) {
			throw new RuntimeException('Well known handlers requested before the apps had been fully registered');
		}

		$registrations = $context->getWellKnownHandlers();
		$this->logger->debug(count($registrations) . ' well known handlers registered');

		return array_filter(
			array_map(function (ServiceRegistration $registration) {
				/** @var ServiceRegistration<IHandler> $registration */
				$class = $registration->getService();

				try {
					$handler = $this->container->get($class);

					if (!($handler) instanceof IHandler) {
						$this->logger->error("Well known handler $class is invalid");

						return null;
					}

					return $handler;
				} catch (QueryException $e) {
					$this->logger->error("Could not load well known handler $class", [
						'exception' => $e,
						'app' => $registration->getAppId(),
					]);

					return null;
				}
			}, $registrations)
		);
	}
}
