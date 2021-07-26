<?php
/**
 * @copyright Copyright (c) 2016 Robin Appelman <robin@icewind.nl>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 *
 */

namespace Icewind\SMB;

interface INotifyHandler {
	// https://msdn.microsoft.com/en-us/library/dn392331.aspx
	const NOTIFY_ADDED = 1;
	const NOTIFY_REMOVED = 2;
	const NOTIFY_MODIFIED = 3;
	const NOTIFY_RENAMED_OLD = 4;
	const NOTIFY_RENAMED_NEW = 5;
	const NOTIFY_ADDED_STREAM = 6;
	const NOTIFY_REMOVED_STREAM = 7;
	const NOTIFY_MODIFIED_STREAM = 8;
	const NOTIFY_REMOVED_BY_DELETE = 9;

	/**
	 * Get all changes detected since the start of the notify process or the last call to getChanges
	 *
	 * @return Change[]
	 */
	public function getChanges(): array;

	/**
	 * Listen actively to all incoming changes
	 *
	 * Note that this is a blocking process and will cause the process to block forever if not explicitly terminated
	 *
	 * @param callable(Change):?bool $callback
	 */
	public function listen(callable $callback): void;

	/**
	 * Stop listening for changes
	 *
	 * Note that any pending changes will be discarded
	 */
	public function stop(): void;
}
