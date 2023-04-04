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

<template>
	<Fragment>
		<td class="files-list__row-checkbox">
			<NcCheckboxRadioSwitch v-if="active"
				:aria-label="t('files', 'Select the row for {displayName}', { displayName })"
				:checked.sync="selectedFiles"
				:value="fileid.toString()"
				name="selectedFiles" />
		</td>

		<!-- Link to file -->
		<td class="files-list__row-name">
			<a ref="name" v-bind="linkTo">
				<!-- Icon or preview -->
				<span class="files-list__row-icon">
					<FolderIcon v-if="source.type === 'folder'" />

					<!-- Decorative image, should not be aria documented -->
					<span v-else-if="previewUrl && !backgroundFailed"
						ref="previewImg"
						class="files-list__row-icon-preview"
						:style="{ backgroundImage }" />

					<span v-else-if="mimeUrl"
						class="files-list__row-icon-preview files-list__row-icon-preview--mime"
						:style="{ backgroundImage: mimeUrl }" />

					<FileIcon v-else />
				</span>

				<!-- File name -->
				<span class="files-list__row-name-text">{{ displayName }}</span>
			</a>
		</td>

		<!-- Actions -->
		<td :class="`files-list__row-actions-${uniqueId}`" class="files-list__row-actions">
			<!-- Inline actions -->
			<!-- TODO: implement CustomElementRender -->

			<!-- Menu actions -->
			<NcActions v-if="active"
				ref="actionsMenu"
				:force-title="true"
				:inline="enabledInlineActions.length">
				<NcActionButton v-for="action in enabledMenuActions"
					:key="action.id"
					:class="'files-list__row-action-' + action.id"
					@click="onActionClick(action)">
					<template #icon>
						<NcLoadingIcon v-if="loading === action.id" :size="18" />
						<CustomSvgIconRender v-else :svg="action.iconSvgInline([source], currentView)" />
					</template>
					{{ action.displayName([source], currentView) }}
				</NcActionButton>
			</NcActions>
		</td>

		<!-- Size -->
		<td v-if="isSizeAvailable"
			:style="{ opacity: sizeOpacity }"
			class="files-list__row-size">
			<span>{{ size }}</span>
		</td>

		<!-- View columns -->
		<td v-for="column in columns"
			:key="column.id"
			:class="`files-list__row-${currentView?.id}-${column.id}`"
			class="files-list__row-column-custom">
			<CustomElementRender v-if="active"
				:current-view="currentView"
				:render="column.render"
				:source="source" />
		</td>
	</Fragment>
</template>

<script lang='ts'>
import { debounce } from 'debounce'
import { Folder, File, formatFileSize } from '@nextcloud/files'
import { Fragment } from 'vue-fragment'
import { join } from 'path'
import { showError } from '@nextcloud/dialogs'
import { translate } from '@nextcloud/l10n'
import CancelablePromise from 'cancelable-promise'
import FileIcon from 'vue-material-design-icons/File.vue'
import FolderIcon from 'vue-material-design-icons/Folder.vue'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import Vue from 'vue'

import { isCachedPreview } from '../services/PreviewService'
import { getFileActions } from '../services/FileAction'
import { useFilesStore } from '../store/files'
import { UserConfig } from '../types'
import { useSelectionStore } from '../store/selection'
import { useUserConfigStore } from '../store/userconfig'
import CustomElementRender from './CustomElementRender.vue'
import CustomSvgIconRender from './CustomSvgIconRender.vue'
import logger from '../logger.js'

// The registered actions list
const actions = getFileActions()

export default Vue.extend({
	name: 'FileEntry',

	components: {
		CustomElementRender,
		CustomSvgIconRender,
		FileIcon,
		FolderIcon,
		Fragment,
		NcActionButton,
		NcActions,
		NcCheckboxRadioSwitch,
		NcLoadingIcon,
	},

	props: {
		active: {
			type: Boolean,
			default: false,
		},
		isSizeAvailable: {
			type: Boolean,
			default: false,
		},
		source: {
			type: Object,
			required: true,
		},
		index: {
			type: Number,
			required: true,
		},
	},

	setup() {
		const filesStore = useFilesStore()
		const selectionStore = useSelectionStore()
		const userConfigStore = useUserConfigStore()
		return {
			filesStore,
			selectionStore,
			userConfigStore,
		}
	},

	data() {
		return {
			backgroundFailed: false,
			backgroundImage: '',
			loading: '',
		}
	},

	computed: {
		/** @return {UserConfig} */
		userConfig() {
			return this.userConfigStore.userConfig
		},

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
		size() {
			const size = parseInt(this.source.size, 10) || 0
			if (typeof size !== 'number' || size < 0) {
				return this.t('files', 'Pending')
			}
			return formatFileSize(size, true)
		},

		sizeOpacity() {
			const size = parseInt(this.source.size, 10) || 0
			if (!size || size < 0) {
				return 1
			}

			// Whatever theme is active, the contrast will pass WCAG AA
			// with color main text over main background and an opacity of 0.7
			const minOpacity = 0.7
			const maxOpacitySize = 10 * 1024 * 1024
			return minOpacity + (1 - minOpacity) * Math.pow((this.source.size / maxOpacitySize), 2)
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
				logger.debug('Changed nodes selection', { selection })
				this.selectionStore.set(selection)
			},
		},

		cropPreviews() {
			return this.userConfig.crop_image_previews
		},

		previewUrl() {
			try {
				const url = new URL(window.location.origin + this.source.attributes.previewUrl)
				// Request tiny previews
				url.searchParams.set('x', '32')
				url.searchParams.set('y', '32')
				// Handle cropping
				url.searchParams.set('a', this.cropPreviews === true ? '1' : '0')
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

		enabledActions() {
			return actions
				.filter(action => !action.enabled || action.enabled([this.source], this.currentView))
				.sort((a, b) => (a.order || 0) - (b.order || 0))
		},

		enabledInlineActions() {
			return this.enabledActions.filter(action => action?.inline?.(this.source, this.currentView))
		},

		enabledMenuActions() {
			return [
				...this.enabledInlineActions,
				...actions.filter(action => !action.inline),
			]
		},

		uniqueId() {
			return this.hashCode(this.source.source)
		},
	},

	watch: {
		active(active, before) {
			if (active === false && before === true) {
				this.resetState()

				// When the row is not active anymore
				// remove the tabindex from the row
				this.$el.parentNode.style.display = 'none'
				return
			}

			// Restore default tabindex
			this.$el.parentNode.style.display = ''
		},

		/**
		 * When the source changes, reset the preview
		 * and fetch the new one.
		 */
		previewUrl() {
			this.clearImg()
			this.debounceIfNotCached()
		},
	},

	/**
	 * The row is mounted once and reused as we scroll.
	 */
	mounted() {
		// ⚠ Init the debounce function on mount and
		// not when the module is imported  to
		// avoid sharing between recycled components
		this.debounceGetPreview = debounce(function() {
			this.fetchAndApplyPreview()
		}, 150, false)

		// Fetch the preview on init
		this.debounceIfNotCached()
	},

	beforeDestroy() {
		this.resetState()
	},

	methods: {
		async debounceIfNotCached() {
			if (!this.previewUrl) {
				return
			}

			// Check if we already have this preview cached
			const isCached = await isCachedPreview(this.previewUrl)
			if (isCached) {
				this.backgroundImage = `url(${this.previewUrl})`
				this.backgroundFailed = false
				return
			}

			// We don't have this preview cached or it expired, requesting it
			this.debounceGetPreview()
		},

		fetchAndApplyPreview() {
			// Ignore if no preview
			if (!this.previewUrl) {
				return
			}

			// If any image is being processed, reset it
			if (this.previewPromise) {
				this.clearImg()
			}

			// Ensure max 5 previews are being fetched at the same time
			const controller = new AbortController()

			// Store the promise to be able to cancel it
			this.previewPromise = new CancelablePromise((resolve, reject, onCancel) => {
				const img = new Image()
				// If active, load the preview with higher priority
				img.fetchpriority = this.active ? 'high' : 'auto'
				img.onload = () => {
					this.backgroundImage = `url(${this.previewUrl})`
					this.backgroundFailed = false
					resolve(img)
				}
				img.onerror = () => {
					this.backgroundFailed = true
					reject(img)
				}
				img.src = this.previewUrl

				// Image loading has been canceled
				onCancel(() => {
					img.onerror = null
					img.onload = null
					img.src = ''
					controller.abort()
				})
			})
		},

		resetState() {
			// Reset loading state
			this.loading = ''

			// Reset the preview
			this.clearImg()

			// Close menu
			this.$refs.actionsMenu.closeMenu()
		},

		clearImg() {
			this.backgroundImage = ''
			this.backgroundFailed = false

			if (this.previewPromise) {
				this.previewPromise.cancel()
				this.previewPromise = null
			}
		},

		hashCode(str) {
			let hash = 0
			for (let i = 0, len = str.length; i < len; i++) {
				const chr = str.charCodeAt(i)
				hash = (hash << 5) - hash + chr
				hash |= 0 // Convert to 32bit integer
			}
			return hash
		},

		async onActionClick(action) {
			const displayName = action.displayName([this.source], this.currentView)
			try {
				this.loading = action.id
				await action.exec(this.source, this.currentView)
			} catch (e) {
				logger.error('Error while executing action', { action, e })
				showError(this.t('files', 'Error while executing action "{displayName}"', { displayName }))
			} finally {
				this.loading = ''
			}
		},

		t: translate,
		formatFileSize,
	},
})
</script>

<style scoped lang='scss'>
@import '../mixins/fileslist-row.scss';

/* Hover effect on tbody lines only */
tr {
	&:hover,
	&:focus,
	&:active {
		background-color: var(--color-background-dark);
	}
}

/* Preview not loaded animation effect */
.files-list__row-icon-preview:not([style*='background']) {
    background: var(--color-loading-dark);
	// animation: preview-gradient-fade 1.2s ease-in-out infinite;
}
</style>

<style>
/* @keyframes preview-gradient-fade {
    0% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
    100% {
        opacity: 1;
    }
} */
</style>
