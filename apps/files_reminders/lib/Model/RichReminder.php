<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesReminders\Model;

use DateTimeInterface;
use JsonSerializable;
use OCA\FilesReminders\Db\Reminder;
use OCA\FilesReminders\Exception\NodeNotFoundException;
use OCP\Files\IRootFolder;
use OCP\Files\Node;

class RichReminder extends Reminder implements JsonSerializable {
	public function __construct(
		private Reminder $reminder,
		private IRootFolder $root,
	) {
		parent::__construct();
	}

	/**
	 * @throws NodeNotFoundException
	 */
	public function getNode(): Node {
		$node = $this->root->getUserFolder($this->getUserId())->getFirstNodeById($this->getFileId());
		if (!$node) {
			throw new NodeNotFoundException();
		}
		return $node;
	}

	protected function getter(string $name): mixed {
		return $this->reminder->getter($name);
	}

	public function __call(string $methodName, array $args) {
		return $this->reminder->__call($methodName, $args);
	}

	public function jsonSerialize(): array {
		return [
			'userId' => $this->getUserId(),
			'fileId' => $this->getFileId(),
			'path' => $this->getNode()->getPath(),
			'dueDate' => $this->getDueDate()->format(DateTimeInterface::ATOM), // ISO 8601
			'updatedAt' => $this->getUpdatedAt()->format(DateTimeInterface::ATOM), // ISO 8601
			'createdAt' => $this->getCreatedAt()->format(DateTimeInterface::ATOM), // ISO 8601
			'notified' => $this->getNotified(),
		];
	}
}
