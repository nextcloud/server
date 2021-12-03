/**
 * @copyright Copyright (c) 2016 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(OC.requestToken)

window.OCP.Collaboration.registerType('file', {
	action: () => {
		return new Promise((resolve, reject) => {
			OC.dialogs.filepicker(t('files_sharing', 'Link to a file'), function(f) {
				const client = OC.Files.getClient()
				client.getFileInfo(f).then((status, fileInfo) => {
					resolve(fileInfo.id)
				}).fail(() => {
					reject(new Error('Cannot get fileinfo'))
				})
			}, false, null, false, OC.dialogs.FILEPICKER_TYPE_CHOOSE, '', { allowDirectoryChooser: true })
		})
	},
	typeString: t('files_sharing', 'Link to a file'),
	typeIconClass: 'icon-files-dark',
})
