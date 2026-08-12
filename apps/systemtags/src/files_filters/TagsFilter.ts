/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFileListFilterChip, IFileListFilterWithUi, INode } from '@nextcloud/files'
import type { TagWithId } from '../types.ts'

import svgTagOutline from '@mdi/svg/svg/tag-outline.svg?raw'
import { FileListFilter, registerFileListFilter } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { defineCustomElement } from 'vue'
import FileListFilterTagsCE from '../components/FileListFilterTags.vue'
import { getNodeSystemTags } from '../utils.ts'

const tagName = 'systemtags-file-list-filter-tags'

class TagsFilter extends FileListFilter implements IFileListFilterWithUi {
	#selectedTags: TagWithId[] = []

	public readonly displayName = t('systemtags', 'Tags')
	public readonly iconSvgInline = svgTagOutline
	public readonly tagName = tagName

	constructor() {
		super('systemtags:tags', 75)
	}

	public filter(nodes: INode[]): INode[] {
		if (this.#selectedTags.length === 0) {
			return nodes
		}

		const selectedNames = this.#selectedTags.map((tag) => tag.displayName)
		return nodes.filter((node) => {
			const nodeTags = getNodeSystemTags(node)
			return selectedNames.some((name) => nodeTags.includes(name))
		})
	}

	public reset(): void {
		this.dispatchEvent(new CustomEvent('reset'))
	}

	public get selectedTags(): TagWithId[] {
		return this.#selectedTags
	}

	public setTags(tags?: TagWithId[]): void {
		this.#selectedTags = tags ?? []
		this.filterUpdated()

		const chips: IFileListFilterChip[] = this.#selectedTags.map((tag) => ({
			icon: svgTagOutline,
			text: tag.displayName,
			onclick: () => {
				this.dispatchEvent(new CustomEvent('deselect', { detail: tag.id }))
				this.setTags(this.#selectedTags.filter((t) => t.id !== tag.id))
			},
		}))
		this.updateChips(chips)
	}
}

export type { TagsFilter }

/**
 * Register the file list filter by system tags
 */
export function registerTagsFilter() {
	const TagsFilterElement = defineCustomElement(FileListFilterTagsCE, { shadowRoot: false })
	customElements.define(tagName, TagsFilterElement)
	registerFileListFilter(new TagsFilter())
}
