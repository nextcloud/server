<template>
	<NcDialog data-cy-systemtags-picker
		:name="t('systemtags', 'Manage tags')"
		:open="true"
		class="systemtags-picker"
		close-on-click-outside
		out-transition
		@update:open="emit('close', null)">
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

		<template #actions>
			<NcButton @click="emit('close', null)">
				{{ t('systemtags', 'Cancel') }}
			</NcButton>
			<NcButton type="tertiary" @click="onSubmit">
				{{ t('systemtags', 'Close') }}
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
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import TagIcon from 'vue-material-design-icons/Tag.vue'

import logger from '../services/logger'
import { getNodeSystemTags } from '../utils'

type TagListCount = {
	string: number
}

export default defineComponent({
	name: 'SystemTagPicker',

	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcDialog,
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
			tagList: {} as TagListCount,
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
			return this.tagList[tag.displayName]
				&& this.tagList[tag.displayName] === this.nodes.length
		},

		isIndeterminate(tag: TagWithId): boolean {
			return this.tagList[tag.displayName] !== 0
				&& this.tagList[tag.displayName] !== this.nodes.length
		},

		onCheckUpdate(tag: TagWithId, checked: boolean) {
			logger.debug('onCheckUpdate', { tag, checked })
		},

		onSubmit() {
			logger.debug('onSubmit')
		},
	},
})
</script>

<style scoped lang="scss">
.systemtags-picker__create {
	display: flex;
	align-items: center;
	
	.input-field {
		margin: 0;
		margin-inline-end: 10px;
	}

	button {
		flex-shrink: 0;
	}
}
</style>
