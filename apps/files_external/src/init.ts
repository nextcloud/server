/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { View, getNavigation, Column, registerFileAction } from '@nextcloud/files'
import FolderNetworkSvg from '@mdi/svg/svg/folder-network.svg?raw'

import { action as enterCredentialsAction } from './actions/enterCredentialsAction'
import { action as inlineStorageCheckAction } from './actions/inlineStorageCheckAction'
import { action as openInFilesAction } from './actions/openInFilesAction'
import { getContents } from './services/externalStorage'

const allowUserMounting = loadState('files_external', 'allowUserMounting', false)

// Register view
const Navigation = getNavigation()
Navigation.register(new View({
	id: 'extstoragemounts',
	name: t('files_external', 'External storage'),
	caption: t('files_external', 'List of external storage.'),

	emptyCaption: allowUserMounting
		? t('files_external', 'There is no external storage configured. You can configure them in your Personal settings.')
		: t('files_external', 'There is no external storage configured and you don\'t have the permission to configure them.'),
	emptyTitle: t('files_external', 'No external storage'),

	icon: FolderNetworkSvg,
	order: 30,

	columns: [
		new Column({
			id: 'storage-type',
			title: t('files_external', 'Storage type'),
			render(node) {
				const backend = node.attributes?.backend || t('files_external', 'Unknown')
				const span = document.createElement('span')
				span.textContent = backend
				return span
			},
		}),
		new Column({
			id: 'scope',
			title: t('files_external', 'Scope'),
			render(node) {
				const span = document.createElement('span')
				let scope = t('files_external', 'Personal')
				if (node.attributes?.scope === 'system') {
					scope = t('files_external', 'System')
				}
				span.textContent = scope
				return span
			},
		}),
	],

	getContents,
}))

// Register actions
registerFileAction(enterCredentialsAction)
registerFileAction(inlineStorageCheckAction)
registerFileAction(openInFilesAction)
