/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { IFileListFilterChip, INode } from '@nextcloud/files'

import { FileListFilter, registerFileListFilter } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import Vue from 'vue'
import FileListFilterModified from '../components/FileListFilter/FileListFilterModified.vue'

import calendarSvg from '@mdi/svg/svg/calendar.svg?raw'

export interface ITimePreset {
	id: string,
	label: string,
	filter: (time: number) => boolean
}

const startOfToday = () => (new Date()).setHours(0, 0, 0, 0)

/**
 * Available presets
 */
const timePresets: ITimePreset[] = [
	{
		id: 'today',
		label: t('files', 'Today'),
		filter: (time: number) => time > startOfToday(),
	},
	{
		id: 'last-7',
		label: t('files', 'Last 7 days'),
		filter: (time: number) => time > (startOfToday() - (7 * 24 * 60 * 60 * 1000)),
	},
	{
		id: 'last-30',
		label: t('files', 'Last 30 days'),
		filter: (time: number) => time > (startOfToday() - (30 * 24 * 60 * 60 * 1000)),
	},
	{
		id: 'this-year',
		label: t('files', 'This year ({year})', { year: (new Date()).getFullYear() }),
		filter: (time: number) => time > (new Date(startOfToday())).setMonth(0, 1),
	},
	{
		id: 'last-year',
		label: t('files', 'Last year ({year})', { year: (new Date()).getFullYear() - 1 }),
		filter: (time: number) => (time > (new Date(startOfToday())).setFullYear((new Date()).getFullYear() - 1, 0, 1)) && (time < (new Date(startOfToday())).setMonth(0, 1)),
	},
] as const

class ModifiedFilter extends FileListFilter {

	private currentInstance?: Vue
	private currentPreset?: ITimePreset

	constructor() {
		super('files:modified', 50)
	}

	public mount(el: HTMLElement) {
		if (this.currentInstance) {
			this.currentInstance.$destroy()
		}

		const View = Vue.extend(FileListFilterModified as never)
		this.currentInstance = new View({
			propsData: {
				timePresets,
			},
			el,
		})
			.$on('update:preset', this.setPreset.bind(this))
			.$mount()
	}

	public filter(nodes: INode[]): INode[] {
		if (!this.currentPreset) {
			return nodes
		}

		return nodes.filter((node) => node.mtime === undefined || this.currentPreset!.filter(node.mtime.getTime()))
	}

	public reset(): void {
		this.setPreset()
	}

	public setPreset(preset?: ITimePreset) {
		this.currentPreset = preset
		this.filterUpdated()

		const chips: IFileListFilterChip[] = []
		if (preset) {
			chips.push({
				icon: calendarSvg,
				text: preset.label,
				onclick: () => this.setPreset(),
			})
		} else {
			(this.currentInstance as { resetFilter: () => void } | undefined)?.resetFilter()
		}
		this.updateChips(chips)
	}

}

/**
 * Register the file list filter by modification date
 */
export function registerModifiedFilter() {
	registerFileListFilter(new ModifiedFilter())
}
