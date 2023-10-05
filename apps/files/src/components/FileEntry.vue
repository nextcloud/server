<!--
  - @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
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
				:checked="selectedFiles"
				:value="fileid"
				name="selectedFiles"
				@update:checked="onSelectionChange" />
		</td>

		<!-- Link to file -->
		<td class="files-list__row-name">
			<a ref="name" v-bind="linkAttrs" @click="execDefaultAction">
				<!-- Icon or preview -->
				<span class="files-list__row-icon">
					<FolderIcon v-if="source.type === 'folder'" />

					<!-- Decorative image, should not be aria documented -->
					<span v-else-if="previewUrl && !backgroundFailed"
						ref="previewImg"
						class="files-list__row-icon-preview"
						:style="{ backgroundImage }" />

					<span v-else-if="mimeIconUrl"
						class="files-list__row-icon-preview files-list__row-icon-preview--mime"
						:style="{ backgroundImage: mimeIconUrl }" />

					<FileIcon v-else />

					<!-- Favorite icon -->
					<span v-if="isFavorite"
						class="files-list__row-icon-favorite"
						:aria-label="t('files', 'Favorite')">
						<StarIcon aria-hidden="true" :size="20" />
					</span>
				</span>

				<!-- File name -->
				<span class="files-list__row-name-text">
					<!-- Keep the displayName stuck to the extension to avoid whitespace rendering issues-->
					<span class="files-list__row-name-name" v-text="displayName" />
					<span class="files-list__row-name-ext" v-text="extension" />
				</span>
			</a>
		</td>

		<!-- Actions -->
		<td :class="`files-list__row-actions-${uniqueId}`" class="files-list__row-actions">
			<!-- Inline actions -->
			<!-- TODO: implement CustomElementRender -->

			<!-- Menu actions -->
			<NcActions v-if="active"
				ref="actionsMenu"
				:boundaries-element="boundariesElement"
				:container="boundariesElement"
				:disabled="source._loading"
				:force-title="true"
				:force-menu="true"
				:inline="enabledInlineActions.length"
				:open.sync="openedMenu">
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
			class="files-list__row-size"
			@click="openDetailsIfAvailable">
			<span>{{ size }}</span>
		</td>

		<!-- View columns -->
		<td v-for="column in columns"
			:key="column.id"
			:class="`files-list__row-${currentView?.id}-${column.id}`"
			class="files-list__row-column-custom"
			@click="openDetailsIfAvailable">
			<CustomElementRender v-if="active"
				:current-view="currentView"
				:render="column.render"
				:source="source" />
		</td>
	</Fragment>
</template>

<script lang='ts'>
import { debounce } from 'debounce'
import { formatFileSize } from '@nextcloud/files'
import { Fragment } from 'vue-frag'
import { join, extname } from 'path'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate } from '@nextcloud/l10n'
import CancelablePromise from 'cancelable-promise'
import FileIcon from 'vue-material-design-icons/File.vue'
import FolderIcon from 'vue-material-design-icons/Folder.vue'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import StarIcon from 'vue-material-design-icons/Star.vue'
import Vue from 'vue'

import { ACTION_DETAILS } from '../actions/sidebarAction.ts'
import { getFileActions } from '../services/FileAction.ts'
import { hashCode } from '../utils/hashUtils.ts'
import { isCachedPreview } from '../services/PreviewService.ts'
import { useActionsMenuStore } from '../store/actionsmenu.ts'
import { useFilesStore } from '../store/files.ts'
import { useKeyboardStore } from '../store/keyboard.ts'
import { useSelectionStore } from '../store/selection.ts'
import { useUserConfigStore } from '../store/userconfig.ts'
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
		StarIcon,
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
		nodes: {
			type: Array,
			required: true,
		},
		filesListWidth: {
			type: Number,
			default: 0,
		},
	},

	setup() {
		const actionsMenuStore = useActionsMenuStore()
		const filesStore = useFilesStore()
		const keyboardStore = useKeyboardStore()
		const selectionStore = useSelectionStore()
		const userConfigStore = useUserConfigStore()
		return {
			actionsMenuStore,
			filesStore,
			keyboardStore,
			selectionStore,
			userConfigStore,
		}
	},

	data() {
		return {
			backgroundFailed: false,
			backgroundImage: '',
			boundariesElement: document.querySelector('.app-content > .files-list'),
			loading: '',
		}
	},

	computed: {
		userConfig() {
			return this.userConfigStore.userConfig
		},

		currentView() {
			return this.$navigation.active
		},
		columns() {
			// Hide columns if the list is too small
			if (this.filesListWidth < 512) {
				return []
			}
			return this.currentView?.columns || []
		},

		dir() {
			// Remove any trailing slash but leave root slash
			return (this.$route?.query?.dir || '/').replace(/^(.+)\/$/, '$1')
		},
		fileid() {
			return this.source?.fileid?.toString?.()
		},

		extension() {
			if (this.source.attributes?.displayName) {
				return extname(this.source.attributes.displayName)
			}
			return this.source.extension || ''
		},
		displayName() {
			const ext = this.extension
			const name = (this.source.attributes.displayName
				|| this.source.basename)

			// Strip extension from name if defined
			return !ext ? name : name.slice(0, 0 - ext.length)
		},

		size() {
			const size = parseInt(this.source.size, 10) || 0
			if (typeof size !== 'number' || size < 0) {
				return this.t('files', 'Pending')
			}
			return formatFileSize(size, true, true).replace('iB', 'B')
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

		linkAttrs() {
			if (this.enabledDefaultActions.length > 0) {
				const action = this.enabledDefaultActions[0]
				const displayName = action.displayName([this.source], this.currentView)
				return {
					class: ['files-list__row-default-action', 'files-list__row-action-' + action.id],
					role: 'button',
					title: displayName,
				}
			}

			/**
			 * A folder would never reach this point
			 * as it has open-folder as default action.
			 * Just to be safe, let's handle it.
			 */
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

		selectedFiles() {
			return this.selectionStore.selected
		},
		isSelected() {
			return this.selectedFiles.includes(this.source?.fileid?.toString?.())
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
				url.searchParams.set('a', this.cropPreviews === true ? '0' : '1')
				return url.href
			} catch (e) {
				return null
			}
		},
		mimeIconUrl() {
			const mimeType = this.source.mime || 'application/octet-stream'
			const mimeIconUrl = window.OC?.MimeType?.getIconUrl?.(mimeType)
			if (mimeIconUrl) {
				return `url(${mimeIconUrl})`
			}
			return ''
		},

		enabledActions() {
			return actions
				.filter(action => !action.enabled || action.enabled([this.source], this.currentView))
				.sort((a, b) => (a.order || 0) - (b.order || 0))
		},
		enabledInlineActions() {
			if (this.filesListWidth < 768) {
				return []
			}
			return this.enabledActions.filter(action => action?.inline?.(this.source, this.currentView))
		},
		enabledMenuActions() {
			if (this.filesListWidth < 768) {
				// If we have a default action, do not render the first one
				if (this.enabledDefaultActions.length > 0) {
					return this.enabledActions.slice(1)
				}
				return this.enabledActions
			}

			const actions = [
				...this.enabledInlineActions,
				...this.enabledActions.filter(action => !action.inline),
			]

			// If we have a default action, do not render the first one
			if (this.enabledDefaultActions.length > 0) {
				return actions.slice(1)
			}

			return actions
		},
		enabledDefaultActions() {
			return [
				...this.enabledActions.filter(action => action.default),
			]
		},
		openedMenu: {
			get() {
				return this.actionsMenuStore.opened === this.uniqueId
			},
			set(opened) {
				this.actionsMenuStore.opened = opened ? this.uniqueId : null
			},
		},

		uniqueId() {
			return hashCode(this.source.source)
		},

		isFavorite() {
			return this.source.attributes.favorite === 1
		},
	},

	watch: {
		active(active, before) {
			if (active === false && before === true) {
				this.resetState()

				// When the row is not active anymore
				// remove the display from the row to prevent
				// keyboard interaction with it.
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

		// Right click watcher on tr
		this.$el.parentNode?.addEventListener?.('contextmenu', this.onRightClick)
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
				})
			})
		},

		resetState() {
			// Reset loading state
			this.loading = ''

			// Reset the preview
			this.clearImg()

			// Close menu
			this.openedMenu = false
		},

		clearImg() {
			this.backgroundImage = ''
			this.backgroundFailed = false

			if (this.previewPromise) {
				this.previewPromise.cancel()
				this.previewPromise = null
			}
		},

		async onActionClick(action) {
			const displayName = action.displayName([this.source], this.currentView)
			try {
				// Set the loading marker
				this.loading = action.id
				Vue.set(this.source, '_loading', true)

				const success = await action.exec(this.source, this.currentView, this.dir)

				// If the action returns null, we stay silent
				if (success === null) {
					return
				}

				if (success) {
					showSuccess(this.t('files', '"{displayName}" action executed successfully', { displayName }))
					return
				}
				showError(this.t('files', '"{displayName}" action failed', { displayName }))
			} catch (e) {
				logger.error('Error while executing action', { action, e })
				showError(this.t('files', '"{displayName}" action failed', { displayName }))
			} finally {
				// Reset the loading marker
				this.loading = ''
				Vue.set(this.source, '_loading', false)
			}
		},
		execDefaultAction(event) {
			if (this.enabledDefaultActions.length > 0) {
				event.preventDefault()
				event.stopPropagation()
				// Execute the first default action if any
				this.enabledDefaultActions[0].exec(this.source, this.currentView, this.dir)
			}
		},

		openDetailsIfAvailable(event) {
			const detailsAction = this.enabledDefaultActions.find(action => action.id === ACTION_DETAILS)
			if (detailsAction) {
				event.preventDefault()
				event.stopPropagation()
				detailsAction.exec(this.source, this.currentView)
			}
		},

		onSelectionChange(selection) {
			const newSelectedIndex = this.index
			const lastSelectedIndex = this.selectionStore.lastSelectedIndex

			// Get the last selected and select all files in between
			if (this.keyboardStore?.shiftKey && lastSelectedIndex !== null) {
				const isAlreadySelected = this.selectedFiles.includes(this.fileid)

				const start = Math.min(newSelectedIndex, lastSelectedIndex)
				const end = Math.max(lastSelectedIndex, newSelectedIndex)

				const lastSelection = this.selectionStore.lastSelection
				const filesToSelect = this.nodes
					.map(file => file.fileid?.toString?.())
					.slice(start, end + 1)

				// If already selected, update the new selection _without_ the current file
				const selection = [...lastSelection, ...filesToSelect]
					.filter(fileId => !isAlreadySelected || fileId !== this.fileid)

				logger.debug('Shift key pressed, selecting all files in between', { start, end, filesToSelect, isAlreadySelected })
				// Keep previous lastSelectedIndex to be use for further shift selections
				this.selectionStore.set(selection)
				return
			}

			logger.debug('Updating selection', { selection })
			this.selectionStore.set(selection)
			this.selectionStore.setLastIndex(newSelectedIndex)
		},

		// Open the actions menu on right click
		onRightClick(event) {
			// If already opened, fallback to default browser
			if (this.openedMenu) {
				return
			}

			// If the clicked row is in the selection, open global menu
			const isMoreThanOneSelected = this.selectedFiles.length > 1
			this.actionsMenuStore.opened = this.isSelected && isMoreThanOneSelected ? 'global' : this.uniqueId

			// Prevent any browser defaults
			event.preventDefault()
			event.stopPropagation()
		},

		t: translate,
		formatFileSize,
	},
})
</script>

<style scoped lang='scss'>
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
