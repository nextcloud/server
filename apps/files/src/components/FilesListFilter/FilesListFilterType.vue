<template>
	<FilesListFilter class="files-list-filter-type"
		:is-active="isActive"
		:filter-name="label"
		@reset-filter="resetFilter">
		<template #icon>
			<NcIconSvgWrapper :path="mdiFile" />
		</template>
		<NcActionButton v-for="fileType of availableTypes"
			:key="fileType.id"
			type="checkbox"
			:model-value="selectedOptions.includes(fileType.id)"
			@click="toggleOption(fileType.id)">
			<template #icon>
				<NcIconSvgWrapper :path="fileType.icon" :style="{ color: fileType.color }" />
			</template>
			{{ fileType.name }}
		</NcActionButton>
	</FilesListFilter>
</template>

<script lang="ts">
import type { Node } from '@nextcloud/files'

import { mdiFile, mdiFileDocument, mdiFilePdfBox, mdiFilePresentationBox, mdiFileTableBox, mdiFolder, mdiImage, mdiMovie, mdiMusic } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'

import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'

import FilesListFilter from './FilesListFilter.vue'
import useFilesFilter from '../../composables/useFilesFilter'

const availableTypes = [
	{
		id: 'document',
		name: t('files', 'Documents'),
		icon: mdiFileDocument,
		color: '#49abea',
		mime: ['x-office/document'],
	},
	{
		id: 'spreadsheet',
		name: t('files', 'Spreadsheets'),
		icon: mdiFileTableBox,
		color: '#9abd4e',
		mime: ['x-office/spreadsheet'],
	},
	{
		id: 'presentation',
		name: t('files', 'Presentations'),
		icon: mdiFilePresentationBox,
		color: '#f0965f',
		mime: ['x-office/presentation'],
	},
	{
		id: 'folder',
		name: t('files', 'Folders'),
		icon: mdiFolder,
		color: 'var(--color-primary-element)',
		mime: ['httpd/unix-directory'],
	},
	{
		id: 'pdf',
		name: t('files', 'PDFs'),
		icon: mdiFilePdfBox,
		color: '#dc5047',
		mime: ['application/pdf'],
	},
	{
		id: 'audio',
		name: t('files', 'Audio'),
		icon: mdiMusic,
		mime: ['audio'],
		color: undefined,
	},
	{
		id: 'image',
		name: t('files', 'Pictures and images'),
		icon: mdiImage,
		mime: ['image'],
		color: undefined,
	},
	{
		id: 'video',
		name: t('files', 'Videos'),
		icon: mdiMovie,
		mime: ['video'],
		color: undefined,
	},
] as const

export default defineComponent({
	name: 'FilesListFilterType',

	components: {
		FilesListFilter,
		NcActionButton,
		NcIconSvgWrapper,
	},

	setup() {
		return {
			...useFilesFilter(),

			availableTypes,
			mdiFile,
		}
	},

	data() {
		return {
			selectedOptions: [] as (typeof availableTypes)[number]['id'][],
		}
	},

	computed: {
		isActive() {
			return this.selectedOptions.length > 0
		},

		label() {
			const selected = availableTypes.filter(({ id }) => this.selectedOptions.includes(id))
			if (selected.length === 0) {
				return t('files', 'Type')
			} else {
				return selected.map(({ name }) => name).join(', ')
			}
		},

		/**
		 * List of mime types selected for filtering
		 */
		mimeList() {
			const options = this.availableTypes.filter(({ id }) => this.selectedOptions.includes(id))
			return options.reduce((previous: string[], current) => [...previous, ...current.mime], [] as string[])
		},
	},

	watch: {
		mimeList() {
			if (this.mimeList.length === 0) {
				this.deleteFilter('files-filter-type')
				return
			}

			this.addFilter({
				id: 'files-filter-type',
				filter: (node: Node) => {
					if (!node.mime) {
						return false
					}

					const mime = node.mime.toLowerCase()
					if (this.mimeList.includes(mime)) {
						return true
					} else if (this.mimeList.includes(window.OC.MimeTypeList.aliases[mime])) {
						return true
					} else if (this.mimeList.includes(mime.split('/')[0])) {
						return true
					}
					return false
				},
			})
		},
	},

	methods: {
		t,

		resetFilter() {
			this.selectedOptions = []
		},

		/**
		 * Toggle option from selected option
		 * @param option The option ID to toggle
		 */
		toggleOption(option: typeof this.selectedOptions[number]) {
			const idx = this.selectedOptions.findIndex((id) => id === option)
			if (idx !== -1) {
				this.selectedOptions.splice(idx, 1)
			} else {
				this.selectedOptions.push(option)
			}
		},
	},
})
</script>

<style>
.files-list-filter-type {
	max-width: 220px;
}
</style>
