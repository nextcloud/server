<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Lib\Notify;

use OC\Files\Notify\Change;
use OC\Files\Notify\RenameChange;
use OCP\Files\Notify\IChange;
use OCP\Files\Notify\INotifyHandler;

class SMBNotifyHandler implements INotifyHandler {
	/**
	 * @var string
	 */
	private $root;

	private $oldRenamePath = null;

	/**
	 * SMBNotifyHandler constructor.
	 *
	 * @param \Icewind\SMB\INotifyHandler $shareNotifyHandler
	 * @param string $root
	 */
	public function __construct(
		private \Icewind\SMB\INotifyHandler $shareNotifyHandler,
		$root,
	) {
		$this->root = str_replace('\\', '/', $root);
	}

	private function relativePath($fullPath) {
		if ($fullPath === $this->root) {
			return '';
		} elseif (substr($fullPath, 0, strlen($this->root)) === $this->root) {
			return substr($fullPath, strlen($this->root));
		} else {
			return null;
		}
	}

	public function listen(callable $callback) {
		$oldRenamePath = null;
		$this->shareNotifyHandler->listen(function (\Icewind\SMB\Change $shareChange) use ($callback) {
			$change = $this->mapChange($shareChange);
			if (!is_null($change)) {
				return $callback($change);
			} else {
				return true;
			}
		});
	}

	/**
	 * Get all changes detected since the start of the notify process or the last call to getChanges
	 *
	 * @return IChange[]
	 */
	public function getChanges() {
		$shareChanges = $this->shareNotifyHandler->getChanges();
		$changes = [];
		foreach ($shareChanges as $shareChange) {
			$change = $this->mapChange($shareChange);
			if ($change) {
				$changes[] = $change;
			}
		}
		return $changes;
	}

	/**
	 * Stop listening for changes
	 *
	 * Note that any pending changes will be discarded
	 */
	public function stop() {
		$this->shareNotifyHandler->stop();
	}

	/**
	 * @param \Icewind\SMB\Change $change
	 * @return IChange|null
	 */
	private function mapChange(\Icewind\SMB\Change $change) {
		$path = $this->relativePath($change->getPath());
		if (is_null($path)) {
			return null;
		}
		if ($change->getCode() === \Icewind\SMB\INotifyHandler::NOTIFY_RENAMED_OLD) {
			$this->oldRenamePath = $path;
			return null;
		}
		$type = $this->mapNotifyType($change->getCode());
		if (is_null($type)) {
			return null;
		}
		if ($type === IChange::RENAMED) {
			if (!is_null($this->oldRenamePath)) {
				$result = new RenameChange($type, $this->oldRenamePath, $path);
				$this->oldRenamePath = null;
			} else {
				$result = null;
			}
		} else {
			$result = new Change($type, $path);
		}
		return $result;
	}

	private function mapNotifyType($smbType) {
		switch ($smbType) {
			case \Icewind\SMB\INotifyHandler::NOTIFY_ADDED:
				return IChange::ADDED;
			case \Icewind\SMB\INotifyHandler::NOTIFY_REMOVED:
				return IChange::REMOVED;
			case \Icewind\SMB\INotifyHandler::NOTIFY_MODIFIED:
			case \Icewind\SMB\INotifyHandler::NOTIFY_ADDED_STREAM:
			case \Icewind\SMB\INotifyHandler::NOTIFY_MODIFIED_STREAM:
			case \Icewind\SMB\INotifyHandler::NOTIFY_REMOVED_STREAM:
				return IChange::MODIFIED;
			case \Icewind\SMB\INotifyHandler::NOTIFY_RENAMED_NEW:
				return IChange::RENAMED;
			default:
				return null;
		}
	}
}
