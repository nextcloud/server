<?php

declare(strict_types=1);

/**
 * @copyright 2023 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
		$nodes = $this->root->getUserFolder($this->getUserId())->getById($this->getFileId());
		if (empty($nodes)) {
			throw new NodeNotFoundException();
		}
		$node = reset($nodes);
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
