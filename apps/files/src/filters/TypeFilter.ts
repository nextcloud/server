/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFileListFilterChip, IFileListFilterWithUi, INode } from '@nextcloud/files'

import svgFileOutline from '@mdi/svg/svg/file-outline.svg?raw'
import { FileListFilter, registerFileListFilter } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import wrap from '@vue/web-component-wrapper'
import Vue from 'vue'
import FileListFilterType from '../components/FileListFilter/FileListFilterType.vue'
import logger from '../logger.ts'

export interface ITypePreset {
	id: string
	label: string
	icon: string
	mime: string[]
}

const tagName = 'files-file-list-filter-type'

class TypeFilter extends FileListFilter implements IFileListFilterWithUi {
	private currentInstance?: Vue
	private currentPresets: ITypePreset[]

	public readonly displayName = t('files', 'Type')
	public readonly iconSvgInline = svgFileOutline
	public readonly tagName = tagName

	constructor() {
		super('files:type', 10)
		this.currentPresets = []
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
		// to be listener by the component
		this.dispatchEvent(new CustomEvent('reset'))
	}

	public get presets(): ITypePreset[] {
		return this.currentPresets
	}

	public setPresets(presets?: ITypePreset[]) {
		logger.debug('TypeFilter: setting presets', { presets })

		this.currentPresets = presets ?? []
		if (this.currentInstance !== undefined) {
			// could be called before the instance was created
			// (meaning the files list is not mounted yet)
			this.currentInstance.$props.presets = presets
		}

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
	 *
	 * @param presetId Id of preset to remove
	 */
	private removeFilterPreset(presetId: string) {
		const filtered = this.currentPresets.filter(({ id }) => id !== presetId)
		this.dispatchEvent(new CustomEvent('deselect', { detail: presetId }))
		this.setPresets(filtered)
	}
}

export type { TypeFilter }

/**
 * Register the file list filter by file type
 */
export function registerTypeFilter() {
	const WrappedComponent = wrap(Vue, FileListFilterType)
	// In Vue 2, wrap doesn't support disabling shadow :(
	// Disable with a hack
	Object.defineProperty(WrappedComponent.prototype, 'attachShadow', {
		value() {
			return this
		},
	})
	Object.defineProperty(WrappedComponent.prototype, 'shadowRoot', {
		get() {
			return this
		},
	})

	window.customElements.define(tagName, WrappedComponent)
	registerFileListFilter(new TypeFilter())
}
