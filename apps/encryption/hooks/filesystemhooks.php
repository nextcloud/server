<?php
/**
 * @author Clark Tomlinson  <clark@owncloud.com>
 * @since 2/19/15, 10:02 AM
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Encryption\Hooks;


use OCA\Encryption\Hooks\Contracts\IHook;
use OCP\Util;

class FileSystemHooks implements IHook {

	/**
	 * Connects Hooks
	 *
	 * @return null
	 */
	public function addHooks() {
		Util::connectHook('OC_Filesystem', 'rename', 'OCA\Encryption\Hooks', 'preRename');
		Util::connectHook('OC_Filesystem', 'post_rename', 'OCA\Encryption\Hooks', 'postRenameOrCopy');
		Util::connectHook('OC_Filesystem', 'copy', 'OCA\Encryption\Hooks', 'preCopy');
		Util::connectHook('OC_Filesystem', 'post_copy', 'OCA\Encryption\Hooks', 'postRenameOrCopy');
		Util::connectHook('OC_Filesystem', 'post_delete', 'OCA\Encryption\Hooks', 'postDelete');
		Util::connectHook('OC_Filesystem', 'delete', 'OCA\Encryption\Hooks', 'preDelete');
		Util::connectHook('\OC\Core\LostPassword\Controller\LostController', 'post_passwordReset', 'OCA\Encryption\Hooks', 'postPasswordReset');
		Util::connectHook('OC_Filesystem', 'post_umount', 'OCA\Encryption\Hooks', 'postUnmount');
		Util::connectHook('OC_Filesystem', 'umount', 'OCA\Encryption\Hooks', 'preUnmount');
	}
}
