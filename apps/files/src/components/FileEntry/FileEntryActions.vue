<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<td class="files-list__row-actions"
		data-cy-files-list-row-actions>
		<!-- Render actions -->
		<CustomElementRender v-for="action in enabledRenderActions"
			:key="action.id"
			:class="'files-list__row-action-' + action.id"
			:current-view="currentView"
			:render="action.renderInline"
			:source="source"
			class="files-list__row-action--inline" />

		<!-- Menu actions -->
		<NcActions ref="actionsMenu"
			:boundaries-element="getBoundariesElement"
			:container="getBoundariesElement"
			:force-name="true"
			type="tertiary"
			:force-menu="enabledInlineActions.length === 0 /* forceMenu only if no inline actions */"
			:inline="enabledInlineActions.length"
			:open.sync="openedMenu"
			@close="openedSubmenu = null">
			<!-- Default actions list-->
			<NcActionButton v-for="action in enabledMenuActions"
				:key="action.id"
				:ref="`action-${action.id}`"
				:class="{
					[`files-list__row-action-${action.id}`]: true,
					[`files-list__row-action--menu`]: isMenu(action.id)
				}"
				:close-after-click="!isMenu(action.id)"
				:data-cy-files-list-row-action="action.id"
				:is-menu="isMenu(action.id)"
				:aria-label="action.title?.([source], currentView)"
				:title="action.title?.([source], currentView)"
				@click="onActionClick(action)">
				<template #icon>
					<NcLoadingIcon v-if="isLoadingAction(action)" :size="18" />
					<NcIconSvgWrapper v-else :svg="action.iconSvgInline([source], currentView)" />
				</template>
				{{ mountType === 'shared' && action.id === 'sharing-status' ? '' : actionDisplayName(action) }}
			</NcActionButton>

			<!-- Submenu actions list-->
			<template v-if="openedSubmenu && enabledSubmenuActions[openedSubmenu?.id]">
				<!-- Back to top-level button -->
				<NcActionButton class="files-list__row-action-back" @click="onBackToMenuClick(openedSubmenu)">
					<template #icon>
						<ArrowLeftIcon />
					</template>
					{{ t('files', 'Back') }}
				</NcActionButton>
				<NcActionSeparator />

				<!-- Submenu actions -->
				<NcActionButton v-for="action in enabledSubmenuActions[openedSubmenu?.id]"
					:key="action.id"
					:class="`files-list__row-action-${action.id}`"
					class="files-list__row-action--submenu"
					close-after-click
					:data-cy-files-list-row-action="action.id"
					:title="action.title?.([source], currentView)"
					@click="onActionClick(action)">
					<template #icon>
						<NcLoadingIcon v-if="isLoadingAction(action)" :size="18" />
						<NcIconSvgWrapper v-else :svg="action.iconSvgInline([source], currentView)" />
					</template>
					{{ actionDisplayName(action) }}
				</NcActionButton>
			</template>
		</NcActions>
	</td>
</template>

<script lang="ts">
import type { PropType } from 'vue'
import type { FileAction, Node } from '@nextcloud/files'

import { DefaultType, NodeStatus } from '@nextcloud/files'
import { defineComponent, inject } from 'vue'
import { translate as t } from '@nextcloud/l10n'

import { useHotKey } from '@nextcloud/vue/dist/Composables/useHotKey.js'
import ArrowLeftIcon from 'vue-material-design-icons/ArrowLeft.vue'
import CustomElementRender from '../CustomElementRender.vue'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionSeparator from '@nextcloud/vue/dist/Components/NcActionSeparator.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

import { executeAction } from '../../utils/actionUtils.ts'
import { useActiveStore } from '../../store/active.ts'
import { useFileListWidth } from '../../composables/useFileListWidth.ts'
import { useNavigation } from '../../composables/useNavigation'
import { useRouteParameters } from '../../composables/useRouteParameters.ts'
import logger from '../../logger.ts'

export default defineComponent({
	name: 'FileEntryActions',

	components: {
		ArrowLeftIcon,
		CustomElementRender,
		NcActionButton,
		NcActions,
		NcActionSeparator,
		NcIconSvgWrapper,
		NcLoadingIcon,
	},

	props: {
		opened: {
			type: Boolean,
			default: false,
		},
		source: {
			type: Object as PropType<Node>,
			required: true,
		},
		gridMode: {
			type: Boolean,
			default: false,
		},
	},

	setup() {
		// The file list is guaranteed to be only shown with active view - thus we can set the `loaded` flag
		const { currentView } = useNavigation(true)
		const { directory: currentDir } = useRouteParameters()

		const activeStore = useActiveStore()
		const filesListWidth = useFileListWidth()
		const enabledFileActions = inject<FileAction[]>('enabledFileActions', [])
		return {
			activeStore,
			currentDir,
			currentView,
			enabledFileActions,
			filesListWidth,
			t,
		}
	},

	data() {
		return {
			openedSubmenu: null as FileAction | null,
		}
	},

	computed: {
		isActive() {
			return this.activeStore?.activeNode?.source === this.source.source
		},

		isLoading() {
			return this.source.status === NodeStatus.LOADING
		},

		// Enabled action that are displayed inline
		enabledInlineActions() {
			if (this.filesListWidth < 768 || this.gridMode) {
				return []
			}
			return this.enabledFileActions.filter(action => {
				try {
					return action?.inline?.(this.source, this.currentView)
				} catch (error) {
					logger.error('Error while checking if action is inline', { action, error })
					return false
				}
			})
		},

		// Enabled action that are displayed inline with a custom render function
		enabledRenderActions() {
			if (this.gridMode) {
				return []
			}
			return this.enabledFileActions.filter(action => typeof action.renderInline === 'function')
		},

		// Actions shown in the menu
		enabledMenuActions() {
			// If we're in a submenu, only render the inline
			// actions before the filtered submenu
			if (this.openedSubmenu) {
				return this.enabledInlineActions
			}

			const actions = [
				// Showing inline first for the NcActions inline prop
				...this.enabledInlineActions,
				// Then the rest
				...this.enabledFileActions.filter(action => action.default !== DefaultType.HIDDEN && typeof action.renderInline !== 'function'),
			].filter((value, index, self) => {
				// Then we filter duplicates to prevent inline actions to be shown twice
				return index === self.findIndex(action => action.id === value.id)
			})

			// Generate list of all top-level actions ids
			const topActionsIds = actions.filter(action => !action.parent).map(action => action.id) as string[]

			// Filter actions that are not top-level AND have a valid parent
			return actions.filter(action => !(action.parent && topActionsIds.includes(action.parent)))
		},

		enabledSubmenuActions() {
			return this.enabledFileActions
				.filter(action => action.parent)
				.reduce((arr, action) => {
					if (!arr[action.parent!]) {
						arr[action.parent!] = []
					}
					arr[action.parent!].push(action)
					return arr
				}, {} as Record<string, FileAction[]>)
		},

		openedMenu: {
			get() {
				return this.opened
			},
			set(value) {
				this.$emit('update:opened', value)
			},
		},

		/**
		 * Making this a function in case the files-list
		 * reference changes in the future. That way we're
		 * sure there is one at the time we call it.
		 */
		getBoundariesElement() {
			return document.querySelector('.app-content > .files-list')
		},

		mountType() {
			return this.source.attributes['mount-type']
		},
	},

	watch: {
		// Close any submenu when the menu is closed
		openedMenu() {
			this.openedSubmenu = null
		},
	},

	created() {
		useHotKey('Escape', this.onKeyDown, {
			stop: true,
			prevent: true,
		})

		useHotKey('a', this.onKeyDown, {
			stop: true,
			prevent: true,
		})
	},

	methods: {
		actionDisplayName(action: FileAction) {
			try {
				if ((this.gridMode || (this.filesListWidth < 768 && action.inline)) && typeof action.title === 'function') {
					// if an inline action is rendered in the menu for
					// lack of space we use the title first if defined
					const title = action.title([this.source], this.currentView)
					if (title) return title
				}
				return action.displayName([this.source], this.currentView)
			} catch (error) {
				logger.error('Error while getting action display name', { action, error })
				// Not ideal, but better than nothing
				return action.id
			}
		},

		isLoadingAction(action: FileAction) {
			if (!this.isActive) {
				return false
			}
			return this.activeStore?.activeAction?.id === action.id
		},

		async onActionClick(action, isSubmenu = false) {
			// If the action is a submenu, we open it
			if (this.enabledSubmenuActions[action.id]) {
				this.openedSubmenu = action
				return
			}

			// Make sure we set the node as active
			this.activeStore.setActiveNode(this.source)

			// Execute the action
			await executeAction(action)

			// If that was a submenu, we just go back after the action
			if (isSubmenu) {
				this.openedSubmenu = null
			}
		},

		isMenu(id: string) {
			return this.enabledSubmenuActions[id]?.length > 0
		},

		async onBackToMenuClick(action: FileAction) {
			this.openedSubmenu = null
			// Wait for first render
			await this.$nextTick()

			// Focus the previous menu action button
			this.$nextTick(() => {
				// Focus the action button
				const menuAction = this.$refs[`action-${action.id}`]?.[0]
				if (menuAction) {
					menuAction.$el.querySelector('button')?.focus()
				}
			})
		},

		onKeyDown(event: KeyboardEvent) {
			// Don't react to the event if the file row is not active
			if (!this.isActive) {
				return
			}

			// ESC close the action menu if opened
			if (event.key === 'Escape' && this.openedMenu) {
				this.openedMenu = false
			}

			// a open the action menu
			if (event.key === 'a' && !this.openedMenu) {
				this.openedMenu = true
			}
		},
	},
})
</script>

<style lang="scss">
// Allow right click to define the position of the menu
// only if defined
main.app-content[style*="mouse-pos-x"] .v-popper__popper {
	transform: translate3d(var(--mouse-pos-x), var(--mouse-pos-y), 0px) !important;

	// If the menu is too close to the bottom, we move it up
	&[data-popper-placement="top"] {
		// 34px added to align with the top of the cursor
		transform: translate3d(var(--mouse-pos-x), calc(var(--mouse-pos-y) - 50vh + 34px), 0px) !important;
	}
	// Hide arrow if floating
	.v-popper__arrow-container {
		display: none;
	}
}
</style>

<style lang="scss" scoped>
:deep(.button-vue--icon-and-text, .files-list__row-action-sharing-status) {
	.button-vue__text {
		color: var(--color-primary-element);
	}
	.button-vue__icon {
		color: var(--color-primary-element);
	}
}
</style>
