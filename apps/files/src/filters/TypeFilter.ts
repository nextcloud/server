/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { IFileListFilterChip, INode } from '@nextcloud/files'

import { FileListFilter, registerFileListFilter } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import Vue from 'vue'
import FileListFilterType from '../components/FileListFilter/FileListFilterType.vue'

// TODO: Create a modern replacement for OC.MimeType...
import svgDocument from '@mdi/svg/svg/file-document.svg?raw'
import svgSpreadsheet from '@mdi/svg/svg/file-table-box.svg?raw'
import svgPresentation from '@mdi/svg/svg/file-presentation-box.svg?raw'
import svgPDF from '@mdi/svg/svg/file-pdf-box.svg?raw'
import svgFolder from '@mdi/svg/svg/folder.svg?raw'
import svgAudio from '@mdi/svg/svg/music.svg?raw'
import svgImage from '@mdi/svg/svg/image.svg?raw'
import svgMovie from '@mdi/svg/svg/movie.svg?raw'

export interface ITypePreset {
	id: string
	label: string
	icon: string
	mime: string[]
}

const colorize = (svg: string, color: string) => {
	return svg.replace('<path ', `<path fill="${color}" `)
}

/**
 * Available presets
 */
const getTypePresets = async () => [
	{
		id: 'document',
		label: t('files', 'Documents'),
		icon: colorize(svgDocument, '#49abea'),
		mime: ['x-office/document'],
	},
	{
		id: 'spreadsheet',
		label: t('files', 'Spreadsheets'),
		icon: colorize(svgSpreadsheet, '#9abd4e'),
		mime: ['x-office/spreadsheet'],
	},
	{
		id: 'presentation',
		label: t('files', 'Presentations'),
		icon: colorize(svgPresentation, '#f0965f'),
		mime: ['x-office/presentation'],
	},
	{
		id: 'pdf',
		label: t('files', 'PDFs'),
		icon: colorize(svgPDF, '#dc5047'),
		mime: ['application/pdf'],
	},
	{
		id: 'folder',
		label: t('files', 'Folders'),
		icon: colorize(svgFolder, window.getComputedStyle(document.body).getPropertyValue('--color-primary-element')),
		mime: ['httpd/unix-directory'],
	},
	{
		id: 'audio',
		label: t('files', 'Audio'),
		icon: svgAudio,
		mime: ['audio'],
	},
	{
		id: 'image',
		// TRANSLATORS: This is for filtering files, e.g. PNG or JPEG, so photos, drawings, or images in general
		label: t('files', 'Photos and images'),
		icon: svgImage,
		mime: ['image'],
	},
	{
		id: 'video',
		label: t('files', 'Videos'),
		icon: svgMovie,
		mime: ['video'],
	},
] as ITypePreset[]

class TypeFilter extends FileListFilter {

	private currentInstance?: Vue
	private currentPresets: ITypePreset[]
	private allPresets?: ITypePreset[]

	constructor() {
		super('files:type', 10)
		this.currentPresets = []
	}

	public async mount(el: HTMLElement) {
		// We need to defer this as on init script this is not available:
		if (this.allPresets === undefined) {
			this.allPresets = await getTypePresets()
		}

		// Already mounted
		if (this.currentInstance) {
			this.currentInstance.$destroy()
			delete this.currentInstance
		}

		const View = Vue.extend(FileListFilterType as never)
		this.currentInstance = new View({
			propsData: {
				presets: this.currentPresets,
				typePresets: this.allPresets!,
			},
			el,
		})
			.$on('update:presets', this.setPresets.bind(this))
			.$mount()
	}

	public filter(nodes: INode[]): INode[] {
		if (!this.currentPresets || this.currentPresets.length === 0) {
			return nodes
		}

		const mimeList = this.currentPresets.reduce((previous: string[], current) => [...previous, ...current.mime], [] as string[])
		return nodes.filter((node) => {
			if (!node.mime) {
				return false
			}
			const mime = node.mime.toLowerCase()

			if (mimeList.includes(mime)) {
				return true
			} else if (mimeList.includes(window.OC.MimeTypeList.aliases[mime])) {
				return true
			} else if (mimeList.includes(mime.split('/')[0])) {
				return true
			}
			return false
		})
	}

	public reset(): void {
		this.setPresets()
	}

	public setPresets(presets?: ITypePreset[]) {
		this.currentPresets = presets ?? []
		this.currentInstance!.$props.presets = presets
		this.filterUpdated()

		const chips: IFileListFilterChip[] = []
		if (presets && presets.length > 0) {
			for (const preset of presets) {
				chips.push({
					icon: preset.icon,
					text: preset.label,
					onclick: () => this.removeFilterPreset(preset.id),
				})
			}
		} else {
			(this.currentInstance as { resetFilter: () => void } | undefined)?.resetFilter()
		}
		this.updateChips(chips)
	}

	/**
	 * Helper callback that removed a preset from selected.
	 * This is used when clicking on "remove" on a filter-chip.
	 * @param presetId Id of preset to remove
	 */
	private removeFilterPreset(presetId: string) {
		const filtered = this.currentPresets.filter(({ id }) => id !== presetId)
		this.setPresets(filtered)
	}

}

/**
 * Register the file list filter by file type
 */
export function registerTypeFilter() {
	registerFileListFilter(new TypeFilter())
}
