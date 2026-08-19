<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Listener;

use OCA\AdminAudit\Actions\Action;
use OCA\Files_Versions\Events\VersionRestoredEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Preview\BeforePreviewFetchedEvent;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<
 *     BeforePreviewFetchedEvent|
 *     VersionRestoredEvent|
 *     NodeRenamedEvent|
 *     NodeCreatedEvent|
 *     NodeCopiedEvent|
 *     NodeWrittenEvent|
 *     BeforeNodeReadEvent|
 *     BeforeNodeDeletedEvent
 * >
 */
class FileEventListener extends Action implements IEventListener {
	#[\Override]
	public function handle(Event $event): void {
		if ($event instanceof BeforePreviewFetchedEvent) {
			$this->beforePreviewFetched($event);
		} elseif ($event instanceof VersionRestoredEvent) {
			$this->versionRestored($event);
		} elseif ($event instanceof NodeRenamedEvent) {
			$this->nodeRenamed($event);
		} elseif ($event instanceof NodeCreatedEvent) {
			$this->nodeCreated($event);
		} elseif ($event instanceof NodeCopiedEvent) {
			$this->nodeCopied($event);
		} elseif ($event instanceof NodeWrittenEvent) {
			$this->nodeWritten($event);
		} elseif ($event instanceof BeforeNodeReadEvent) {
			$this->beforeNodeRead($event);
		} elseif ($event instanceof BeforeNodeDeletedEvent) {
			$this->beforeNodeDeleted($event);
		}
	}

	/**
	 * Logs preview access to a file
	 */
	private function beforePreviewFetched(BeforePreviewFetchedEvent $event): void {
		try {
			$node = $event->getNode();
			$params = [
				'id' => $node->getId(),
				'width' => $event->getWidth(),
				'height' => $event->getHeight(),
				'crop' => $event->isCrop(),
				'mode' => $event->getMode(),
				'path' => $node->getPath(),
			];
		} catch (InvalidPathException|NotFoundException $e) {
			Server::get(LoggerInterface::class)->error(
				'Exception thrown in file preview: ' . $e->getMessage(), ['app' => 'admin_audit', 'exception' => $e]
			);
			return;
		}
		
		$this->log(
			'Preview accessed: (id: "%s", width: "%s", height: "%s" crop: "%s", mode: "%s", path: "%s")',
			$params,
			array_keys($params)
		);
	}

	/**
	 * Logs when a version is restored
	 */
	private function versionRestored(VersionRestoredEvent $event): void {
		$version = $event->getVersion();
		$this->log('Version "%s" of "%s" was restored.',
			[
				'version' => $version->getRevisionId(),
				'path' => $version->getVersionPath()
			],
			['version', 'path']
		);
	}

	/**
	 * Logs rename actions of files
	 */
	private function nodeRenamed(NodeRenamedEvent $event): void {
		try {
			$target = $event->getTarget();
			$source = $event->getSource();
			$params = [
				'newid' => $target->getId(),
				'oldpath' => $source->getPath(),
				'newpath' => $target->getPath(),
			];
		} catch (InvalidPathException|NotFoundException $e) {
			Server::get(LoggerInterface::class)->error(
				'Exception thrown in file rename: ' . $e->getMessage(), ['app' => 'admin_audit', 'exception' => $e]
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
	 */
	private function nodeCreated(NodeCreatedEvent $event): void {
		try {
			$node = $event->getNode();
			$params = [
				'id' => $node->getId(),
				'path' => $node->getPath(),
			];
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
			'File with id "%s" created: "%s"',
			$params,
			array_keys($params)
		);
	}

	/**
	 * Logs copying of files
	 */
	private function nodeCopied(NodeCopiedEvent $event): void {
		try {
			$source = $event->getSource();
			$target = $event->getTarget();
			$params = [
				'oldid' => $source->getId(),
				'newid' => $target->getId(),
				'oldpath' => $source->getPath(),
				'newpath' => $target->getPath(),
			];
		} catch (InvalidPathException|NotFoundException $e) {
			Server::get(LoggerInterface::class)->error(
				'Exception thrown in file copy: ' . $e->getMessage(), ['app' => 'admin_audit', 'exception' => $e]
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
	 */
	private function nodeWritten(NodeWrittenEvent $event): void {
		try {
			$node = $event->getNode();
			$params = [
				'id' => $node->getId(),
				'path' => $node->getPath(),
			];
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
			'File with id "%s" written to: "%s"',
			$params,
			array_keys($params)
		);
	}

	/**
	 * Logs file read actions
	 */
	private function beforeNodeRead(BeforeNodeReadEvent $event): void {
		try {
			$node = $event->getNode();
			$params = [
				'id' => $node instanceof NonExistingFile ? 'not-yet-assigned' : $node->getId(),
				'path' => $node->getPath(),
			];
		} catch (InvalidPathException|NotFoundException $e) {
			Server::get(LoggerInterface::class)->error(
				'Exception thrown in file read: ' . $e->getMessage(), ['app' => 'admin_audit', 'exception' => $e]
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
	 * Logs deletions of files
	 */
	private function beforeNodeDeleted(BeforeNodeDeletedEvent $event): void {
		try {
			$node = $event->getNode();
			$params = [
				'id' => $node->getId(),
				'path' => $node->getPath(),
			];
		} catch (InvalidPathException|NotFoundException $e) {
			Server::get(LoggerInterface::class)->error(
				'Exception thrown in file delete: ' . $e->getMessage(), ['app' => 'admin_audit', 'exception' => $e]
			);
			return;
		}

		$this->log(
			'File with id "%s" deleted: "%s"',
			$params,
			array_keys($params)
		);
	}
}
