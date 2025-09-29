<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="system-tags">
		<NcLoadingIcon v-if="loadingTags"
			:name="t('systemtags', 'Loading collaborative tags …')"
			:size="32" />

		<NcSelectTags v-show="!loadingTags"
			class="system-tags__select"
			:input-label="t('systemtags', 'Search or create collaborative tags')"
			:placeholder="t('systemtags', 'Collaborative tags …')"
			:options="sortedTags"
			:value="selectedTags"
			:create-option="createOption"
			:disabled="disabled"
			:taggable="true"
			:passthru="true"
			:fetch-tags="false"
			:loading="loading"
			@input="handleInput"
			@option:selected="handleSelect"
			@option:created="handleCreate"
			@option:deselected="handleDeselect">
			<template #no-options>
				{{ t('systemtags', 'No tags to select, type to create a new tag') }}
			</template>
		</NcSelectTags>
	</div>
</template>

<script lang="ts">
// FIXME Vue TypeScript ESLint errors
/* eslint-disable */
import type { Node } from '@nextcloud/files'
import type { Tag, TagWithId } from '../types.js'

import Vue from 'vue'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcSelectTags from '@nextcloud/vue/components/NcSelectTags'

import { emit, subscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

import { defaultBaseTag } from '../utils.js'
import { fetchLastUsedTagIds, fetchTags } from '../services/api.js'
import { fetchNode } from '../../../files/src/services/WebdavClient.js'
import {
	createTagForFile,
	deleteTagForFile,
	fetchTagsForFile,
	setTagForFile,
} from '../services/files.js'
import logger from '../logger.js'


export default Vue.extend({
	name: 'SystemTags',

	components: {
		NcLoadingIcon,
		NcSelectTags,
	},

	props: {
		fileId: {
			type: Number,
			required: true,
		},
		disabled: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			sortedTags: [] as TagWithId[],
			selectedTags: [] as TagWithId[],
			loadingTags: false,
			loading: false,
		}
	},

	async created() {
		try {
			const tags = await fetchTags()
			const lastUsedOrder = await fetchLastUsedTagIds()

			const lastUsedTags: TagWithId[] = []
			const remainingTags: TagWithId[] = []

			for (const tag of tags) {
				if (lastUsedOrder.includes(tag.id)) {
					lastUsedTags.push(tag)
					continue
				}
				remainingTags.push(tag)
			}

			const sortByLastUsed = (a: TagWithId, b: TagWithId) => {
				return lastUsedOrder.indexOf(a.id) - lastUsedOrder.indexOf(b.id)
			}
			lastUsedTags.sort(sortByLastUsed)

			this.sortedTags = [...lastUsedTags, ...remainingTags]
		} catch (error) {
			showError(t('systemtags', 'Failed to load tags'))
		}
	},

	watch: {
		fileId: {
			immediate: true,
			async handler() {
				this.loadingTags = true
				try {
					this.selectedTags = await fetchTagsForFile(this.fileId)
				} catch (error) {
					showError(t('systemtags', 'Failed to load selected tags'))
				}
				this.loadingTags = false
			},
		},
	},

	mounted() {
		subscribe('systemtags:node:updated', this.onTagUpdated)
	},

	methods: {
		t,

		createOption(newDisplayName: string): Tag {
			for (const tag of this.sortedTags) {
				const { id, displayName, ...baseTag } = tag
				if (
					displayName === newDisplayName
					&& Object.entries(baseTag)
						.every(([key, value]) => defaultBaseTag[key] === value)
				) {
					// Return existing tag to prevent vue-select from thinking the tags are different and showing duplicate options
					return tag
				}
			}
			return {
				...defaultBaseTag,
				displayName: newDisplayName,
			}
		},

		handleInput(selectedTags: Tag[]) {
			/**
			 * Filter out tags with no id to prevent duplicate selected options
			 *
			 * Created tags are added programmatically by `handleCreate()` with
			 * their respective ids returned from the server
			 */
			this.selectedTags = selectedTags.filter(selectedTag => Boolean(selectedTag.id)) as TagWithId[]
		},

		async handleSelect(tags: Tag[]) {
			const lastTag = tags[tags.length - 1]
			if (!lastTag.id) {
				// Ignore created tags handled by `handleCreate()`
				return
			}
			const selectedTag = lastTag as TagWithId
			this.loading = true
			try {
				await setTagForFile(selectedTag, this.fileId)
				const sortToFront = (a: TagWithId, b: TagWithId) => {
					if (a.id === selectedTag.id) {
						return -1
					} else if (b.id === selectedTag.id) {
						return 1
					}
					return 0
				}
				this.sortedTags.sort(sortToFront)
			} catch (error) {
				showError(t('systemtags', 'Failed to select tag'))
			}
			this.loading = false

			this.updateAndDispatchNodeTagsEvent(this.fileId)
		},

		async handleCreate(tag: Tag) {
			this.loading = true
			try {
				const id = await createTagForFile(tag, this.fileId)
				const createdTag = { ...tag, id }
				this.sortedTags.unshift(createdTag)
				this.selectedTags.push(createdTag)
			} catch (error) {
				const systemTagsCreationRestrictedToAdmin = loadState<true|false>('settings', 'restrictSystemTagsCreationToAdmin', false) === true
				if (systemTagsCreationRestrictedToAdmin) {
					showError(t('systemtags', 'System admin disabled tag creation. You can only use existing ones.'))
					return
				}
				showError(t('systemtags', 'Failed to create tag'))
			}
			this.loading = false

			this.updateAndDispatchNodeTagsEvent(this.fileId)
		},

		async handleDeselect(tag: TagWithId) {
			this.loading = true
			try {
				await deleteTagForFile(tag, this.fileId)
			} catch (error) {
				showError(t('systemtags', 'Failed to delete tag'))
			}
			this.loading = false

			this.updateAndDispatchNodeTagsEvent(this.fileId)
		},

		async onTagUpdated(node: Node) {
			if (node.fileid !== this.fileId) {
				return
			}

			this.loadingTags = true
			try {
				this.selectedTags = await fetchTagsForFile(this.fileId)
			} catch (error) {
				showError(t('systemtags', 'Failed to load selected tags'))
			}

			this.loadingTags = false
		},

		async updateAndDispatchNodeTagsEvent(fileId: number) {
			const path = window.OCA?.Files?.Sidebar?.file || ''
			try {
				const node = await fetchNode(path)
				if (node) {
					emit('systemtags:node:updated', node)
				}
			} catch (error) {
				logger.error('Failed to fetch node for system tags update', { error, fileId })
			}
		},
	},
})
</script>

<style lang="scss" scoped>
.system-tags {
	display: flex;
	flex-direction: column;

	// Fix issue with AppSidebar styles overwriting NcSelect styles
	&__select {
		width: 100%;
		:deep {
			.vs__deselect {
				padding: 0;
			}
		}
	}
}
</style>
