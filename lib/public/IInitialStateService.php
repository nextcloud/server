<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP;

use Closure;

/**
 * @since 16.0.0
 * @deprecated 21 Use {@see \OCP\AppFramework\Services\IInitialState} or {@see \OCP\AppFramework\Services\InitialStateProvider}
 * @see \OCP\AppFramework\Services\IInitialState
 */
interface IInitialStateService {
	/**
	 * Allows an app to provide its initial state to the template system.
	 * Use this if you know your initial state still be used for example if
	 * you are in the render function of you controller.
	 *
	 * @since 16.0.0
	 *
	 * @param string $appName
	 * @param string $key
	 * @param bool|int|float|string|array|\JsonSerializable $data
	 *
	 * @deprecated 21 Use {@see \OCP\AppFramework\Services\IInitialState} or {@see \OCP\AppFramework\Services\InitialStateProvider}
	 * @see \OCP\AppFramework\Services\IInitialState::provideInitialState()
	 */
	public function provideInitialState(string $appName, string $key, $data): void;

	/**
	 * Allows an app to provide its initial state via a lazy method.
	 * This will call the closure when the template is being generated.
	 * Use this if your app is injected into pages. Since then the render method
	 * is not called explicitly. But we do not want to load the state on webdav
	 * requests for example.
	 *
	 * @since 16.0.0
	 *
	 * @param string $appName
	 * @param string $key
	 * @param Closure $closure returns a primitive or an object that implements JsonSerializable
	 *
	 * @deprecated 21 Use {@see \OCP\AppFramework\Services\IInitialState} or {@see \OCP\AppFramework\Services\InitialStateProvider}
	 * @see \OCP\AppFramework\Services\IInitialState::provideLazyInitialState()
	 */
	public function provideLazyInitialState(string $appName, string $key, Closure $closure): void;
}
