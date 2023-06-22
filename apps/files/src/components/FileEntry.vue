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
			<!-- Icon or preview -->
			<span class="files-list__row-icon" @click="execDefaultAction">
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

			<!-- Rename input -->
			<form v-show="isRenaming"
				v-on-click-outside="stopRenaming"
				:aria-hidden="!isRenaming"
				:aria-label="t('files', 'Rename file')"
				class="files-list__row-rename"
				@submit.prevent.stop="onRename">
				<NcTextField ref="renameInput"
					:aria-label="t('files', 'File name')"
					:autofocus="true"
					:minlength="1"
					:required="true"
					:value.sync="newName"
					enterkeyhint="done"
					@keyup="checkInputValidity"
					@keyup.esc="stopRenaming" />
			</form>

			<a v-show="!isRenaming"
				ref="basename"
				:aria-hidden="isRenaming"
				v-bind="linkTo"
				@click="execDefaultAction">
				<!-- File name -->
				<span class="files-list__row-name-text">
					<!-- Keep the displayName stuck to the extension to avoid whitespace rendering issues-->
					{{ displayName }}<span class="files-list__row-name-ext" v-text="source.extension" />
				</span>
			</a>
		</td>

		<!-- Actions -->
		<td v-show="!isRenamingSmallScreen" :class="`files-list__row-actions-${uniqueId}`" class="files-list__row-actions">
			<!-- Inline actions -->
			<!-- TODO: implement CustomElementRender -->

			<!-- Menu actions -->
			<NcActions v-if="active"
				ref="actionsMenu"
				:boundaries-element="boundariesElement"
				:container="boundariesElement"
				:disabled="source._loading"
				:force-title="true"
				:force-menu="enabledInlineActions.length === 0 /* forceMenu only if no inline actions */"
				:inline="enabledInlineActions.length"
				:open.sync="openedMenu">
				<NcActionButton v-for="action in enabledMenuActions"
					:key="action.id"
					:class="'files-list__row-action-' + action.id"
					:close-after-click="true"
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

		<!-- Mtime -->
		<td v-if="isMtimeAvailable"
			class="files-list__row-mtime"
			@click="openDetailsIfAvailable">
			<span>{{ mtime }}</span>
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
import { emit } from '@nextcloud/event-bus'
import { formatFileSize } from '@nextcloud/files'
import { Fragment } from 'vue-frag'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate } from '@nextcloud/l10n'
import { vOnClickOutside } from '@vueuse/components'
import axios from '@nextcloud/axios'
import CancelablePromise from 'cancelable-promise'
import FileIcon from 'vue-material-design-icons/File.vue'
import FolderIcon from 'vue-material-design-icons/Folder.vue'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import StarIcon from 'vue-material-design-icons/Star.vue'
import Vue from 'vue'

import { ACTION_DETAILS } from '../actions/sidebarAction.ts'
import { getFileActions, DefaultType } from '../services/FileAction.ts'
import { hashCode } from '../utils/hashUtils.ts'
import { isCachedPreview } from '../services/PreviewService.ts'
import { useActionsMenuStore } from '../store/actionsmenu.ts'
import { useFilesStore } from '../store/files.ts'
import type moment from 'moment'
import { useKeyboardStore } from '../store/keyboard.ts'
import { useSelectionStore } from '../store/selection.ts'
import { useUserConfigStore } from '../store/userconfig.ts'
import { useRenamingStore } from '../store/renaming.ts'
import CustomElementRender from './CustomElementRender.vue'
import CustomSvgIconRender from './CustomSvgIconRender.vue'
import logger from '../logger.js'

// The registered actions list
const actions = getFileActions()

Vue.directive('onClickOutside', vOnClickOutside)

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
		NcTextField,
		StarIcon,
	},

	props: {
		active: {
			type: Boolean,
			default: false,
		},
		isMtimeAvailable: {
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
		const renamingStore = useRenamingStore()
		const selectionStore = useSelectionStore()
		const userConfigStore = useUserConfigStore()
		return {
			actionsMenuStore,
			filesStore,
			keyboardStore,
			renamingStore,
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
		displayName() {
			const ext = (this.source.extension || '')
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

		mtime() {
			if (this.source.mtime) {
				return moment(this.source.mtime).fromNow()
			}
			return this.t('files_trashbin', 'A long time ago')
		},
		mtimeTitle() {
			if (this.source.mtime) {
				return moment(this.source.mtime).format('LLL')
			}
			return ''
		},

		linkTo() {
			if (this.enabledDefaultActions.length > 0) {
				const action = this.enabledDefaultActions[0]
				const displayName = action.displayName([this.source], this.currentView)
				return {
					title: displayName,
					role: 'button',
				}
			}

			return {
				download: this.source.basename,
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

		// Sorted actions that are enabled for this node
		enabledActions() {
			return actions
				.filter(action => !action.enabled || action.enabled([this.source], this.currentView))
				.sort((a, b) => (a.order || 0) - (b.order || 0))
		},

		// Enabled action that are displayed inline
		enabledInlineActions() {
			if (this.filesListWidth < 768) {
				return []
			}
			return this.enabledActions.filter(action => action?.inline?.(this.source, this.currentView))
		},

		// Default actions
		enabledDefaultActions() {
			return this.enabledActions.filter(action => !!action.default)
		},

		// Actions shown in the menu
		enabledMenuActions() {
			return this.enabledActions.filter(action => action.default !== DefaultType.HIDDEN)
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

		isRenaming() {
			return this.renamingStore.renamingNode === this.source
		},
		isRenamingSmallScreen() {
			return this.isRenaming && this.filesListWidth < 512
		},
		newName: {
			get() {
				return this.renamingStore.newName
			},
			set(newName) {
				this.renamingStore.newName = newName
			},
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
		source() {
			this.resetState()
			this.debounceIfNotCached()
		},

		/**
		 * If renaming starts, select the file name
		 * in the input, without the extension.
		 */
		isRenaming() {
			this.startRenaming()
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

		/**
		 * Check if the file name is valid and update the
		 * input validity using browser's native validation.
		 * @param event the keyup event
		 */
		checkInputValidity(event: KeyboardEvent) {
			const input = event?.target as HTMLInputElement
			const newName = this.newName.trim?.() || ''
			try {
				this.isFileNameValid(newName)
				input.setCustomValidity('')
				input.title = ''
			} catch (e) {
				input.setCustomValidity(e.message)
				input.title = e.message
			} finally {
				input.reportValidity()
			}
		},
		isFileNameValid(name) {
			const trimmedName = name.trim()
			if (trimmedName === '.' || trimmedName === '..') {
				throw new Error(this.t('files', '"{name}" is an invalid file name.', { name }))
			} else if (trimmedName.length === 0) {
				throw new Error(this.t('files', 'File name cannot be empty.'))
			} else if (trimmedName.indexOf('/') !== -1) {
				throw new Error(this.t('files', '"/" is not allowed inside a file name.'))
			} else if (trimmedName.match(OC.config.blacklist_files_regex)) {
				throw new Error(this.t('files', '"{name}" is not an allowed filetype.', { name }))
			} else if (this.checkIfNodeExists(name)) {
				throw new Error(this.t('files', '{newName} already exists.', { newName: name }))
			}

			return true
		},
		checkIfNodeExists(name) {
			return this.nodes.find(node => node.basename === name && node !== this.source)
		},

		startRenaming() {
			this.checkInputValidity()
			this.$nextTick(() => {
				const extLength = (this.source.extension || '').length
				const length = this.source.basename.length - extLength
				const input = this.$refs.renameInput?.$refs?.inputField?.$refs?.input
				if (!input) {
					logger.error('Could not find the rename input')
					return
				}
				input.setSelectionRange(0, length)
				input.focus()
			})
		},
		stopRenaming() {
			if (!this.isRenaming) {
				return
			}

			// Reset the renaming store
			this.renamingStore.$reset()
		},

		// Rename and move the file
		async onRename() {
			const oldName = this.source.basename
			const oldSource = this.source.source
			const newName = this.newName.trim?.() || ''
			if (newName === '') {
				showError(this.t('files', 'Name cannot be empty'))
				return
			}

			if (oldName === newName) {
				this.stopRenaming()
				return
			}

			// Checking if already exists
			if (this.checkIfNodeExists(newName)) {
				showError(this.t('files', 'Another entry with the same name already exists'))
				return
			}

			// Set loading state
			this.loading = 'renaming'
			Vue.set(this.source, '_loading', true)

			// Update node
			this.source.rename(newName)

			try {
				await axios({
					method: 'MOVE',
					url: oldSource,
					headers: {
						Destination: encodeURI(this.source.source),
					},
				})

				// Success 🎉
				emit('files:node:updated', this.source)
				emit('files:node:renamed', this.source)
				showSuccess(this.t('files', 'Renamed "{oldName}" to "{newName}"', { oldName, newName }))
				this.stopRenaming()
				this.$nextTick(() => {
					this.$refs.basename.focus()
				})
			} catch (error) {
				logger.error('Error while renaming file', { error })
				this.source.rename(oldName)
				this.$refs.renameInput.focus()

				// TODO: 409 means current folder does not exist, redirect ?
				if (error?.response?.status === 404) {
					showError(this.t('files', 'Could not rename "{oldName}", it does not exist any more', { oldName }))
					return
				} else if (error?.response?.status === 412) {
					showError(this.t('files', 'The name "{newName}"" is already used in the folder "{dir}". Please choose a different name.', { newName, dir: this.dir }))
					return
				}

				// Unknown error
				showError(this.t('files', 'Could not rename "{oldName}"', { oldName }))
			} finally {
				this.loading = false
				Vue.set(this.source, '_loading', false)
			}
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
