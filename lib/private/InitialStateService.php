<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC;

use Closure;
use OC\AppFramework\Bootstrap\Coordinator;
use OCP\AppFramework\QueryException;
use OCP\AppFramework\Services\InitialStateProvider;
use OCP\IInitialStateService;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class InitialStateService implements IInitialStateService {

	/** @var string[][] */
	private array $states = [];

	/** @var Closure[][] */
	private array $lazyStates = [];


	public function __construct(
		private LoggerInterface $logger,
		private Coordinator $bootstrapCoordinator,
		private ContainerInterface $container,
	) {
	}

	public function provideInitialState(string $appName, string $key, $data): void {
		// Scalars and JsonSerializable are fine
		if (is_scalar($data) || $data instanceof \JsonSerializable || is_array($data)) {
			if (!isset($this->states[$appName])) {
				$this->states[$appName] = [];
			}
			try {
				$this->states[$appName][$key] = json_encode($data, JSON_THROW_ON_ERROR);
			} catch (\JsonException $e) {
				$this->logger->error('Invalid ' . $key . ' data provided to provideInitialState by ' . $appName, ['exception' => $e]);
			}
			return;
		}

		$this->logger->warning('Invalid ' . $key . ' data provided to provideInitialState by ' . $appName);
	}

	public function provideLazyInitialState(string $appName, string $key, Closure $closure): void {
		if (!isset($this->lazyStates[$appName])) {
			$this->lazyStates[$appName] = [];
		}
		$this->lazyStates[$appName][$key] = $closure;
	}

	/**
	 * Invoke all callbacks to populate the `states` property
	 */
	private function invokeLazyStateCallbacks(): void {
		foreach ($this->lazyStates as $app => $lazyStates) {
			foreach ($lazyStates as $key => $lazyState) {
				$startTime = microtime(true);
				$this->provideInitialState($app, $key, $lazyState());
				$endTime = microtime(true);
				$duration = $endTime - $startTime;
				if ($duration > 1) {
					$this->logger->warning('Lazy initial state provider for {key} took {duration} seconds.', [
						'app' => $app,
						'key' => $key,
						'duration' => round($duration, 2),
					]);
				}
			}
		}
		$this->lazyStates = [];
	}

	/**
	 * Load the lazy states via the IBootstrap mechanism
	 */
	private function loadLazyStates(): void {
		$context = $this->bootstrapCoordinator->getRegistrationContext();

		if ($context === null) {
			// To early, nothing to do yet
			return;
		}

		$initialStates = $context->getInitialStates();
		foreach ($initialStates as $initialState) {
			try {
				$provider = $this->container->query($initialState->getService());
			} catch (QueryException $e) {
				// Log an continue. We can be fault tolerant here.
				$this->logger->error('Could not load initial state provider dynamically: ' . $e->getMessage(), [
					'exception' => $e,
					'app' => $initialState->getAppId(),
				]);
				continue;
			}

			if (!($provider instanceof InitialStateProvider)) {
				// Log an continue. We can be fault tolerant here.
				$this->logger->error('Initial state provider is not an InitialStateProvider instance: ' . $initialState->getService(), [
					'app' => $initialState->getAppId(),
				]);
			}

			$this->provideInitialState($initialState->getAppId(), $provider->getKey(), $provider);
		}
	}

	public function getInitialStates(): array {
		$this->invokeLazyStateCallbacks();
		$this->loadLazyStates();

		$appStates = [];
		foreach ($this->states as $app => $states) {
			foreach ($states as $key => $value) {
				$appStates["$app-$key"] = $value;
			}
		}
		return $appStates;
	}
}
