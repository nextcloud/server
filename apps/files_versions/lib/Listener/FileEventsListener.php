<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Sam Tuke <mail@samtuke.com>
 * @author Louis Chmn <louis@chmn.me>
 *
 * @license AGPL-3.0
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
namespace OCA\Files_Versions\Listener;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OC\DB\Exceptions\DbalException;
use OC\Files\Filesystem;
use OC\Files\Mount\MoveableMount;
use OC\Files\Node\NonExistingFile;
use OC\Files\View;
use OCA\Files_Versions\Db\VersionEntity;
use OCA\Files_Versions\Db\VersionsMapper;
use OCA\Files_Versions\Storage;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\BeforeNodeCopiedEvent;
use OCP\Files\Events\Node\BeforeNodeDeletedEvent;
use OCP\Files\Events\Node\BeforeNodeRenamedEvent;
use OCP\Files\Events\Node\BeforeNodeTouchedEvent;
use OCP\Files\Events\Node\BeforeNodeWrittenEvent;
use OCP\Files\Events\Node\NodeCopiedEvent;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\Events\Node\NodeRenamedEvent;
use OCP\Files\Events\Node\NodeTouchedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\Folder;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\Files\Node;

class FileEventsListener implements IEventListener {
	private IRootFolder $rootFolder;
	private VersionsMapper $versionsMapper;
	/**
	 * @var array<int, array>
	 */
	private array $writeHookInfo = [];
	/**
	 * @var array<int, Node>
	 */
	private array $nodesTouched = [];
	/**
	 * @var array<string, Node>
	 */
	private array $versionsDeleted = [];
	private IMimeTypeLoader $mimeTypeLoader;

	public function __construct(
		IRootFolder $rootFolder,
		VersionsMapper $versionsMapper,
		IMimeTypeLoader $mimeTypeLoader
	) {
		$this->rootFolder = $rootFolder;
		$this->versionsMapper = $versionsMapper;
		$this->mimeTypeLoader = $mimeTypeLoader;
	}

	public function handle(Event $event): void {
		if ($event instanceof NodeCreatedEvent) {
			$this->created($event->getNode());
		}

		if ($event instanceof BeforeNodeTouchedEvent) {
			$this->pre_touch_hook($event->getNode());
		}

		if ($event instanceof NodeTouchedEvent) {
			$this->touch_hook($event->getNode());
		}

		if ($event instanceof BeforeNodeWrittenEvent) {
			$this->write_hook($event->getNode());
		}

		if ($event instanceof NodeWrittenEvent) {
			$this->post_write_hook($event->getNode());
		}

		if ($event instanceof BeforeNodeDeletedEvent) {
			$this->pre_remove_hook($event->getNode());
		}

		if ($event instanceof NodeDeletedEvent) {
			$this->remove_hook($event->getNode());
		}

		if ($event instanceof NodeRenamedEvent) {
			$this->rename_hook($event->getSource(), $event->getTarget());
		}

		if ($event instanceof NodeCopiedEvent) {
			$this->copy_hook($event->getSource(), $event->getTarget());
		}

		if ($event instanceof BeforeNodeRenamedEvent) {
			$this->pre_renameOrCopy_hook($event->getSource(), $event->getTarget());
		}

		if ($event instanceof BeforeNodeCopiedEvent) {
			$this->pre_renameOrCopy_hook($event->getSource(), $event->getTarget());
		}
	}

	public function pre_touch_hook(Node $node): void {
		// Do not handle folders.
		if ($node instanceof Folder) {
			return;
		}

		// $node is a non-existing on file creation.
		if ($node instanceof NonExistingFile) {
			return;
		}

		$this->nodesTouched[$node->getId()] = $node;
	}

	public function touch_hook(Node $node): void {
		$previousNode = $this->nodesTouched[$node->getId()] ?? null;

		if ($previousNode === null) {
			return;
		}

		unset($this->nodesTouched[$node->getId()]);

		try {
			// We update the timestamp of the version entity associated with the previousNode.
			$versionEntity = $this->versionsMapper->findVersionForFileId($previousNode->getId(), $previousNode->getMTime());
			// Create a version in the DB for the current content.
			$versionEntity->setTimestamp($node->getMTime());
			$this->versionsMapper->update($versionEntity);
		} catch (DbalException $ex) {
			// Ignore UniqueConstraintViolationException, as we are probably in the middle of a rollback
			// Where the previous node would temporary have the mtime of the old version, so the rollback touches it to fix it.
			if (!($ex->getPrevious() instanceof UniqueConstraintViolationException)) {
				throw $ex;
			}
		} catch (DoesNotExistException $ex) {
			// Ignore DoesNotExistException, as we are probably in the middle of a rollback
			// Where the previous node would temporary have a wrong mtime, so the rollback touches it to fix it.
		}
	}

	public function created(Node $node): void {
		// Do not handle folders.
		if ($node instanceof Folder) {
			return;
		}

		$versionEntity = new VersionEntity();
		$versionEntity->setFileId($node->getId());
		$versionEntity->setTimestamp($node->getMTime());
		$versionEntity->setSize($node->getSize());
		$versionEntity->setMimetype($this->mimeTypeLoader->getId($node->getMimetype()));
		$versionEntity->setMetadata([]);
		$this->versionsMapper->insert($versionEntity);
	}

	/**
	 * listen to write event.
	 */
	public function write_hook(Node $node): void {
		// Do not handle folders.
		if ($node instanceof Folder) {
			return;
		}

		// $node is a non-existing on file creation.
		if ($node instanceof NonExistingFile) {
			return;
		}

		$path = $this->getPathForNode($node);
		$result = Storage::store($path);

		// Store the result of the version creation so it can be used in post_write_hook.
		$this->writeHookInfo[$node->getId()] = [
			'previousNode' => $node,
			'versionCreated' => $result !== false
		];
	}

	/**
	 * listen to post_write event.
	 */
	public function post_write_hook(Node $node): void {
		// Do not handle folders.
		if ($node instanceof Folder) {
			return;
		}

		$writeHookInfo = $this->writeHookInfo[$node->getId()] ?? null;

		if ($writeHookInfo === null) {
			return;
		}

		if ($writeHookInfo['versionCreated'] && $node->getMTime() !== $writeHookInfo['previousNode']->getMTime()) {
			// If a new version was created, insert a version in the DB for the current content.
			// Unless both versions have the same mtime.
			$versionEntity = new VersionEntity();
			$versionEntity->setFileId($node->getId());
			$versionEntity->setTimestamp($node->getMTime());
			$versionEntity->setSize($node->getSize());
			$versionEntity->setMimetype($this->mimeTypeLoader->getId($node->getMimetype()));
			$versionEntity->setMetadata([]);
			$this->versionsMapper->insert($versionEntity);
		} else {
			// If no new version was stored in the FS, no new version should be added in the DB.
			// So we simply update the associated version.
			$currentVersionEntity = $this->versionsMapper->findVersionForFileId($node->getId(), $writeHookInfo['previousNode']->getMtime());
			$currentVersionEntity->setTimestamp($node->getMTime());
			$currentVersionEntity->setSize($node->getSize());
			$currentVersionEntity->setMimetype($this->mimeTypeLoader->getId($node->getMimetype()));
			$this->versionsMapper->update($currentVersionEntity);
		}

		unset($this->writeHookInfo[$node->getId()]);
	}

	/**
	 * Erase versions of deleted file
	 *
	 * This function is connected to the delete signal of OC_Filesystem
	 * cleanup the versions directory if the actual file gets deleted
	 */
	public function remove_hook(Node $node): void {
		// Need to normalize the path as there is an issue with path concatenation in View.php::getAbsolutePath.
		$path = Filesystem::normalizePath($node->getPath());
		if (!array_key_exists($path, $this->versionsDeleted)) {
			return;
		}
		$node = $this->versionsDeleted[$path];
		$relativePath = $this->getPathForNode($node);
		unset($this->versionsDeleted[$path]);
		Storage::delete($relativePath);
		$this->versionsMapper->deleteAllVersionsForFileId($node->getId());
	}

	/**
	 * mark file as "deleted" so that we can clean up the versions if the file is gone
	 */
	public function pre_remove_hook(Node $node): void {
		$path = $this->getPathForNode($node);
		Storage::markDeletedFile($path);
		$this->versionsDeleted[$node->getPath()] = $node;
	}

	/**
	 * rename/move versions of renamed/moved files
	 *
	 * This function is connected to the rename signal of OC_Filesystem and adjust the name and location
	 * of the stored versions along the actual file
	 */
	public function rename_hook(Node $source, Node $target): void {
		$oldPath = $this->getPathForNode($source);
		$newPath = $this->getPathForNode($target);
		Storage::renameOrCopy($oldPath, $newPath, 'rename');
	}

	/**
	 * copy versions of copied files
	 *
	 * This function is connected to the copy signal of OC_Filesystem and copies the
	 * the stored versions to the new location
	 */
	public function copy_hook(Node $source, Node $target): void {
		$oldPath = $this->getPathForNode($source);
		$newPath = $this->getPathForNode($target);
		Storage::renameOrCopy($oldPath, $newPath, 'copy');
	}

	/**
	 * Remember owner and the owner path of the source file.
	 * If the file already exists, then it was a upload of a existing file
	 * over the web interface and we call Storage::store() directly
	 *
	 *
	 */
	public function pre_renameOrCopy_hook(Node $source, Node $target): void {
		// if we rename a movable mount point, then the versions don't have
		// to be renamed
		$oldPath = $this->getPathForNode($source);
		$newPath = $this->getPathForNode($target);
		$absOldPath = Filesystem::normalizePath('/' . \OC_User::getUser() . '/files' . $oldPath);
		$manager = Filesystem::getMountManager();
		$mount = $manager->find($absOldPath);
		$internalPath = $mount->getInternalPath($absOldPath);
		if ($internalPath === '' and $mount instanceof MoveableMount) {
			return;
		}

		$view = new View(\OC_User::getUser() . '/files');
		if ($view->file_exists($newPath)) {
			Storage::store($newPath);
		} else {
			Storage::setSourcePathAndUser($oldPath);
		}
	}

	/**
	 * Retrieve the path relative to the current user root folder.
	 * If no user is connected, use the node's owner.
	 */
	private function getPathForNode(Node $node): ?string {
		try {
			return $this->rootFolder
				->getUserFolder(\OC_User::getUser())
				->getRelativePath($node->getPath());
		} catch (\Throwable $ex) {
			return $this->rootFolder
				->getUserFolder($node->getOwner()->getUid())
				->getRelativePath($node->getPath());
		}
	}
}
