<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\AppFramework\Services;

use Closure;

/**
 * @since 20.0.0
 */
interface IInitialState {
	/**
	 * Allows an app to provide its initial state to the template system.
	 * Use this if you know your initial state sill be used for example if
	 * you are in the render function of you controller.
	 *
	 * @since 20.0.0
	 *
	 * @param string $key
	 * @param bool|int|float|string|array|\JsonSerializable $data
	 */
	public function provideInitialState(string $key, $data): void;

	/**
	 * Allows an app to provide its initial state via a lazy method.
	 * This will call the closure when the template is being generated.
	 * Use this if your app is injected into pages. Since then the render method
	 * is not called explicitly. But we do not want to load the state on webdav
	 * requests for example.
	 *
	 * @since 20.0.0
	 *
	 * @param string $key
	 * @param Closure $closure returns a primitive or an object that implements JsonSerializable
	 * @psalm-param Closure():bool|Closure():int|Closure():float|Closure():string|Closure():array|Closure():\JsonSerializable $closure
	 */
	public function provideLazyInitialState(string $key, Closure $closure): void;
}
