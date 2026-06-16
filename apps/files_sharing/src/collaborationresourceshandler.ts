/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCSPNonce } from '@nextcloud/auth'
import { FilePickerType, getFilePickerBuilder } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

__webpack_nonce__ = getCSPNonce()

window.OCP.Collaboration.registerType('file', {
	typeString: t('files_sharing', 'Link to a file'),
	typeIconClass: 'icon-files-dark',
	async action() {
		const filePicker = getFilePickerBuilder(t('files_sharing', 'Link to a file'))
			.setType(FilePickerType.Choose)
			.allowDirectories(true)
			.build()

		try {
			const [node] = await filePicker.pickNodes()
			return node!.fileid
		} catch {
			throw new Error('Cannot get fileinfo')
		}
	},
})
