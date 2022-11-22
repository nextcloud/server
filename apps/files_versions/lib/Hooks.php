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
namespace OCA\Files_Versions;

use OC\Files\Filesystem;
use OC\Files\Mount\MoveableMount;
use OC\Files\View;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\BeforeNodeCopiedEvent;
use OCP\Files\Events\Node\BeforeNodeDeletedEvent;
use OCP\Files\Events\Node\BeforeNodeRenamedEvent;
use OCP\Files\Events\Node\BeforeNodeWrittenEvent;
use OCP\Files\Events\Node\NodeCopiedEvent;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\Events\Node\NodeRenamedEvent;
use OCP\Files\Folder;
use OCP\Files\Node;

class Hooks implements IEventListener {
	public Folder $userFolder;

	public function __construct(
		Folder $userFolder
	) {
		$this->userFolder = $userFolder;
	}

	public function handle(Event $event): void {
		if ($event instanceof BeforeNodeWrittenEvent) {
			$this->write_hook($event->getNode());
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

	/**
	 * listen to write event.
	 */
	public function write_hook(Node $node): void {
		$path = $this->userFolder->getRelativePath($node->getPath());
		Storage::store($path);
	}


	/**
	 * Erase versions of deleted file
	 *
	 * This function is connected to the delete signal of OC_Filesystem
	 * cleanup the versions directory if the actual file gets deleted
	 */
	public function remove_hook(Node $node): void {
		$path = $this->userFolder->getRelativePath($node->getPath());
		Storage::delete($path);
	}

	/**
	 * mark file as "deleted" so that we can clean up the versions if the file is gone
	 */
	public function pre_remove_hook(Node $node): void {
		$path = $this->userFolder->getRelativePath($node->getPath());
		Storage::markDeletedFile($path);
	}

	/**
	 * rename/move versions of renamed/moved files
	 *
	 * This function is connected to the rename signal of OC_Filesystem and adjust the name and location
	 * of the stored versions along the actual file
	 */
	public function rename_hook(Node $source, Node $target): void {
		$oldPath = $this->userFolder->getRelativePath($source->getPath());
		$newPath = $this->userFolder->getRelativePath($target->getPath());
		Storage::renameOrCopy($oldPath, $newPath, 'rename');
	}

	/**
	 * copy versions of copied files
	 *
	 * This function is connected to the copy signal of OC_Filesystem and copies the
	 * the stored versions to the new location
	 */
	public function copy_hook(Node $source, Node $target): void {
		$oldPath = $this->userFolder->getRelativePath($source->getPath());
		$newPath = $this->userFolder->getRelativePath($target->getPath());
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
		$oldPath = $this->userFolder->getRelativePath($source->getPath());
		$newPath = $this->userFolder->getRelativePath($target->getPath());
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
}
