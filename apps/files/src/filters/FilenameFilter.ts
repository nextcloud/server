/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFileListFilterChip, INode } from '@nextcloud/files'
import { FileListFilter } from '@nextcloud/files'

/**
 * Simple file list filter controlled by the Navigation search box
 */
export class FilenameFilter extends FileListFilter {

	private searchQuery = ''

	constructor() {
		super('files:filename', 5)
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
			}
			this.updateChips(chips)
			// Emit the new query as it might have come not from the Navigation
			this.dispatchTypedEvent('update:query', new CustomEvent('update:query', { detail: query }))
		}
	}

}
