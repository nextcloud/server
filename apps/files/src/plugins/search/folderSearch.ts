/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Node } from '@nextcloud/files'
import { emit } from '@nextcloud/event-bus'
import { getFilePickerBuilder } from '@nextcloud/dialogs'
import { imagePath } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'
import logger from '../../logger'

/**
 * Initialize the unified search plugin.
 */
function init() {
	const OCA = window.OCA
	if (!OCA.UnifiedSearch) {
		return
	}

	logger.info('Initializing unified search plugin: folder search from files app')
	OCA.UnifiedSearch.registerFilterAction({
		id: 'in-folder',
		appId: 'files',
		searchFrom: 'files',
		label: t('files', 'In folder'),
		icon: imagePath('files', 'app.svg'),
		callback: (showFilePicker: boolean = true) => {
			if (showFilePicker) {
				const filepicker = getFilePickerBuilder('Pick plain text files')
					.addMimeTypeFilter('httpd/unix-directory')
					.allowDirectories(true)
					.addButton({
						label: 'Pick',
						callback: (nodes: Node[]) => {
							logger.info('Folder picked', { folder: nodes[0] })
							const folder = nodes[0]
							emit('nextcloud:unified-search:add-filter', {
								id: 'in-folder',
								appId: 'files',
								searchFrom: 'files',
								payload: folder,
								filterUpdateText: t('files', 'Search in folder: {folder}', { folder: folder.basename }),
								filterParams: { path: folder.path },
							})
						},
					})
					.build()
				filepicker.pick()
			} else {
				logger.debug('Folder search callback was handled without showing the file picker, it might already be open')
			}
		},
	})
}

document.addEventListener('DOMContentLoaded', init)
