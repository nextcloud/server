<?php

declare(strict_types=1);
/**
 * @copyright Copyright 2022 Carl Schwan <carl@carlschwan.eu>
 * @license AGPL-3.0-or-later
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Metadata;

use OC\Files\Filesystem;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\Events\NodeRemovedFromCache;
use OCP\Files\File;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\FileInfo;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<NodeRemovedFromCache>
 * @template-implements IEventListener<NodeDeletedEvent>
 * @template-implements IEventListener<NodeWrittenEvent>
 */
class FileEventListener implements IEventListener {
	private IMetadataManager $manager;
	private LoggerInterface $logger;

	public function __construct(IMetadataManager $manager, LoggerInterface $logger) {
		$this->manager = $manager;
		$this->logger = $logger;
	}

	private function shouldExtractMetadata(Node $node): bool {
		try {
			if ($node->getMimetype() === 'httpd/unix-directory') {
				return false;
			}
		} catch (NotFoundException $e) {
			return false;
		}
		if ($node->getSize(false) <= 0) {
			return false;
		}

		$path = $node->getPath();
		return $this->isCorrectPath($path);
	}

	private function isCorrectPath(string $path): bool {
		// TODO make this more dynamic, we have the same issue in other places
		return !str_starts_with($path, 'appdata_') && !str_starts_with($path, 'files_versions/') && !str_starts_with($path, 'files_trashbin/');
	}

	public function handle(Event $event): void {
		if ($event instanceof NodeRemovedFromCache) {
			if (!$this->isCorrectPath($event->getPath())) {
				// Don't listen to paths for which we don't extract metadata
				return;
			}
			$view = Filesystem::getView();
			if (!$view) {
				// Should not happen since a scan in the user folder should setup
				// the file system.
				$e = new \Exception(); // don't trigger, just get backtrace
				$this->logger->error('Detecting deletion of a file with possible metadata but file system setup is not setup', [
					'exception' => $e,
					'app' => 'metadata'
				]);
				return;
			}
			$info = $view->getFileInfo($event->getPath());
			if ($info && $info->getType() === FileInfo::TYPE_FILE) {
				$this->manager->clearMetadata($info->getId());
			}
		}

		if ($event instanceof NodeDeletedEvent) {
			$node = $event->getNode();
			if ($this->shouldExtractMetadata($node)) {
				/** @var File $node */
				$this->manager->clearMetadata($event->getNode()->getId());
			}
		}

		if ($event instanceof NodeWrittenEvent) {
			$node = $event->getNode();
			if ($this->shouldExtractMetadata($node)) {
				/** @var File $node */
				$this->manager->generateMetadata($event->getNode(), false);
			}
		}
	}
}
