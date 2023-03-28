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
	<th class="files-list__column files-list__row-actions-batch" colspan="2">
		<NcActions ref="actionsMenu"
			:disabled="!!loading"
			:force-title="true"
			:inline="3">
			<NcActionButton v-for="action in enabledActions"
				:key="action.id"
				:class="'files-list__row-actions-batch-' + action.id"
				@click="onActionClick(action)">
				<template #icon>
					<NcLoadingIcon v-if="loading === action.id" :size="18" />
					<CustomSvgIconRender v-else :svg="action.iconSvgInline(nodes, currentView)" />
				</template>
				{{ action.displayName(nodes, currentView) }}
			</NcActionButton>
		</NcActions>
	</th>
</template>

<script lang="ts">
import { showError, showSuccess } from '@nextcloud/dialogs'
import { getFileActions } from '@nextcloud/files'
import { translate } from '@nextcloud/l10n'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import Vue from 'vue'

import { useFilesStore } from '../store/files'
import { useSelectionStore } from '../store/selection'
import CustomSvgIconRender from './CustomSvgIconRender.vue'
import logger from '../logger.js'

// The registered actions list
const actions = getFileActions()

export default Vue.extend({
	name: 'FilesListActionsHeader',

	components: {
		CustomSvgIconRender,
		NcActions,
		NcActionButton,
		NcLoadingIcon,
	},

	props: {
		currentView: {
			type: Object,
			required: true,
		},
		selectedNodes: {
			type: Array,
			default: () => ([]),
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
			loading: null,
		}
	},

	computed: {
		enabledActions() {
			return actions
				.filter(action => action.execBatch)
				.filter(action => !action.enabled || action.enabled(this.nodes, this.currentView))
				.sort((a, b) => (a.order || 0) - (b.order || 0))
		},

		nodes() {
			return this.selectedNodes
				.map(fileid => this.getNode(fileid))
				.filter(node => node)
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
				this.loading = action.id
				const results = await action.execBatch(this.nodes, this.currentView)

				if (results.some(result => result !== true)) {
					// Remove the failed ids from the selection
					const failedIds = selectionIds
						.filter((fileid, index) => results[index] !== true)
					this.selectionStore.set(failedIds)

					showError(this.t('files', '"{displayName}" failed on some elements ', { displayName }))
					return
				}

				// Show success message and clear selection
				showSuccess(this.t('files', '"{displayName}" batch action successfully executed', { displayName }))
				this.selectionStore.reset()
			} catch (e) {
				logger.error('Error while executing action', { action, e })
				showError(this.t('files', 'Error while executing action "{displayName}"', { displayName }))
			} finally {
				this.loading = null
			}
		},

		t: translate,
	},
})
</script>

<style scoped lang="scss">
.files-list__row-actions-batch {
	flex: 1 1 100% !important;

	// Remove when https://github.com/nextcloud/nextcloud-vue/pull/3936 is merged
	::v-deep .button-vue__wrapper {
		width: 100%;
		span.button-vue__text {
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}
	}
}
</style>
