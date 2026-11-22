<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Actions;

use OC\Files\Node\NonExistingFile;
use OCA\AdminAudit\Operation;
use OCP\Files\Events\Node\BeforeNodeDeletedEvent;
use OCP\Files\Events\Node\BeforeNodeReadEvent;
use OCP\Files\Events\Node\NodeCopiedEvent;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\Events\Node\NodeRenamedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * Class Files logs the actions to files
 *
 * @package OCA\AdminAudit\Actions
 */
class Files extends Action {
	/**
	 * Logs file read actions
	 */
	public function read(BeforeNodeReadEvent $event): void {
		try {
			$node = $event->getNode();
			$params = [
				'id' => $node instanceof NonExistingFile ? 'not-yet-assigned' : $node->getId(),
				'path' => $node->getPath(),
			] + $this->getRealPathParam('realPath', $node)
				+ $this->getOwnerPathParam('ownerPath', $node);
		} catch (InvalidPathException|NotFoundException $e) {
			Server::get(LoggerInterface::class)->error(
				'Exception thrown in file read: ' . $e->getMessage(), ['app' => 'admin_audit', 'exception' => $e]
			);
			return;
		}
		$this->log(
			Operation::FileRead,
			$this->appendRealPathText('File with id "%s" accessed: "%s"', $params),
			$params,
			array_keys($params)
		);
	}

	/**
	 * Logs rename actions of files
	 */
	public function afterRename(NodeRenamedEvent $event): void {
		try {
			$target = $event->getTarget();
			$source = $event->getSource();
			$params = [
				'newid' => $target->getId(),
				'oldpath' => $source->getPath(),
				'newpath' => $target->getPath(),
			] + $this->getRealPathParam('oldRealPath', $source)
				+ $this->getRealPathParam('newRealPath', $target)
				+ $this->getOwnerPathParam('oldOwnerPath', $source)
				+ $this->getOwnerPathParam('newOwnerPath', $target);
		} catch (InvalidPathException|NotFoundException $e) {
			Server::get(LoggerInterface::class)->error(
				'Exception thrown in file rename: ' . $e->getMessage(), ['app' => 'admin_audit', 'exception' => $e]
			);
			return;
		}

		$this->log(
			Operation::FileRenamed,
			$this->appendRealPathText('File renamed with id "%s" from "%s" to "%s"', $params),
			$params,
			array_keys($params)
		);
	}

	/**
	 * Logs creation of files
	 */
	public function create(NodeCreatedEvent $event): void {
		try {
			$node = $event->getNode();
			$params = [
				'id' => $node->getId(),
				'path' => $node->getPath(),
			] + $this->getRealPathParam('realPath', $node)
				+ $this->getOwnerPathParam('ownerPath', $node);
		} catch (InvalidPathException|NotFoundException $e) {
			Server::get(LoggerInterface::class)->error(
				'Exception thrown in file create: ' . $e->getMessage(), ['app' => 'admin_audit', 'exception' => $e]
			);
			return;
		}
		if ($params['path'] === '/' || $params['path'] === '') {
			return;
		}
		$this->log(
			Operation::FileCreated,
			$this->appendRealPathText('File with id "%s" created: "%s"', $params),
			$params,
			array_keys($params)
		);
	}

	/**
	 * Logs copying of files
	 */
	public function copy(NodeCopiedEvent $event): void {
		try {
			$source = $event->getSource();
			$target = $event->getTarget();
			$params = [
				'oldid' => $source->getId(),
				'newid' => $target->getId(),
				'oldpath' => $source->getPath(),
				'newpath' => $target->getPath(),
			] + $this->getRealPathParam('oldRealPath', $source)
				+ $this->getRealPathParam('newRealPath', $target)
				+ $this->getOwnerPathParam('oldOwnerPath', $source)
				+ $this->getOwnerPathParam('newOwnerPath', $target);
		} catch (InvalidPathException|NotFoundException $e) {
			Server::get(LoggerInterface::class)->error(
				'Exception thrown in file copy: ' . $e->getMessage(), ['app' => 'admin_audit', 'exception' => $e]
			);
			return;
		}
		$this->log(
			Operation::FileCopied,
			$this->appendRealPathText('File id copied from: "%s" to "%s", path from "%s" to "%s"', $params),
			$params,
			array_keys($params)
		);
	}

	/**
	 * Logs writing of files
	 */
	public function write(NodeWrittenEvent $event): void {
		try {
			$node = $event->getNode();
			$params = [
				'id' => $node->getId(),
				'path' => $node->getPath(),
			] + $this->getRealPathParam('realPath', $node)
				+ $this->getOwnerPathParam('ownerPath', $node);
		} catch (InvalidPathException|NotFoundException $e) {
			Server::get(LoggerInterface::class)->error(
				'Exception thrown in file write: ' . $e->getMessage(), ['app' => 'admin_audit', 'exception' => $e]
			);
			return;
		}
		if ($params['path'] === '/' || $params['path'] === '') {
			return;
		}

		$this->log(
			Operation::FileWritten,
			$this->appendRealPathText('File with id "%s" written to: "%s"', $params),
			$params,
			array_keys($params)
		);
	}

	/**
	 * Logs deletions of files
	 */
	public function delete(BeforeNodeDeletedEvent $event): void {
		try {
			$node = $event->getNode();
			$params = [
				'id' => $node->getId(),
				'path' => $node->getPath(),
			] + $this->getRealPathParam('realPath', $node)
				+ $this->getOwnerPathParam('ownerPath', $node);
		} catch (InvalidPathException|NotFoundException $e) {
			Server::get(LoggerInterface::class)->error(
				'Exception thrown in file delete: ' . $e->getMessage(), ['app' => 'admin_audit', 'exception' => $e]
			);
			return;
		}
		$this->log(
			Operation::FileDeleted,
			$this->appendRealPathText('File with id "%s" deleted: "%s"', $params),
			$params,
			array_keys($params)
		);
	}
}
