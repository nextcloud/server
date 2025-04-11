/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getCSPNonce } from '@nextcloud/auth'

// eslint-disable-next-line camelcase
__webpack_nonce__ = getCSPNonce()

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
