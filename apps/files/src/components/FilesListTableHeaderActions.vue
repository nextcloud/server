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
	<div class="files-list__column files-list__row-actions-batch">
		<NcActions ref="actionsMenu"
			:disabled="!!loading || areSomeNodesLoading"
			:force-name="true"
			:inline="inlineActions"
			:menu-name="inlineActions <= 1 ? t('files', 'Actions') : null"
			:open.sync="openedMenu">
			<NcActionButton v-for="action in enabledActions"
				:key="action.id"
				:class="'files-list__row-actions-batch-' + action.id"
				@click="onActionClick(action)">
				<template #icon>
					<NcLoadingIcon v-if="loading === action.id" :size="18" />
					<NcIconSvgWrapper v-else :svg="action.iconSvgInline(nodes, currentView)" />
				</template>
				{{ action.displayName(nodes, currentView) }}
			</NcActionButton>
		</NcActions>
	</div>
</template>

<script lang="ts">
import { Node, NodeStatus, View, getFileActions } from '@nextcloud/files'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate } from '@nextcloud/l10n'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import Vue, { defineComponent, type PropType } from 'vue'

import { useActionsMenuStore } from '../store/actionsmenu.ts'
import { useFilesStore } from '../store/files.ts'
import { useSelectionStore } from '../store/selection.ts'
import filesListWidthMixin from '../mixins/filesListWidth.ts'
import logger from '../logger.js'
import type { FileId } from '../types'

// The registered actions list
const actions = getFileActions()

export default defineComponent({
	name: 'FilesListTableHeaderActions',

	components: {
		NcActions,
		NcActionButton,
		NcIconSvgWrapper,
		NcLoadingIcon,
	},

	mixins: [
		filesListWidthMixin,
	],

	props: {
		currentView: {
			type: Object as PropType<View>,
			required: true,
		},
		selectedNodes: {
			type: Array as PropType<FileId[]>,
			default: () => ([]),
		},
	},

	setup() {
		const actionsMenuStore = useActionsMenuStore()
		const filesStore = useFilesStore()
		const selectionStore = useSelectionStore()
		return {
			actionsMenuStore,
			filesStore,
			selectionStore,
		}
	},

	data() {
		return {
			loading: null,
		}
	},

	computed: {
		dir() {
			// Remove any trailing slash but leave root slash
			return (this.$route?.query?.dir || '/').replace(/^(.+)\/$/, '$1')
		},
		enabledActions() {
			return actions
				.filter(action => action.execBatch)
				.filter(action => !action.enabled || action.enabled(this.nodes, this.currentView))
				.sort((a, b) => (a.order || 0) - (b.order || 0))
		},

		nodes() {
			return this.selectedNodes
				.map(fileid => this.getNode(fileid))
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
			if (this.filesListWidth < 512) {
				return 0
			}
			if (this.filesListWidth < 768) {
				return 1
			}
			if (this.filesListWidth < 1024) {
				return 2
			}
			return 3
		},
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

		async onActionClick(action) {
			const displayName = action.displayName(this.nodes, this.currentView)
			const selectionIds = this.selectedNodes
			try {
				// Set loading markers
				this.loading = action.id
				this.nodes.forEach(node => {
					Vue.set(node, 'status', NodeStatus.LOADING)
				})

				// Dispatch action execution
				const results = await action.execBatch(this.nodes, this.currentView, this.dir)

				// Check if all actions returned null
				if (!results.some(result => result !== null)) {
					// If the actions returned null, we stay silent
					this.selectionStore.reset()
					return
				}

				// Handle potential failures
				if (results.some(result => result === false)) {
					// Remove the failed ids from the selection
					const failedIds = selectionIds
						.filter((fileid, index) => results[index] === false)
					this.selectionStore.set(failedIds)

					if (results.some(result => result === null)) {
						// If some actions returned null, we assume that the dev
						// is handling the error messages and we stay silent
						return
					}

					showError(this.t('files', '"{displayName}" failed on some elements ', { displayName }))
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
					Vue.set(node, 'status', undefined)
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
