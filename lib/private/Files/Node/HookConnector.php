<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Node;

use OC\Files\Filesystem;
use OC\Files\View;
use OCP\EventDispatcher\GenericEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Exceptions\AbortedEventException;
use OCP\Files\Events\Node\BeforeNodeCopiedEvent;
use OCP\Files\Events\Node\BeforeNodeCreatedEvent;
use OCP\Files\Events\Node\BeforeNodeDeletedEvent;
use OCP\Files\Events\Node\BeforeNodeReadEvent;
use OCP\Files\Events\Node\BeforeNodeRenamedEvent;
use OCP\Files\Events\Node\BeforeNodeTouchedEvent;
use OCP\Files\Events\Node\BeforeNodeWrittenEvent;
use OCP\Files\Events\Node\NodeCopiedEvent;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\Events\Node\NodeRenamedEvent;
use OCP\Files\Events\Node\NodeTouchedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\FileInfo;
use OCP\Files\IRootFolder;
use OCP\Util;
use Psr\Log\LoggerInterface;

class HookConnector {
	/** @var FileInfo[] */
	private array $deleteMetaCache = [];

	public function __construct(
		private IRootFolder $root,
		private View $view,
		private IEventDispatcher $dispatcher,
		private LoggerInterface $logger,
	) {
	}

	public function viewToNode() {
		Util::connectHook('OC_Filesystem', 'write', $this, 'write');
		Util::connectHook('OC_Filesystem', 'post_write', $this, 'postWrite');

		Util::connectHook('OC_Filesystem', 'create', $this, 'create');
		Util::connectHook('OC_Filesystem', 'post_create', $this, 'postCreate');

		Util::connectHook('OC_Filesystem', 'delete', $this, 'delete');
		Util::connectHook('OC_Filesystem', 'post_delete', $this, 'postDelete');

		Util::connectHook('OC_Filesystem', 'rename', $this, 'rename');
		Util::connectHook('OC_Filesystem', 'post_rename', $this, 'postRename');

		Util::connectHook('OC_Filesystem', 'copy', $this, 'copy');
		Util::connectHook('OC_Filesystem', 'post_copy', $this, 'postCopy');

		Util::connectHook('OC_Filesystem', 'touch', $this, 'touch');
		Util::connectHook('OC_Filesystem', 'post_touch', $this, 'postTouch');

		Util::connectHook('OC_Filesystem', 'read', $this, 'read');
	}

	public function write($arguments) {
		$node = $this->getNodeForPath($arguments['path']);
		$this->root->emit('\OC\Files', 'preWrite', [$node]);
		$this->dispatcher->dispatch('\OCP\Files::preWrite', new GenericEvent($node));

		$event = new BeforeNodeWrittenEvent($node);
		$this->dispatcher->dispatchTyped($event);
	}

	public function postWrite($arguments) {
		$node = $this->getNodeForPath($arguments['path']);
		$this->root->emit('\OC\Files', 'postWrite', [$node]);
		$this->dispatcher->dispatch('\OCP\Files::postWrite', new GenericEvent($node));

		$event = new NodeWrittenEvent($node);
		$this->dispatcher->dispatchTyped($event);
	}

	public function create($arguments) {
		$node = $this->getNodeForPath($arguments['path']);
		$this->root->emit('\OC\Files', 'preCreate', [$node]);
		$this->dispatcher->dispatch('\OCP\Files::preCreate', new GenericEvent($node));

		$event = new BeforeNodeCreatedEvent($node);
		$this->dispatcher->dispatchTyped($event);
	}

	public function postCreate($arguments) {
		$node = $this->getNodeForPath($arguments['path']);
		$this->root->emit('\OC\Files', 'postCreate', [$node]);
		$this->dispatcher->dispatch('\OCP\Files::postCreate', new GenericEvent($node));

		$event = new NodeCreatedEvent($node);
		$this->dispatcher->dispatchTyped($event);
	}

	public function delete($arguments) {
		$node = $this->getNodeForPath($arguments['path']);
		$this->deleteMetaCache[$node->getPath()] = $node->getFileInfo();
		$this->root->emit('\OC\Files', 'preDelete', [$node]);
		$this->dispatcher->dispatch('\OCP\Files::preDelete', new GenericEvent($node));

		$event = new BeforeNodeDeletedEvent($node);
		try {
			$this->dispatcher->dispatchTyped($event);
		} catch (AbortedEventException $e) {
			$arguments['run'] = false;
			$this->logger->warning('delete process aborted', ['exception' => $e]);
		}
	}

	public function postDelete($arguments) {
		$node = $this->getNodeForPath($arguments['path']);
		unset($this->deleteMetaCache[$node->getPath()]);
		$this->root->emit('\OC\Files', 'postDelete', [$node]);
		$this->dispatcher->dispatch('\OCP\Files::postDelete', new GenericEvent($node));

		$event = new NodeDeletedEvent($node);
		$this->dispatcher->dispatchTyped($event);
	}

	public function touch($arguments) {
		$node = $this->getNodeForPath($arguments['path']);
		$this->root->emit('\OC\Files', 'preTouch', [$node]);
		$this->dispatcher->dispatch('\OCP\Files::preTouch', new GenericEvent($node));

		$event = new BeforeNodeTouchedEvent($node);
		$this->dispatcher->dispatchTyped($event);
	}

	public function postTouch($arguments) {
		$node = $this->getNodeForPath($arguments['path']);
		$this->root->emit('\OC\Files', 'postTouch', [$node]);
		$this->dispatcher->dispatch('\OCP\Files::postTouch', new GenericEvent($node));

		$event = new NodeTouchedEvent($node);
		$this->dispatcher->dispatchTyped($event);
	}

	public function rename($arguments) {
		$source = $this->getNodeForPath($arguments['oldpath']);
		$target = $this->getNodeForPath($arguments['newpath']);
		$this->root->emit('\OC\Files', 'preRename', [$source, $target]);
		$this->dispatcher->dispatch('\OCP\Files::preRename', new GenericEvent([$source, $target]));

		$event = new BeforeNodeRenamedEvent($source, $target);
		try {
			$this->dispatcher->dispatchTyped($event);
		} catch (AbortedEventException $e) {
			$arguments['run'] = false;
			$this->logger->warning('rename process aborted', ['exception' => $e]);
		}
	}

	public function postRename($arguments) {
		$source = $this->getNodeForPath($arguments['oldpath']);
		$target = $this->getNodeForPath($arguments['newpath']);
		$this->root->emit('\OC\Files', 'postRename', [$source, $target]);
		$this->dispatcher->dispatch('\OCP\Files::postRename', new GenericEvent([$source, $target]));

		$event = new NodeRenamedEvent($source, $target);
		$this->dispatcher->dispatchTyped($event);
	}

	public function copy($arguments) {
		$source = $this->getNodeForPath($arguments['oldpath']);
		$target = $this->getNodeForPath($arguments['newpath'], $source instanceof Folder);
		$this->root->emit('\OC\Files', 'preCopy', [$source, $target]);
		$this->dispatcher->dispatch('\OCP\Files::preCopy', new GenericEvent([$source, $target]));

		$event = new BeforeNodeCopiedEvent($source, $target);
		try {
			$this->dispatcher->dispatchTyped($event);
		} catch (AbortedEventException $e) {
			$arguments['run'] = false;
			$this->logger->warning('copy process aborted', ['exception' => $e]);
		}
	}

	public function postCopy($arguments) {
		$source = $this->getNodeForPath($arguments['oldpath']);
		$target = $this->getNodeForPath($arguments['newpath']);
		$this->root->emit('\OC\Files', 'postCopy', [$source, $target]);
		$this->dispatcher->dispatch('\OCP\Files::postCopy', new GenericEvent([$source, $target]));

		$event = new NodeCopiedEvent($source, $target);
		$this->dispatcher->dispatchTyped($event);
	}

	public function read($arguments) {
		$node = $this->getNodeForPath($arguments['path']);
		$this->root->emit('\OC\Files', 'read', [$node]);
		$this->dispatcher->dispatch('\OCP\Files::read', new GenericEvent([$node]));

		$event = new BeforeNodeReadEvent($node);
		$this->dispatcher->dispatchTyped($event);
	}

	private function getNodeForPath(string $path, bool $isDir = false): Node {
		$info = Filesystem::getView()->getFileInfo($path);
		if (!$info) {
			$fullPath = Filesystem::getView()->getAbsolutePath($path);
			if (isset($this->deleteMetaCache[$fullPath])) {
				$info = $this->deleteMetaCache[$fullPath];
			} else {
				$info = null;
			}
			if ($isDir || Filesystem::is_dir($path)) {
				return new NonExistingFolder($this->root, $this->view, $fullPath, $info);
			} else {
				return new NonExistingFile($this->root, $this->view, $fullPath, $info);
			}
		}
		if ($info->getType() === FileInfo::TYPE_FILE) {
			return new File($this->root, $this->view, $info->getPath(), $info);
		} else {
			return new Folder($this->root, $this->view, $info->getPath(), $info);
		}
	}
}
