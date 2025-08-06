<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="files-list__column files-list__row-actions-batch" data-cy-files-list-selection-actions>
		<NcActions ref="actionsMenu"
			container="#app-content-vue"
			:boundaries-element="boundariesElement"
			:disabled="!!loading || areSomeNodesLoading"
			:force-name="true"
			:inline="enabledInlineActions.length"
			:menu-name="enabledInlineActions.length <= 1 ? t('files', 'Actions') : null"
			:open.sync="openedMenu"
			@close="openedSubmenu = null">
			<!-- Default actions list-->
			<NcActionButton v-for="action in enabledMenuActions"
				:key="action.id"
				:ref="`action-batch-${action.id}`"
				:class="{
					[`files-list__row-actions-batch-${action.id}`]: true,
					[`files-list__row-actions-batch--menu`]: isValidMenu(action)
				}"
				:close-after-click="!isValidMenu(action)"
				:data-cy-files-list-selection-action="action.id"
				:is-menu="isValidMenu(action)"
				:aria-label="action.displayName(nodes, currentView) + ' ' + t('files', '(selected)') /** TRANSLATORS: Selected like 'selected files and folders' */"
				:title="action.title?.(nodes, currentView)"
				@click="onActionClick(action)">
				<template #icon>
					<NcLoadingIcon v-if="loading === action.id" :size="18" />
					<NcIconSvgWrapper v-else :svg="action.iconSvgInline(nodes, currentView)" />
				</template>
				{{ action.displayName(nodes, currentView) }}
			</NcActionButton>

			<!-- Submenu actions list-->
			<template v-if="openedSubmenu && enabledSubmenuActions[openedSubmenu?.id]">
				<!-- Back to top-level button -->
				<NcActionButton class="files-list__row-actions-batch-back" data-cy-files-list-selection-action="menu-back" @click="onBackToMenuClick(openedSubmenu)">
					<template #icon>
						<ArrowLeftIcon />
					</template>
					{{ t('files', 'Back') }}
				</NcActionButton>
				<NcActionSeparator />

				<!-- Submenu actions -->
				<NcActionButton v-for="action in enabledSubmenuActions[openedSubmenu?.id]"
					:key="action.id"
					:class="`files-list__row-actions-batch-${action.id}`"
					class="files-list__row-actions-batch--submenu"
					close-after-click
					:data-cy-files-list-selection-action="action.id"
					:aria-label="action.displayName(nodes, currentView) + ' ' + t('files', '(selected)') /** TRANSLATORS: Selected like 'selected files and folders' */"
					:title="action.title?.(nodes, currentView)"
					@click="onActionClick(action)">
					<template #icon>
						<NcLoadingIcon v-if="loading === action.id" :size="18" />
						<NcIconSvgWrapper v-else :svg="action.iconSvgInline(nodes, currentView)" />
					</template>
					{{ action.displayName(nodes, currentView) }}
				</NcActionButton>
			</template>
		</NcActions>
	</div>
</template>

<script lang="ts">
import type { FileAction, Node, View } from '@nextcloud/files'
import type { PropType } from 'vue'
import type { FileSource } from '../types'

import { getFileActions, NodeStatus, DefaultType } from '@nextcloud/files'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate } from '@nextcloud/l10n'
import { defineComponent } from 'vue'

import ArrowLeftIcon from 'vue-material-design-icons/ArrowLeft.vue'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

import { useRouteParameters } from '../composables/useRouteParameters.ts'
import { useFileListWidth } from '../composables/useFileListWidth.ts'
import { useActionsMenuStore } from '../store/actionsmenu.ts'
import { useFilesStore } from '../store/files.ts'
import { useSelectionStore } from '../store/selection.ts'
import actionsMixins from '../mixins/actionsMixin.ts'
import logger from '../logger.ts'

// The registered actions list
const actions = getFileActions()

export default defineComponent({
	name: 'FilesListTableHeaderActions',

	components: {
		ArrowLeftIcon,
		NcActions,
		NcActionButton,
		NcIconSvgWrapper,
		NcLoadingIcon,
	},

	mixins: [actionsMixins],

	props: {
		currentView: {
			type: Object as PropType<View>,
			required: true,
		},
		selectedNodes: {
			type: Array as PropType<FileSource[]>,
			default: () => ([]),
		},
	},

	setup() {
		const actionsMenuStore = useActionsMenuStore()
		const filesStore = useFilesStore()
		const selectionStore = useSelectionStore()
		const fileListWidth = useFileListWidth()
		const { directory } = useRouteParameters()

		const boundariesElement = document.getElementById('app-content-vue')

		return {
			directory,
			fileListWidth,

			actionsMenuStore,
			filesStore,
			selectionStore,

			boundariesElement,
		}
	},

	data() {
		return {
			loading: null,
		}
	},

	computed: {
		enabledFileActions(): FileAction[] {
			return actions
				// We don't handle renderInline actions in this component
				.filter(action => !action.renderInline)
				// We don't handle actions that are not visible
				.filter(action => action.default !== DefaultType.HIDDEN)
				// We allow top-level actions that have no execBatch method
				// but children actions always need to have it
				.filter(action => action.execBatch || !action.parent)
				// We filter out actions that are not enabled for the current selection
				.filter(action => !action.enabled || action.enabled(this.nodes, this.currentView))
				.sort((a, b) => (a.order || 0) - (b.order || 0))
		},

		/**
		 * Return the list of enabled actions that are
		 * allowed to be rendered inlined.
		 * This means that they are not within a menu, nor
		 * being the parent of submenu actions.
		 */
		enabledInlineActions(): FileAction[] {
			return this.enabledFileActions
				// Remove all actions that are not top-level actions
				.filter(action => action.parent === undefined)
				// Remove all actions that are not batch actions
				.filter(action => action.execBatch !== undefined)
				// Remove all top-menu entries
				.filter(action => !this.isValidMenu(action))
				// Return a maximum actions to fit the screen
				.slice(0, this.inlineActions)
		},

		/**
		 * Return the rest of enabled actions that are not
		 * rendered inlined.
		 */
		enabledMenuActions(): FileAction[] {
			// If we're in a submenu, only render the inline
			// actions before the filtered submenu
			if (this.openedSubmenu) {
				return this.enabledInlineActions
			}

			// We filter duplicates to prevent inline actions to be shown twice
			const actions = this.enabledFileActions.filter((value, index, self) => {
				return index === self.findIndex(action => action.id === value.id)
			})

			// Generate list of all top-level actions ids
			const childrenActionsIds = actions
				.filter(action => action.parent)
				// Filter out all actions that are not batch actions
				.filter(action => action.execBatch)
				.map(action => action.parent) as string[]

			const menuActions = actions
				.filter(action => {
					// If the action is not a batch action, we need
					// to make sure it's a top-level parent entry
					// and that we have some children actions bound to it
					if (!action.execBatch) {
						return childrenActionsIds.includes(action.id)
					}

					// Rendering second-level actions is done in the template
					// when openedSubmenu is set.
					if (action.parent) {
						return false
					}

					return true
				})
				.filter(action => !this.enabledInlineActions.includes(action))

			// Make sure we render the inline actions first
			// and then the rest of the actions.
			// We do NOT want nested actions to be rendered inlined
			return [...this.enabledInlineActions, ...menuActions]
		},

		nodes() {
			return this.selectedNodes
				.map(source => this.getNode(source))
				.filter(Boolean) as Node[]
		},

		areSomeNodesLoading() {
			return this.nodes.some(node => node.status === NodeStatus.LOADING)
		},

		openedMenu: {
			get() {
				return this.actionsMenuStore.opened === 'global'
			},
			set(opened) {
				this.actionsMenuStore.opened = opened ? 'global' : null
			},
		},

		inlineActions() {
			if (this.fileListWidth < 512) {
				return 0
			}
			if (this.fileListWidth < 768) {
				return 1
			}
			if (this.fileListWidth < 1024) {
				return 2
			}
			return 3
		},
	},

	methods: {
		/**
		 * Get a cached note from the store
		 *
		 * @param source The source of the node to get
		 */
		getNode(source: string): Node|undefined {
			return this.filesStore.getNode(source)
		},

		async onActionClick(action) {
			// If the action is a submenu, we open it
			if (this.enabledSubmenuActions[action.id]) {
				this.openedSubmenu = action
				return
			}

			let displayName = action.id
			try {
				displayName = action.displayName(this.nodes, this.currentView)
			} catch (error) {
				logger.error('Error while getting action display name', { action, error })
			}

			const selectionSources = this.selectedNodes
			try {
				// Set loading markers
				this.loading = action.id
				this.nodes.forEach(node => {
					this.$set(node, 'status', NodeStatus.LOADING)
				})

				// Dispatch action execution
				const results = await action.execBatch(this.nodes, this.currentView, this.directory)

				// Check if all actions returned null
				if (!results.some(result => result !== null)) {
					// If the actions returned null, we stay silent
					this.selectionStore.reset()
					return
				}

				// Handle potential failures
				if (results.some(result => result === false)) {
					// Remove the failed ids from the selection
					const failedSources = selectionSources
						.filter((source, index) => results[index] === false)
					this.selectionStore.set(failedSources)

					if (results.some(result => result === null)) {
						// If some actions returned null, we assume that the dev
						// is handling the error messages and we stay silent
						return
					}

					showError(this.t('files', '"{displayName}" failed on some elements', { displayName }))
					return
				}

				// Show success message and clear selection
				showSuccess(this.t('files', '"{displayName}" batch action executed successfully', { displayName }))
				this.selectionStore.reset()
			} catch (e) {
				logger.error('Error while executing action', { action, e })
				showError(this.t('files', '"{displayName}" action failed', { displayName }))
			} finally {
				// Remove loading markers
				this.loading = null
				this.nodes.forEach(node => {
					this.$set(node, 'status', undefined)
				})
			}
		},

		t: translate,
	},
})
</script>

<style scoped lang="scss">
.files-list__row-actions-batch {
	flex: 1 1 100% !important;
	max-width: 100%;
}
</style>
