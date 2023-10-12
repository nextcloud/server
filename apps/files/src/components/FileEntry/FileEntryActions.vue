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
		<NcActions v-if="visible"
			ref="actionsMenu"
			:boundaries-element="getBoundariesElement()"
			:container="getBoundariesElement()"
			:disabled="isLoading"
			:force-name="true"
			:force-menu="enabledInlineActions.length === 0 /* forceMenu only if no inline actions */"
			:inline="enabledInlineActions.length"
			:open.sync="openedMenu">
			<NcActionButton v-for="action in enabledMenuActions"
				:key="action.id"
				:class="'files-list__row-action-' + action.id"
				:close-after-click="true"
				:data-cy-files-list-row-action="action.id"
				:title="action.title?.([source], currentView)"
				@click="onActionClick(action)">
				<template #icon>
					<NcLoadingIcon v-if="loading === action.id" :size="18" />
					<NcIconSvgWrapper v-else :svg="action.iconSvgInline([source], currentView)" />
				</template>
				{{ actionDisplayName(action) }}
			</NcActionButton>
		</NcActions>
	</td>
</template>

<script lang="ts">
import { DefaultType, FileAction, Folder, Node, NodeStatus, View, getFileActions } from '@nextcloud/files'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n';
import Vue, { PropType } from 'vue'

import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

import logger from '../../logger.js'

// The registered actions list
const actions = getFileActions()

export default Vue.extend({
	name: 'FileEntryActions',

	components: {
		NcActionButton,
		NcActions,
		NcIconSvgWrapper,
		NcLoadingIcon,
	},

	props: {
		filesListWidth: {
			type: Number,
			required: true,
		},
		loading: {
			type: String,
			required: true,
		},
		opened: {
			type: Boolean,
			default: false,
		},
		source: {
			type: Object as PropType<Node>,
			required: true,
		},
		visible: {
			type: Boolean,
			default: false,
		},
	},

	setup() {
		return {
		}
	},

	computed: {
		currentView(): View {
			return this.$navigation.active as View
		},
		isLoading() {
			return this.source.status === NodeStatus.LOADING
		},

		// Sorted actions that are enabled for this node
		enabledActions() {
			if (this.source.attributes.failed) {
				return []
			}

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

		// Enabled action that are displayed inline with a custom render function
		enabledRenderActions() {
			if (!this.visible) {
				return []
			}
			return this.enabledActions.filter(action => typeof action.renderInline === 'function')
		},

		// Default actions
		enabledDefaultActions() {
			return this.enabledActions.filter(action => !!action?.default)
		},

		// Actions shown in the menu
		enabledMenuActions() {
			return [
				// Showing inline first for the NcActions inline prop
				...this.enabledInlineActions,
				// Then the rest
				...this.enabledActions.filter(action => action.default !== DefaultType.HIDDEN && typeof action.renderInline !== 'function'),
			].filter((value, index, self) => {
				// Then we filter duplicates to prevent inline actions to be shown twice
				return index === self.findIndex(action => action.id === value.id)
			})
		},

		openedMenu: {
			get() {
				return this.opened
			},
			set(value) {
				this.$emit('update:opened', value)
			},
		},
	},

	methods: {
		/**
		 * Making this a function in case the files-list
		 * reference changes in the future. That way we're
		 * sure there is one at the time we call it.
		 */
		getBoundariesElement() {
			return document.querySelector('.app-content > table.files-list')
		},

		actionDisplayName(action: FileAction) {
			if (this.filesListWidth < 768 && action.inline && typeof action.title === 'function') {
				// if an inline action is rendered in the menu for
				// lack of space we use the title first if defined
				const title = action.title([this.source], this.currentView)
				if (title) return title
			}
			return action.displayName([this.source], this.currentView)
		},

		async onActionClick(action) {
			const displayName = action.displayName([this.source], this.currentView)
			try {
				// Set the loading marker
				this.$emit('update:loading', action.id)
				Vue.set(this.source, 'status', NodeStatus.LOADING)

				const success = await action.exec(this.source, this.currentView, this.currentDir)

				// If the action returns null, we stay silent
				if (success === null) {
					return
				}

				if (success) {
					showSuccess(t('files', '"{displayName}" action executed successfully', { displayName }))
					return
				}
				showError(t('files', '"{displayName}" action failed', { displayName }))
			} catch (e) {
				logger.error('Error while executing action', { action, e })
				showError(t('files', '"{displayName}" action failed', { displayName }))
			} finally {
				// Reset the loading marker
				this.$emit('update:loading', '')
				Vue.set(this.source, 'status', undefined)
			}
		},
		execDefaultAction(event) {
			if (this.enabledDefaultActions.length > 0) {
				event.preventDefault()
				event.stopPropagation()
				// Execute the first default action if any
				this.enabledDefaultActions[0].exec(this.source, this.currentView, this.currentDir)
			}
		},

		t,
	},
})
</script>
