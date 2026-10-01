/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFileListFilterChip, IFileListFilterWithUi, INode } from '@nextcloud/files'

import svgCalendarRangeOutline from '@mdi/svg/svg/calendar-range-outline.svg?raw'
import { FileListFilter, registerFileListFilter } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { defineCustomElement } from 'vue'
import FileListFilterModified from '../components/FileListFilter/FileListFilterModified.vue'

export interface ITimePreset {
	id: string
	label: string
	filter: (time: number) => boolean
}

const tagName = 'files-file-list-filter-modified'

class ModifiedFilter extends FileListFilter implements IFileListFilterWithUi {
	private currentInstance?: InstanceType<typeof FileListFilterModified>
	private currentPreset?: ITimePreset

	public readonly displayName = t('files', 'Modified')
	public readonly iconSvgInline = svgCalendarRangeOutline
	public readonly tagName = tagName

	constructor() {
		super('files:modified', 50)
	}

	public filter(nodes: INode[]): INode[] {
		if (!this.currentPreset) {
			return nodes
		}

		return nodes.filter((node) => node.mtime === undefined || this.currentPreset!.filter(node.mtime.getTime()))
	}

	public reset(): void {
		this.dispatchEvent(new CustomEvent('reset'))
	}

	public get preset() {
		return this.currentPreset
	}

	public setPreset(preset?: ITimePreset) {
		this.currentPreset = preset
		this.filterUpdated()

		const chips: IFileListFilterChip[] = []
		if (preset) {
			chips.push({
				icon: svgCalendarRangeOutline,
				text: preset.label,
				onclick: () => this.reset(),
			})
		} else {
			(this.currentInstance as { resetFilter: () => void } | undefined)?.resetFilter()
		}
		this.updateChips(chips)
	}
}

export type { ModifiedFilter }

/**
 * Register the file list filter by modification date
 */
export function registerModifiedFilter() {
	const WrappedComponent = defineCustomElement(FileListFilterModified, { shadowRoot: false })
	customElements.define(tagName, WrappedComponent)
	registerFileListFilter(new ModifiedFilter())
}
