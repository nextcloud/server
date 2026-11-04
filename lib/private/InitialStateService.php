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

	#[\Override]
	public function provideInitialState(string $appName, string $key, $data): void {
		// Scalars and JsonSerializable are fine
		if (is_scalar($data) || $data instanceof \JsonSerializable || is_array($data)) {
			try {
				// JSON_HEX_TAG keeps "</script>" and "<!--" out of the JSON so it can be embedded in a <script> element
				$this->states[$appName][$key] = json_encode($data, JSON_THROW_ON_ERROR | JSON_HEX_TAG);
			} catch (\JsonException $e) {
				$this->logger->error('Invalid ' . $key . ' data provided to provideInitialState by ' . $appName, ['exception' => $e]);
			}
			return;
		}

		$this->logger->warning('Invalid ' . $key . ' data provided to provideInitialState by ' . $appName);
	}

	#[\Override]
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
				$provider = $this->container->get($initialState->getService());
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

	/**
	 * Get all initial states as one JSON object of the shape `{"<app>": {"<key>": <value>}}`.
	 *
	 * The result contains no "<" or ">", so it can be embedded in a `<script type="application/json">` element as is.
	 */
	public function getInitialStatesJson(): string {
		$this->invokeLazyStateCallbacks();
		$this->loadLazyStates();

		$apps = [];
		foreach ($this->states as $app => $states) {
			$entries = [];
			foreach ($states as $key => $value) {
				$entries[] = json_encode((string)$key, JSON_THROW_ON_ERROR | JSON_HEX_TAG) . ':' . $value;
			}
			$apps[] = json_encode((string)$app, JSON_THROW_ON_ERROR | JSON_HEX_TAG) . ':{' . implode(',', $entries) . '}';
		}
		return '{' . implode(',', $apps) . '}';
	}
}
