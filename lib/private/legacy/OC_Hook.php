<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

use OC\Files\Filesystem;
use OC\ServerNotAvailableException;
use OCP\HintException;
use OCP\Server;
use OCP\Share;
use Psr\Log\LoggerInterface;

class OC_Hook {
	public static $thrownExceptions = [];

	private static array $registered = [];

	private static array $allowList = [
		[Filesystem::CLASSNAME, Filesystem::signal_read],
		[Filesystem::CLASSNAME, Filesystem::signal_create],
		[Filesystem::CLASSNAME, Filesystem::signal_post_create],
		[Filesystem::CLASSNAME, Filesystem::signal_update],
		[Filesystem::CLASSNAME, Filesystem::signal_post_update],
		[Filesystem::CLASSNAME, Filesystem::signal_write],
		[Filesystem::CLASSNAME, Filesystem::signal_post_write],
		[Filesystem::CLASSNAME, Filesystem::signal_delete],
		[Filesystem::CLASSNAME, Filesystem::signal_post_delete],
		[Filesystem::CLASSNAME, Filesystem::signal_rename],
		[Filesystem::CLASSNAME, Filesystem::signal_post_rename],
		[Filesystem::CLASSNAME, Filesystem::signal_copy],
		[Filesystem::CLASSNAME, Filesystem::signal_post_copy],
		[Filesystem::CLASSNAME, Filesystem::signal_touch],
		[Filesystem::CLASSNAME, Filesystem::signal_post_touch],
		[Filesystem::CLASSNAME, Filesystem::signal_delete_mount],
		[Filesystem::CLASSNAME, Filesystem::signal_create_mount],
		[Filesystem::CLASSNAME, Filesystem::signal_setup],
		[Filesystem::CLASSNAME, Filesystem::signal_pre_setup],
		[Filesystem::CLASSNAME, Filesystem::signal_post_init_mountpoints],
		[Filesystem::CLASSNAME, 'umount'],
		[Share::class,'share_link_access'],
		[Share::class,'pre_unshare'],
		[Share::class,'post_unshare'],
		[Share::class,'post_unshareFromSelf'],
		[Share::class,'pre_shared'],
		[Share::class,'post_shared'],
		[Share::class,'post_set_expiration_date'],
		[Share::class,'post_update_password'],
		[Share::class,'post_update_permissions'],
		['\OC\Share','verifyExpirationDate'],
		['\OC\Files\Storage\Shared','fopen'],
		['\OC\Files\Storage\Shared','file_get_contents'],
		['\OC\Files\Storage\Shared','file_put_contents'],
		[\OCA\Files_Trashbin\Trashbin::class,'post_moveToTrash'],
		[\OCA\Files_Trashbin\Trashbin::class,'post_restore'],
		['\OCP\Trashbin','preDeleteAll'],
		['\OCP\Trashbin','deleteAll'],
		['\OCP\Versions','rollback'],
		['\OCP\Versions','preDelete'],
		['\OCP\Versions','delete'],
		[OC_User::class,'pre_createUser'],
		[OC_User::class,'post_createUser'],
		[OC_User::class,'pre_deleteUser'],
		[OC_User::class,'post_deleteUser'],
		[OC_User::class,'pre_setPassword'],
		[OC_User::class,'post_setPassword'],
		[OC_User::class,'pre_login'],
		[OC_User::class,'post_login'],
		[OC_User::class,'logout'],
		[OC_User::class,'changeUser'],
		['\OC\User','assignedUserId'],
		['\OC\User','preUnassignedUserId'],
		['\OC\User','postUnassignedUserId'],
		[OC\Files\Cache\Scanner::class,'scan_file'],
		[OC\Files\Cache\Scanner::class,'post_scan_file'],
		['Scanner','removeFromCache'],
		['Scanner','addToCache'],
		['Scanner','correctFolderSize'],
		['\OCP\Config','js'],
		['\OC\Core\LostPassword\Controller\LostController','post_passwordReset'],
		['\OC\Core\LostPassword\Controller\LostController','pre_passwordReset'],
	];

	/**
	 * connects a function to a hook
	 *
	 * @param string $signalClass class name of emitter
	 * @param string $signalName name of signal
	 * @param string|object $slotClass class name of slot
	 * @param string $slotName name of slot
	 *
	 * This function makes it very easy to connect to use hooks.
	 *
	 * TODO: write example
	 */
	public static function connect(string $signalClass, string $signalName, string|object $slotClass, string $slotName): bool {
		if (str_starts_with($signalClass, '\\')) {
			$signalName = substr($signalClass, 1);
		}
		$found = array_find(self::$allowList, function ($allowed) use ($signalClass, $signalName) {
			[$allowedClass, $allowedSignal] = $allowed;
			return $allowedClass === $signalClass && $allowedSignal === $signalName;
		}) !== null;

		if (!$found) {
			throw new \RuntimeException("The signal $signalClass::$signalName is no longer emitted in server. Listening to it is NOOP.");
		}
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
			if ($hook['class'] === $slotClass && $hook['name'] === $slotName) {
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
	 * @throws HintException
	 * @throws ServerNotAvailableException Emits a signal. To get data from the slot use references!
	 *
	 * TODO: write example
	 */
	public static function emit(string $signalClass, string $signalName, $params = []): bool {
		if (str_starts_with($signalClass, '\\')) {
			$signalName = substr($signalClass, 1);
		}
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
				Server::get(LoggerInterface::class)->error($e->getMessage(), ['exception' => $e]);
				if ($e instanceof HintException) {
					throw $e;
				}
				if ($e instanceof ServerNotAvailableException) {
					throw $e;
				}
			}
		}

		return true;
	}

	/**
	 * Clear hooks
	 */
	public static function clear(string $signalClass = '', string $signalName = ''): void {
		if ($signalClass) {
			if (str_starts_with($signalClass, '\\')) {
				$signalName = substr($signalClass, 1);
			}
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
	public static function getHooks(): array {
		return self::$registered;
	}
}
