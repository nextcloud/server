<!--
  - @copyright Copyright (c) 2019 Gary Kim <gary@garykim.dev>
  -
  - @author Gary Kim <gary@garykim.dev>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<script lang='ts'>
import { Folder, File } from '@nextcloud/files'
import { Fragment } from 'vue-fragment'
import { join } from 'path'
import { translate } from '@nextcloud/l10n'
import FileIcon from 'vue-material-design-icons/File.vue'
import FolderIcon from 'vue-material-design-icons/Folder.vue'
import TrashCan from 'vue-material-design-icons/TrashCan.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import Vue from 'vue'

import logger from '../logger.js'
import { useSelectionStore } from '../store/selection'
import { useFilesStore } from '../store/files'
import { loadState } from '@nextcloud/initial-state'
import { debounce } from 'debounce'

// TODO: move to store
// TODO: watch 'files:config:updated' event
const userConfig = loadState('files', 'config', {})

// The preview service worker cache name (see webpack config)
const SWCacheName = 'previews'

export default Vue.extend({
	name: 'FileEntry',

	components: {
		FileIcon,
		FolderIcon,
		Fragment,
		NcActionButton,
		NcActions,
		NcCheckboxRadioSwitch,
		Pencil,
		TrashCan,
	},

	props: {
		source: {
			type: [File, Folder],
			required: true,
		},
	},

	setup() {
		const filesStore = useFilesStore()
		const selectionStore = useSelectionStore()
		return {
			filesStore,
			selectionStore,
		}
	},

	data() {
		return {
			userConfig,
			backgroundImage: '',
			backgroundFailed: false,
		}
	},

	computed: {
		/** @return {Navigation} */
		currentView() {
			return this.$navigation.active
		},

		columns() {
			return this.currentView?.columns || []
		},

		dir() {
			// Remove any trailing slash but leave root slash
			return (this.$route?.query?.dir || '/').replace(/^(.+)\/$/, '$1')
		},

		fileid() {
			return this.source.attributes.fileid
		},
		displayName() {
			return this.source.attributes.displayName
				|| this.source.basename
		},

		linkTo() {
			if (this.source.type === 'folder') {
				const to = { ...this.$route, query: { dir: join(this.dir, this.source.basename) } }
				return {
					is: 'router-link',
					title: this.t('files', 'Open folder {name}', { name: this.displayName }),
					to,
				}
			}
			return {
				href: this.source.source,
				// TODO: Use first action title ?
				title: this.t('files', 'Download file {name}', { name: this.displayName }),
			}
		},

		selectedFiles: {
			get() {
				return this.selectionStore.selected
			},
			set(selection) {
				logger.debug('Added node to selection', { selection })
				this.selectionStore.set(selection)
			},
		},

		previewUrl() {
			try {
				const url = new URL(window.location.origin + this.source.attributes.previewUrl)
				const cropping = this.userConfig?.crop_image_previews === true
				url.searchParams.set('a', cropping ? '1' : '0')
				return url.href
			} catch (e) {
				return null
			}
		},

		mimeUrl() {
			const mimeType = this.source.mime || 'application/octet-stream'
			const mimeUrl = window.OC?.MimeType?.getIconUrl?.(mimeType)
			if (mimeUrl) {
				return `url(${mimeUrl})`
			}
			return ''
		},
	},

	watch: {
		source() {
			this.resetPreview()
			this.debounceIfNotCached()
		},
	},

	mounted() {
		// Init the debounce function on mount and
		// not when the module is imported ⚠
		this.debounceGetPreview = debounce(function() {
			this.fetchAndApplyPreview()
		}, 150, false)

		this.debounceIfNotCached()
	},

	methods: {
		/**
		 * Get a cached note from the store
		 *
		 * @param {number} fileId the file id to get
		 * @return {Folder|File}
		 */
		getNode(fileId) {
			return this.filesStore.getNode(fileId)
		},

		async debounceIfNotCached() {
			if (!this.previewUrl) {
				return
			}

			// Check if we already have this preview cached
			const isCached = await this.isCachedPreview(this.previewUrl)
			if (isCached) {
				logger.debug('Preview already cached', { fileId: this.source.attributes.fileid, backgroundFailed: this.backgroundFailed })
				this.backgroundImage = `url(${this.previewUrl})`
				this.backgroundFailed = false
				return
			}

			// We don't have this preview cached or it expired, requesting it
			this.debounceGetPreview()
		},

		 fetchAndApplyPreview() {
			logger.debug('Fetching preview', { fileId: this.source.attributes.fileid })
			this.img = new Image()
			this.img.onload = () => {
				this.backgroundImage = `url(${this.previewUrl})`
			}
			this.img.onerror = (a, b, c) => {
				this.backgroundFailed = true
				logger.error('Failed to fetch preview', { fileId: this.source.attributes.fileid, a, b, c })
			}
			this.img.src = this.previewUrl
		},

		resetPreview() {
			// Reset the preview
			this.backgroundImage = ''
			this.backgroundFailed = false

			// If we're already fetching a preview, cancel it
			if (this.img) {
				// Do not fail on cancel
				this.img.onerror = null
				this.img.src = ''
				delete this.img
			}
		},

		isCachedPreview(previewUrl) {
			return caches.open(SWCacheName)
				.then(function(cache) {
					return cache.match(previewUrl)
						.then(function(response) {
							return !!response // or `return response ? true : false`, or similar.
						})
				})
		},

		t: translate,
	},

	/**
	 * While a bit more complex, this component is pretty straightforward.
	 * For performance reasons, we're using a render function instead of a template.
	 */
	render(createElement) {
		// Checkbox
		const checkbox = createElement('td', {
			staticClass: 'files-list__row-checkbox',
		}, [createElement('NcCheckboxRadioSwitch', {
			attrs: {
				'aria-label': this.t('files', 'Select the row for {displayName}', {
					displayName: this.displayName,
				}),
				checked: this.selectedFiles,
				value: this.fileid.toString(),
				name: 'selectedFiles',
			},
			on: {
				'update:checked': ($event) => {
					this.selectedFiles = $event
				},
			},
		})])

		// Icon
		const iconContent = () => {
			// Folder icon
			if (this.source.type === 'folder') {
				return createElement('FolderIcon')
			}
			// Render cached preview or fallback to mime icon if defined
			const renderPreview = this.previewUrl && !this.backgroundFailed
			if (renderPreview || this.mimeUrl) {
				return createElement('span', {
					ref: 'previewImg',
					class: {
						'files-list__row-icon-preview': true,
						'files-list__row-icon-preview--mime': !renderPreview,
					},
					style: {
						backgroundImage: renderPreview
							? this.backgroundImage
							: this.mimeUrl,
					},
				})
			}
			// Empty file icon
			return createElement('FileIcon')
		}
		const icon = createElement('td', {
			staticClass: 'files-list__row-icon',
		}, [iconContent()])

		// Name
		const name = createElement('td', {
			staticClass: 'files-list__row-name',
		}, [
			createElement(this.linkTo?.is || 'a', {
				attrs: this.linkTo,
			}, this.displayName),
		])

		// Actions
		const actions = createElement('td', {
			staticClass: 'files-list__row-actions',
		}, [createElement('NcActions', [
			createElement('NcActionButton', [
				this.t('files', 'Rename'),
				createElement('Pencil', {
					slot: 'icon',
				}),
			]),
			createElement('NcActionButton', [
				this.t('files', 'Delete'),
				createElement('TrashCan', {
					slot: 'icon',
				}),
			]),
		])])

		// Columns
		const columns = this.columns.map(column => {
			const td = document.createElement('td')
			column.render(td, this.source)
			return createElement('td', {
				class: {
					[`files-list__row-${this.currentView?.id}-${column.id}`]: true,
					'files-list__row-column--custom': true,
				},
				key: column.id,
				domProps: {
					innerHTML: td.innerHTML,
				},
			}, '123')
		})

		console.debug(columns, this.displayName)

		return createElement('Fragment', [
			checkbox,
			icon,
			name,
			actions,
			...columns,
		])
	},
})
</script>

<style scoped lang='scss'>
@import '../mixins/fileslist-row.scss';

.files-list__row-icon-preview:not([style*='background']) {
    background: linear-gradient(110deg, var(--color-loading-dark) 0%, var(--color-loading-dark) 25%, var(--color-loading-light) 50%, var(--color-loading-dark) 75%, var(--color-loading-dark) 100%);
    background-size: 400%;
	animation: preview-gradient-slide 1s ease infinite;
}
</style>

<style>
@keyframes preview-gradient-slide {
    from {
        background-position: 100% 0%;
    }
    to {
        background-position: 0% 0%;
    }
}
</style>
