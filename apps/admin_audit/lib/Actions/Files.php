<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\AdminAudit\Actions;

use OCP\Files\Events\Node\BeforeNodeReadEvent;
use OCP\Files\Events\Node\BeforeNodeWrittenEvent;
use OCP\Files\Events\Node\NodeCopiedEvent;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\Events\Node\NodeRenamedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Preview\BeforePreviewFetchedEvent;
use Psr\Log\LoggerInterface;

/**
 * Class Files logs the actions to files
 *
 * @package OCA\AdminAudit\Actions
 */
class Files extends Action {
	/**
	 * Logs file read actions
	 *
	 * @param BeforeNodeReadEvent $event
	 */
	public function read(BeforeNodeReadEvent $event): void {
		try {
			$params = [
				'id' => $event->getNode()->getId(),
				'path' => mb_substr($event->getNode()->getInternalPath(), 5),
			];
		} catch (InvalidPathException|NotFoundException $e) {
			\OCP\Server::get(LoggerInterface::class)->error(
				"Exception thrown in file read: ".$e->getMessage(), ['app' => 'admin_audit', 'exception' => $e]
			);
			return;
		}
		$this->log(
			'File with id "%s" accessed: "%s"',
			$params,
			array_keys($params)
		);
	}

	/**
	 * Logs rename actions of files
	 *
	 * @param NodeRenamedEvent $event
	 */
	public function rename(NodeRenamedEvent $event): void {
		try {
			$source = $event->getSource();
			$target = $event->getTarget();
			$params = [
				'newid' => $target->getId(),
				'oldpath' => mb_substr($source->getPath(), 5),
				'newpath' => mb_substr($target->getPath(), 5),
			];
		} catch (InvalidPathException|NotFoundException $e) {
			\OCP\Server::get(LoggerInterface::class)->error(
				"Exception thrown in file rename: ".$e->getMessage(), ['app' => 'admin_audit', 'exception' => $e]
			);
			return;
		}

		$this->log(
			'File renamed with id "%s" from "%s" to "%s"',
			$params,
			array_keys($params)
		);
	}

	/**
	 * Logs creation of files
	 *
	 * @param NodeCreatedEvent $event
	 */
	public function create(NodeCreatedEvent $event): void {
		try {
			$params = [
				'id' => $event->getNode()->getId(),
				'path' => mb_substr($event->getNode()->getInternalPath(), 5),
			];
		} catch (InvalidPathException|NotFoundException $e) {
			\OCP\Server::get(LoggerInterface::class)->error(
				"Exception thrown in file create: ".$e->getMessage(), ['app' => 'admin_audit', 'exception' => $e]
			);
			return;
		}
		if ($params['path'] === '/' || $params['path'] === '') {
			return;
		}
		$this->log(
			'File with id "%s" created: "%s"',
			$params,
			array_keys($params)
		);
	}

	/**
	 * Logs copying of files
	 *
	 * @param NodeCopiedEvent $event
	 */
	public function copy(NodeCopiedEvent $event): void {
		try {
			$params = [
				'oldid' => $event->getSource()->getId(),
				'newid' => $event->getTarget()->getId(),
				'oldpath' => mb_substr($event->getSource()->getInternalPath(), 5),
				'newpath' => mb_substr($event->getTarget()->getInternalPath(), 5),
			];
		} catch (InvalidPathException|NotFoundException $e) {
			\OCP\Server::get(LoggerInterface::class)->error(
				"Exception thrown in file copy: ".$e->getMessage(), ['app' => 'admin_audit', 'exception' => $e]
			);
			return;
		}
		$this->log(
			'File id copied from: "%s" to "%s", path from "%s" to "%s"',
			$params,
			array_keys($params)
		);
	}

	/**
	 * Logs writing of files
	 *
	 * @param BeforeNodeWrittenEvent $event
	 */
	public function write(BeforeNodeWrittenEvent $event): void {
		try {
			$params = [
				'id' => $event->getNode()->getId(),
				'path' => mb_substr($event->getNode()->getInternalPath(), 5),
			];
		} catch (InvalidPathException|NotFoundException $e) {
			\OCP\Server::get(LoggerInterface::class)->error(
				"Exception thrown in file write: ".$e->getMessage(), ['app' => 'admin_audit', 'exception' => $e]
			);
			return;
		}
		if ($params['path'] === '/' || $params['path'] === '') {
			return;
		}

		$this->log(
			'File with id "%s" written to: "%s"',
			$params,
			array_keys($params)
		);
	}

	/**
	 * Logs update of files
	 *
	 * @param NodeWrittenEvent $event
	 */
	public function update(NodeWrittenEvent $event): void {
		try {
			$params = [
				'id' => $event->getNode()->getId(),
				'path' => mb_substr($event->getNode()->getInternalPath(), 5),
			];
		} catch (InvalidPathException|NotFoundException $e) {
			\OCP\Server::get(LoggerInterface::class)->error(
				"Exception thrown in file update: ".$e->getMessage(), ['app' => 'admin_audit', 'exception' => $e]
			);
			return;
		}
		$this->log(
			'File with id "%s" updated: "%s"',
			$params,
			array_keys($params)
		);
	}

	/**
	 * Logs deletions of files
	 *
	 * @param NodeDeletedEvent $event
	 */
	public function delete(NodeDeletedEvent $event): void {
		try {
			$params = [
				'id' => $event->getNode()->getId(),
				'path' => mb_substr($event->getNode()->getInternalPath(), 5),
			];
		} catch (InvalidPathException|NotFoundException $e) {
			\OCP\Server::get(LoggerInterface::class)->error(
				"Exception thrown in file delete: ".$e->getMessage(), ['app' => 'admin_audit', 'exception' => $e]
			);
			return;
		}
		$this->log(
			'File with id "%s" deleted: "%s"',
			$params,
			array_keys($params)
		);
	}

	/**
	 * Logs preview access to a file
	 *
	 * @param BeforePreviewFetchedEvent $event
	 */
	public function preview(BeforePreviewFetchedEvent $event): void {
		try {
			$file = $event->getNode();
			$params = [
				'id' => $file->getId(),
				'width' => $event->getWidth(),
				'height' => $event->getHeight(),
				'crop' => $event->isCrop(),
				'mode' => $event->getMode(),
				'path' => mb_substr($file->getInternalPath(), 5)
			];
		} catch (InvalidPathException|NotFoundException $e) {
			\OCP\Server::get(LoggerInterface::class)->error(
				"Exception thrown in file preview: ".$e->getMessage(), ['app' => 'admin_audit', 'exception' => $e]
			);
			return;
		}
		$this->log(
			'Preview accessed: (id: "%s", width: "%s", height: "%s" crop: "%s", mode: "%s", path: "%s")',
			$params,
			array_keys($params)
		);
	}
}
