<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\Files\Filesystem;
use OCP\Util;

/**
 * Helper class to register hooks on
 */
class HookHelper {
	public static $hookCalls;

	public static function setUpHooks() {
		self::clear();
		Util::connectHook(
			Filesystem::CLASSNAME,
			Filesystem::signal_create,
			'\Test\HookHelper',
			'createCallback'
		);
		Util::connectHook(
			Filesystem::CLASSNAME,
			Filesystem::signal_update,
			'\Test\HookHelper',
			'updateCallback'
		);
		Util::connectHook(
			Filesystem::CLASSNAME,
			Filesystem::signal_write,
			'\Test\HookHelper',
			'writeCallback'
		);

		Util::connectHook(
			Filesystem::CLASSNAME,
			Filesystem::signal_post_create,
			'\Test\HookHelper',
			'postCreateCallback'
		);
		Util::connectHook(
			Filesystem::CLASSNAME,
			Filesystem::signal_post_update,
			'\Test\HookHelper',
			'postUpdateCallback'
		);
		Util::connectHook(
			Filesystem::CLASSNAME,
			Filesystem::signal_post_write,
			'\Test\HookHelper',
			'postWriteCallback'
		);
	}

	public static function clear() {
		self::$hookCalls = [];
	}

	public static function createCallback($params) {
		self::$hookCalls[] = [
			'signal' => Filesystem::signal_create,
			'params' => $params
		];
	}

	public static function updateCallback($params) {
		self::$hookCalls[] = [
			'signal' => Filesystem::signal_update,
			'params' => $params
		];
	}

	public static function writeCallback($params) {
		self::$hookCalls[] = [
			'signal' => Filesystem::signal_write,
			'params' => $params
		];
	}

	public static function postCreateCallback($params) {
		self::$hookCalls[] = [
			'signal' => Filesystem::signal_post_create,
			'params' => $params
		];
	}

	public static function postUpdateCallback($params) {
		self::$hookCalls[] = [
			'signal' => Filesystem::signal_post_update,
			'params' => $params
		];
	}

	public static function postWriteCallback($params) {
		self::$hookCalls[] = [
			'signal' => Filesystem::signal_post_write,
			'params' => $params
		];
	}

	/**
	 * Callback that sets the run parameter to false
	 */
	public static function cancellingCallback($params) {
		$params[Filesystem::signal_param_run] = false;
	}
}
