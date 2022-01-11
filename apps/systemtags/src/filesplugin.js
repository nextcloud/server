/**
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
	OCA.SystemTags = _.extend({}, OCA.SystemTags)
	if (!OCA.SystemTags) {
		/**
		 * @namespace
		 */
		OCA.SystemTags = {}
	}

	/**
	 * @namespace
	 */
	OCA.SystemTags.FilesPlugin = {
		ignoreLists: [
			'trashbin',
			'files.public',
		],

		attach(fileList) {
			if (this.ignoreLists.indexOf(fileList.id) >= 0) {
				return
			}

			// only create and attach once
			// FIXME: this should likely be done on a different code path now
			// for the sidebar to only have it registered once
			if (!OCA.SystemTags.View) {
				const systemTagsInfoView = new OCA.SystemTags.SystemTagsInfoView()
				fileList.registerDetailView(systemTagsInfoView)
				OCA.SystemTags.View = systemTagsInfoView
			}
		},
	}

})()

OC.Plugins.register('OCA.Files.FileList', OCA.SystemTags.FilesPlugin)
