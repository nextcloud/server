<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

use Psr\Log\LoggerInterface;

class OC_Hook {
	public static $thrownExceptions = [];

	private static $registered = [];

	/**
	 * connects a function to a hook
	 *
	 * @param string $signalClass class name of emitter
	 * @param string $signalName name of signal
	 * @param string|object $slotClass class name of slot
	 * @param string $slotName name of slot
	 * @return bool
	 *
	 * This function makes it very easy to connect to use hooks.
	 *
	 * TODO: write example
	 */
	public static function connect($signalClass, $signalName, $slotClass, $slotName) {
		// If we're trying to connect to an emitting class that isn't
		// yet registered, register it
		if (!array_key_exists($signalClass, self::$registered)) {
			self::$registered[$signalClass] = [];
		}
		// If we're trying to connect to an emitting method that isn't
		// yet registered, register it with the emitting class
		if (!array_key_exists($signalName, self::$registered[$signalClass])) {
			self::$registered[$signalClass][$signalName] = [];
		}

		// don't connect hooks twice
		foreach (self::$registered[$signalClass][$signalName] as $hook) {
			if ($hook['class'] === $slotClass and $hook['name'] === $slotName) {
				return false;
			}
		}
		// Connect the hook handler to the requested emitter
		self::$registered[$signalClass][$signalName][] = [
			'class' => $slotClass,
			'name' => $slotName
		];

		// No chance for failure ;-)
		return true;
	}

	/**
	 * emits a signal
	 *
	 * @param string $signalClass class name of emitter
	 * @param string $signalName name of signal
	 * @param mixed $params default: array() array with additional data
	 * @return bool true if slots exists or false if not
	 * @throws \OCP\HintException
	 * @throws \OC\ServerNotAvailableException Emits a signal. To get data from the slot use references!
	 *
	 * TODO: write example
	 */
	public static function emit($signalClass, $signalName, $params = []) {
		// Return false if no hook handlers are listening to this
		// emitting class
		if (!array_key_exists($signalClass, self::$registered)) {
			return false;
		}

		// Return false if no hook handlers are listening to this
		// emitting method
		if (!array_key_exists($signalName, self::$registered[$signalClass])) {
			return false;
		}

		// Call all slots
		foreach (self::$registered[$signalClass][$signalName] as $i) {
			try {
				call_user_func([ $i['class'], $i['name'] ], $params);
			} catch (Exception $e) {
				self::$thrownExceptions[] = $e;
				\OCP\Server::get(LoggerInterface::class)->error($e->getMessage(), ['exception' => $e]);
				if ($e instanceof \OCP\HintException) {
					throw $e;
				}
				if ($e instanceof \OC\ServerNotAvailableException) {
					throw $e;
				}
			}
		}

		return true;
	}

	/**
	 * clear hooks
	 * @param string $signalClass
	 * @param string $signalName
	 */
	public static function clear($signalClass = '', $signalName = '') {
		if ($signalClass) {
			if ($signalName) {
				self::$registered[$signalClass][$signalName] = [];
			} else {
				self::$registered[$signalClass] = [];
			}
		} else {
			self::$registered = [];
		}
	}

	/**
	 * DO NOT USE!
	 * For unit tests ONLY!
	 */
	public static function getHooks() {
		return self::$registered;
	}
}
