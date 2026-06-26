/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFileListFilterChip, INode } from '@nextcloud/files'

import { subscribe } from '@nextcloud/event-bus'
import { FileListFilter, registerFileListFilter } from '@nextcloud/files'
import { getPinia } from '../store/index.ts'
import { useSearchStore } from '../store/search.ts'

/**
 * Simple file list filter controlled by the Navigation search box
 */
class FilenameFilter extends FileListFilter {
	private searchQuery = ''

	constructor() {
		super('files:filename', 5)
		subscribe('files:search:updated', ({ query, scope }) => {
			if (scope === 'filter') {
				this.updateQuery(query)
			}
		})
	}

	public filter(nodes: INode[]): INode[] {
		const queryParts = this.searchQuery.toLocaleLowerCase().split(' ').filter(Boolean)
		return nodes.filter((node) => {
			const displayname = node.displayname.toLocaleLowerCase()
			return queryParts.every((part) => displayname.includes(part))
		})
	}

	public reset(): void {
		this.updateQuery('')
	}

	public updateQuery(query: string) {
		query = (query || '').trim()

		// Only if the query is different we update the filter to prevent re-computing all nodes
		if (query !== this.searchQuery) {
			this.searchQuery = query
			this.filterUpdated()

			const chips: IFileListFilterChip[] = []
			if (query !== '') {
				chips.push({
					text: query,
					onclick: () => {
						this.updateQuery('')
					},
				})
			} else {
				// make sure to also reset the search store when pressing the "X" on the filter chip
				const store = useSearchStore(getPinia())
				if (store.scope === 'filter') {
					store.query = ''
				}
			}
			this.updateChips(chips)
		}
	}
}

/**
 * Register the filename filter
 */
export function registerFilenameFilter() {
	registerFileListFilter(new FilenameFilter())
}
