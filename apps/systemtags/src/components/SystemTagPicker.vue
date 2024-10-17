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
		<div class="systemtags-picker__tags">
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

		<!-- Note -->
		<div class="systemtags-picker__note">
			<NcNoteCard v-if="!hasChanges" type="info">
				{{ t('systemtags', 'Select or create tags to apply to all selected files') }}
			</NcNoteCard>
			<NcNoteCard v-else type="info">
				<span v-html="statusMessage" />
			</NcNoteCard>
		</div>

		<template #actions>
			<NcButton type="tertiary" @click="onCancel">
				{{ t('systemtags', 'Cancel') }}
			</NcButton>
			<NcButton :disabled="!hasChanges" @click="onSubmit">
				{{ t('systemtags', 'Apply changes') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script lang="ts">
import type { Node } from '@nextcloud/files'
import type { PropType } from 'vue'
import type { TagWithId } from '../types'

import { defineComponent } from 'vue'
import { emit } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import TagIcon from 'vue-material-design-icons/Tag.vue'

import logger from '../services/logger'
import { getNodeSystemTags } from '../utils'
import { showInfo } from '@nextcloud/dialogs'

type TagListCount = {
	string: number
}

export default defineComponent({
	name: 'SystemTagPicker',

	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcDialog,
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
			input: '',
			opened: true,
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
			if (this.toAdd.length === 1 && this.toRemove.length === 1) {
				return t('systemtags', '{tag1} will be set and {tag2} will be removed from {count} files.', {
					tag1: this.toAdd[0].displayName,
					tag2: this.toRemove[0].displayName,
					count: this.nodes.length,
				})
			}

			const tagsAdd = this.toAdd.map(tag => tag.displayName)
			const lastTagAdd = tagsAdd.pop() as string
			const tagsRemove = this.toRemove.map(tag => tag.displayName)
			const lastTagRemove = tagsRemove.pop() as string

			const addStringSingular = t('systemtags', '{tag} will be set to {count} files.', {
				tag: this.toAdd[0]?.displayName,
				count: this.nodes.length,
			})

			const removeStringSingular = t('systemtags', '{tag} will be removed from {count} files.', {
				tag: this.toRemove[0]?.displayName,
				count: this.nodes.length,
			})

			const addStringPlural = t('systemtags', '{tags} and {lastTag} will be set to {count} files.', {
				tags: tagsAdd.join(', '),
				lastTag: lastTagAdd,
				count: this.nodes.length,
			})

			const removeStringPlural = t('systemtags', '{tags} and {lastTag} will be removed from {count} files.', {
				tags: tagsRemove.join(', '),
				lastTag: lastTagRemove,
				count: this.nodes.length,
			})

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
			return this.tagList[tag.displayName] === this.nodes.length
		},

		isIndeterminate(tag: TagWithId): boolean {
			return this.tagList[tag.displayName]
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

		onSubmit() {
			logger.debug('onSubmit')
			this.$emit('close', null)
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

</style>
