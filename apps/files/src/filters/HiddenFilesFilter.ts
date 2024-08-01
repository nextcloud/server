/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode } from '@nextcloud/files'
import type { UserConfig } from '../types'
import { FileListFilter, registerFileListFilter } from '@nextcloud/files'
import { subscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'

class HiddenFilesFilter extends FileListFilter {

	private showHidden?: boolean

	constructor() {
		super('files:hidden', 0)
		this.showHidden = loadState<UserConfig>('files', 'config', { show_hidden: false }).show_hidden

		subscribe('files:config:updated', ({ key, value }) => {
			if (key === 'show_hidden') {
				this.showHidden = Boolean(value)
				this.filterUpdated()
			}
		})
	}

	public filter(nodes: INode[]): INode[] {
		if (this.showHidden) {
			return nodes
		}
		return nodes.filter((node) => (node.attributes.hidden !== true && !node.basename.startsWith('.')))
	}

}

/**
 * Register a file list filter to only show hidden files if enabled by user config
 */
export function registerHiddenFilesFilter() {
	registerFileListFilter(new HiddenFilesFilter())
}
