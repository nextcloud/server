<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog data-cy-systemtags-picker
		:name="t('systemtags', 'Manage tags')"
		:open="opened"
		class="systemtags-picker"
		close-on-click-outside
		out-transition
		@update:open="onCancel">
		<NcEmptyContent v-if="loading || done" :name="t('systemtags', 'Applying tags changes…')">
			<template #icon>
				<NcLoadingIcon v-if="!done" />
				<CheckIcon v-else fill-color="var(--color-success)" />
			</template>
		</NcEmptyContent>

		<template v-else>
			<!-- Search or create input -->
			<div class="systemtags-picker__create">
				<NcTextField :value.sync="input"
					:label="t('systemtags', 'Search or create tag')">
					<TagIcon :size="20" />
				</NcTextField>
				<NcButton>
					{{ t('systemtags', 'Create tag') }}
				</NcButton>
			</div>

			<!-- Tags list -->
			<div v-if="filteredTags.length > 0" class="systemtags-picker__tags">
				<NcCheckboxRadioSwitch v-for="tag in filteredTags"
					:key="tag.id"
					:label="tag.displayName"
					:checked="isChecked(tag)"
					:indeterminate="isIndeterminate(tag)"
					:disabled="!tag.canAssign"
					@update:checked="onCheckUpdate(tag, $event)">
					{{ formatTagName(tag) }}
				</NcCheckboxRadioSwitch>
			</div>
			<NcEmptyContent v-else :name="t('systemtags', 'No tags found')">
				<template #icon>
					<TagIcon />
				</template>
			</NcEmptyContent>

			<!-- Note -->
			<div class="systemtags-picker__note">
				<NcNoteCard v-if="!hasChanges" type="info">
					{{ t('systemtags', 'Select or create tags to apply to all selected files') }}
				</NcNoteCard>
				<NcNoteCard v-else type="info">
					<span v-html="statusMessage" />
				</NcNoteCard>
			</div>
		</template>

		<template #actions>
			<NcButton :disabled="loading || done" type="tertiary" @click="onCancel">
				{{ t('systemtags', 'Cancel') }}
			</NcButton>
			<NcButton :disabled="!hasChanges || loading || done" @click="onSubmit">
				{{ t('systemtags', 'Apply changes') }}
			</NcButton>
		</template>

		<!-- Chip html for v-html tag rendering -->
		<div v-show="false">
			<NcChip ref="chip"
				text="%s"
				type="primary"
				no-close />
		</div>
	</NcDialog>
</template>

<script lang="ts">
import type { Node } from '@nextcloud/files'
import type { PropType } from 'vue'
import type { TagWithId } from '../types'

import { defineComponent } from 'vue'
import { emit } from '@nextcloud/event-bus'
import { sanitize } from 'dompurify'
import { showError, showInfo } from '@nextcloud/dialogs'
import { getLanguage, t } from '@nextcloud/l10n'
import escapeHTML from 'escape-html'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcChip from '@nextcloud/vue/dist/Components/NcChip.js'
import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import TagIcon from 'vue-material-design-icons/Tag.vue'
import CheckIcon from 'vue-material-design-icons/CheckCircle.vue'

import { getNodeSystemTags, setNodeSystemTags } from '../utils'
import { getTagObjects, setTagObjects } from '../services/api'
import logger from '../services/logger'

type TagListCount = {
	string: number
}

export default defineComponent({
	name: 'SystemTagPicker',

	components: {
		CheckIcon,
		NcButton,
		NcCheckboxRadioSwitch,
		// eslint-disable-next-line vue/no-unused-components
		NcChip,
		NcDialog,
		NcEmptyContent,
		NcLoadingIcon,
		NcNoteCard,
		NcTextField,
		TagIcon,
	},

	props: {
		nodes: {
			type: Array as PropType<Node[]>,
			required: true,
		},

		tags: {
			type: Array as PropType<TagWithId[]>,
			default: () => [],
		},
	},

	setup() {
		return {
			emit,
			t,
		}
	},

	data() {
		return {
			done: false,
			loading: false,
			opened: true,

			input: '',
			tagList: {} as TagListCount,

			toAdd: [] as TagWithId[],
			toRemove: [] as TagWithId[],
		}
	},

	computed: {
		filteredTags(): TagWithId[] {
			if (this.input.trim() === '') {
				return this.tags
			}

			return this.tags
				.filter(tag => tag.displayName.normalize().includes(this.input.normalize()))
		},

		hasChanges(): boolean {
			return this.toAdd.length > 0 || this.toRemove.length > 0
		},

		statusMessage(): string {
			if (this.toAdd.length === 0 && this.toRemove.length === 0) {
				return ''
			}

			if (this.toAdd.length === 1 && this.toRemove.length === 1) {
				return t('systemtags', '{tag1} will be set and {tag2} will be removed from {count} files.', {
					tag1: this.formatTagChip(this.toAdd[0]),
					tag2: this.formatTagChip(this.toRemove[0]),
					count: this.nodes.length,
				}, undefined, { escape: false })
			}

			const tagsAdd = this.toAdd.map(this.formatTagChip)
			const lastTagAdd = tagsAdd.pop() as string
			const tagsRemove = this.toRemove.map(this.formatTagChip)
			const lastTagRemove = tagsRemove.pop() as string

			const addStringSingular = t('systemtags', '{tag} will be set to {count} files.', {
				tag: lastTagAdd,
				count: this.nodes.length,
			}, undefined, { escape: false })

			const removeStringSingular = t('systemtags', '{tag} will be removed from {count} files.', {
				tag: lastTagRemove,
				count: this.nodes.length,
			}, undefined, { escape: false })

			const addStringPlural = t('systemtags', '{tags} and {lastTag} will be set to {count} files.', {
				tags: tagsAdd.join(', '),
				lastTag: lastTagAdd,
				count: this.nodes.length,
			}, undefined, { escape: false })

			const removeStringPlural = t('systemtags', '{tags} and {lastTag} will be removed from {count} files.', {
				tags: tagsRemove.join(', '),
				lastTag: lastTagRemove,
				count: this.nodes.length,
			}, undefined, { escape: false })

			// Singular
			if (this.toAdd.length === 1 && this.toRemove.length === 0) {
				return addStringSingular
			}
			if (this.toAdd.length === 0 && this.toRemove.length === 1) {
				return removeStringSingular
			}

			// Plural
			if (this.toAdd.length > 1 && this.toRemove.length === 0) {
				return addStringPlural
			}
			if (this.toAdd.length === 0 && this.toRemove.length > 1) {
				return removeStringPlural
			}

			// Mixed
			if (this.toAdd.length > 1 && this.toRemove.length === 1) {
				return `${addStringPlural}<br>${removeStringSingular}`
			}
			if (this.toAdd.length === 1 && this.toRemove.length > 1) {
				return `${addStringSingular}<br>${removeStringPlural}`
			}

			// Both plural
			return `${addStringPlural}<br>${removeStringPlural}`
		},
	},

	beforeMount() {
		// Efficient way of counting tags and their occurrences
		this.tagList = this.nodes.reduce((acc: TagListCount, node: Node) => {
			const tags = getNodeSystemTags(node) || []
			tags.forEach(tag => {
				acc[tag] = (acc[tag] || 0) + 1
			})
			return acc
		}, {} as TagListCount) as TagListCount
	},

	methods: {
		// Format & sanitize a tag chip for v-html tag rendering
		formatTagChip(tag: TagWithId): string {
			const chip = this.$refs.chip as NcChip
			const chipHtml = chip.$el.outerHTML
			return chipHtml.replace('%s', escapeHTML(sanitize(tag.displayName)))
		},

		formatTagName(tag: TagWithId): string {
			if (tag.userVisible) {
				return t('systemtags', '{displayName} (hidden)', { displayName: tag.displayName })
			}

			if (tag.userAssignable) {
				return t('systemtags', '{displayName} (restricted)', { displayName: tag.displayName })
			}

			return tag.displayName
		},

		isChecked(tag: TagWithId): boolean {
			return tag.displayName in this.tagList
				&& this.tagList[tag.displayName] === this.nodes.length
		},

		isIndeterminate(tag: TagWithId): boolean {
			return tag.displayName in this.tagList
				&& this.tagList[tag.displayName] !== 0
				&& this.tagList[tag.displayName] !== this.nodes.length
		},

		onCheckUpdate(tag: TagWithId, checked: boolean) {
			if (checked) {
				this.toAdd.push(tag)
				this.toRemove = this.toRemove.filter(search => search.id !== tag.id)
				this.tagList[tag.displayName] = this.nodes.length
			} else {
				this.toRemove.push(tag)
				this.toAdd = this.toAdd.filter(search => search.id !== tag.id)
				this.tagList[tag.displayName] = 0
			}
		},

		async onSubmit() {
			this.loading = true
			logger.debug('Applying tags', {
				toAdd: this.toAdd,
				toRemove: this.toRemove,
			})

			try {
				// Add tags
				for (const tag of this.toAdd) {
					const { etag, objects } = await getTagObjects(tag, 'files')

					// Create a new list of ids in one pass
					const ids = [...new Set([
						...objects.map(obj => obj.id).filter(Boolean),
						...this.nodes.map(node => node.fileid).filter(Boolean),
					])] as number[]

					// Set tags
					await setTagObjects(tag, 'files', ids.map(id => ({ id, type: 'files' })), etag)
				}

				// Remove tags
				for (const tag of this.toRemove) {
					const { etag, objects } = await getTagObjects(tag, 'files')

					// Get file IDs from the nodes array just once
					const nodeFileIds = new Set(this.nodes.map(node => node.fileid))

					// Create a filtered and deduplicated list of ids in one pass
					const ids = objects
						.map(obj => obj.id)
						.filter((id, index, self) => !nodeFileIds.has(id) && self.indexOf(id) === index)

					// Set tags
					await setTagObjects(tag, 'files', ids.map(id => ({ id, type: 'files' })), etag)
				}
			} catch (error) {
				logger.error('Failed to apply tags', { error })
				showError(t('systemtags', 'Failed to apply tags changes'))
				this.loading = false
				return
			}

			const nodes = [] as Node[]

			// Update nodes
			this.toAdd.forEach(tag => {
				this.nodes.forEach(node => {
					const tags = [...(getNodeSystemTags(node) || []), tag.displayName]
						.sort((a, b) => a.localeCompare(b, getLanguage(), { ignorePunctuation: true }))
					setNodeSystemTags(node, tags)
					nodes.push(node)
				})
			})

			this.toRemove.forEach(tag => {
				this.nodes.forEach(node => {
					const tags = [...(getNodeSystemTags(node) || [])].filter(t => t !== tag.displayName)
						.sort((a, b) => a.localeCompare(b, getLanguage(), { ignorePunctuation: true }))
					setNodeSystemTags(node, tags)
					nodes.push(node)
				})
			})

			// trigger update event
			nodes.forEach(node => emit('systemtags:node:updated', node))

			this.done = true
			this.loading = false
			setTimeout(() => {
				this.opened = false
				this.$emit('close', null)
			}, 2000)
		},

		onCancel() {
			this.opened = false
			showInfo(t('systemtags', 'File tags modification canceled'))
			this.$emit('close', null)
		},
	},
})
</script>

<style scoped lang="scss">
// Common sticky properties
.systemtags-picker__create,
.systemtags-picker__note {
	position: sticky;
	z-index: 9;
	background-color: var(--color-main-background);
}

.systemtags-picker__create {
	display: flex;
	top: 0;
	gap: 8px;
	padding-block-end: 8px;
	align-items: flex-end;

	button {
		flex-shrink: 0;
	}
}

.systemtags-picker__note {
	bottom: 0;
	padding-block: 8px;

	& > div {
		margin: 0 !important;
	}
}

// Rendered chip in note
.nc-chip {
	display: inline !important;
}
</style>
