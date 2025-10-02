/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode } from '@nextcloud/files'
import type { ComponentPublicInstance } from 'vue'

import { subscribe } from '@nextcloud/event-bus'
import { FileListFilter, registerFileListFilter } from '@nextcloud/files'
import Vue from 'vue'
import FileListFilterToSearch from '../components/FileListFilter/FileListFilterToSearch.vue'

class SearchFilter extends FileListFilter {
	private currentInstance?: ComponentPublicInstance<typeof FileListFilterToSearch>

	constructor() {
		super('files:filter-to-search', 999)
		subscribe('files:search:updated', ({ query, scope }) => {
			if (query && scope === 'filter') {
				this.currentInstance?.showButton()
			} else {
				this.currentInstance?.hideButton()
			}
		})
	}

	public mount(el: HTMLElement) {
		if (this.currentInstance) {
			this.currentInstance.$destroy()
		}

		const View = Vue.extend(FileListFilterToSearch)
		this.currentInstance = new View().$mount(el) as unknown as ComponentPublicInstance<typeof FileListFilterToSearch>
	}

	public filter(nodes: INode[]): INode[] {
		return nodes
	}
}

/**
 * Register a file list filter to only show hidden files if enabled by user config
 */
export function registerFilterToSearchToggle() {
	registerFileListFilter(new SearchFilter())
}
