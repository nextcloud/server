/**
 * Copyright (c) 2015
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

(function() {
	OCA.Versions = OCA.Versions || {}

	/**
	 * @namespace
	 */
	OCA.Versions.Util = {
		/**
		 * Initialize the versions plugin.
		 *
		 * @param {OCA.Files.FileList} fileList file list to be extended
		 */
		attach(fileList) {
			if (fileList.id === 'trashbin' || fileList.id === 'files.public') {
				return
			}

			fileList.registerTabView(new OCA.Versions.VersionsTabView('versionsTabView', { order: -10 }))
		},
	}
})()

OC.Plugins.register('OCA.Files.FileList', OCA.Versions.Util)
