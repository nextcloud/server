<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<form class="system-tag-form"
		:disabled="loading"
		aria-labelledby="system-tag-form-heading"
		@submit.prevent="handleSubmit"
		@reset="reset">
		<h4 id="system-tag-form-heading">
			{{ t('systemtags', 'Create or edit tags') }}
		</h4>

		<div class="system-tag-form__group">
			<label for="system-tags-input">{{ t('systemtags', 'Search for a tag to edit') }}</label>
			<NcSelectTags v-model="selectedTag"
				input-id="system-tags-input"
				:placeholder="t('systemtags', 'Collaborative tags …')"
				:fetch-tags="false"
				:options="tags"
				:multiple="false"
				passthru>
				<template #no-options>
					{{ t('systemtags', 'No tags to select') }}
				</template>
			</NcSelectTags>
		</div>

		<div class="system-tag-form__group">
			<label for="system-tag-name">{{ t('systemtags', 'Tag name') }}</label>
			<NcTextField id="system-tag-name"
				ref="tagNameInput"
				:value.sync="tagName"
				:error="Boolean(errorMessage)"
				:helper-text="errorMessage"
				label-outside />
		</div>

		<div class="system-tag-form__group">
			<label for="system-tag-level">{{ t('systemtags', 'Tag level') }}</label>
			<NcSelect v-model="tagLevel"
				input-id="system-tag-level"
				:options="tagLevelOptions"
				:reduce="level => level.id"
				:clearable="false"
				:disabled="loading" />
		</div>

		<div class="system-tag-form__row">
			<NcButton v-if="isCreating"
				native-type="submit"
				:disabled="isCreateDisabled || loading">
				{{ t('systemtags', 'Create') }}
			</NcButton>
			<template v-else>
				<NcButton native-type="submit"
					:disabled="isUpdateDisabled || loading">
					{{ t('systemtags', 'Update') }}
				</NcButton>
				<NcButton :disabled="loading"
					@click="handleDelete">
					{{ t('systemtags', 'Delete') }}
				</NcButton>
			</template>
			<NcButton native-type="reset"
				:disabled="isResetDisabled || loading">
				{{ t('systemtags', 'Reset') }}
			</NcButton>
			<NcLoadingIcon v-if="loading"
				:name="t('systemtags', 'Loading …')"
				:size="32" />
		</div>
	</form>
</template>

<script lang="ts">
/* eslint-disable */
import Vue, { type PropType } from 'vue'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import NcSelectTags from '@nextcloud/vue/dist/Components/NcSelectTags.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

import { translate as t } from '@nextcloud/l10n'
import { showSuccess } from '@nextcloud/dialogs'

import { defaultBaseTag } from '../utils.js'
import { createTag, deleteTag, updateTag } from '../services/api.js'

import type { Tag, TagWithId } from '../types.js'

enum TagLevel {
	Public = 'Public',
	Restricted = 'Restricted',
	Invisible = 'Invisible',
}

interface TagLevelOption {
	id: TagLevel
	label: string
}

const tagLevelOptions: TagLevelOption[] = [
	{
		id: TagLevel.Public,
		label: t('systemtags', 'Public'),
	},
	{
		id: TagLevel.Restricted,
		label: t('systemtags', 'Restricted'),
	},
	{
		id: TagLevel.Invisible,
		label: t('systemtags', 'Invisible'),
	},
]

const getTagLevel = (userVisible: boolean, userAssignable: boolean): TagLevel => {
	const matchLevel: Record<string, TagLevel> = {
		[[true, true].join(',')]: TagLevel.Public,
		[[true, false].join(',')]: TagLevel.Restricted,
		[[false, false].join(',')]: TagLevel.Invisible,
	}
	return matchLevel[[userVisible, userAssignable].join(',')]
}

export default Vue.extend({
	name: 'SystemTagForm',

	components: {
		NcButton,
		NcLoadingIcon,
		NcSelect,
		NcSelectTags,
		NcTextField,
	},

	props: {
		tags: {
			type: Array as PropType<TagWithId[]>,
			required: true,
		},
	},

	data() {
		return {
			loading: false,
			tagLevelOptions,
			selectedTag: null as null | TagWithId,
			errorMessage: '',
			tagName: '',
			tagLevel: TagLevel.Public,
		}
	},

	watch: {
		selectedTag(tag: null | TagWithId) {
			this.tagName = tag ? tag.displayName : ''
			this.tagLevel = tag ? getTagLevel(tag.userVisible, tag.userAssignable) : TagLevel.Public
		},
	},

	computed: {
		isCreating(): boolean {
			return this.selectedTag === null
		},

		isCreateDisabled(): boolean {
			return this.tagName === ''
		},

		isUpdateDisabled(): boolean {
			return (
				this.tagName === ''
				|| (
					this.selectedTag?.displayName === this.tagName
					&& getTagLevel(this.selectedTag?.userVisible, this.selectedTag?.userAssignable) === this.tagLevel
				)
			)
		},

		isResetDisabled(): boolean {
			if (this.isCreating) {
				return this.tagName === '' && this.tagLevel === TagLevel.Public
			}
			return this.selectedTag === null
		},

		userVisible(): boolean {
			const matchLevel: Record<TagLevel, boolean> = {
				[TagLevel.Public]: true,
				[TagLevel.Restricted]: true,
				[TagLevel.Invisible]: false,
			}
			return matchLevel[this.tagLevel]
		},

		userAssignable(): boolean {
			const matchLevel: Record<TagLevel, boolean> = {
				[TagLevel.Public]: true,
				[TagLevel.Restricted]: false,
				[TagLevel.Invisible]: false,
			}
			return matchLevel[this.tagLevel]
		},

		tagProperties(): Omit<Tag, 'id' | 'canAssign'> {
			return {
				displayName: this.tagName,
				userVisible: this.userVisible,
				userAssignable: this.userAssignable,
			}
		},
	},

	methods: {
		t,

		async handleSubmit() {
			if (this.isCreating) {
				await this.create()
				return
			}
			await this.update()
		},

		async create() {
			const tag: Tag = { ...defaultBaseTag, ...this.tagProperties }
			this.loading = true
			try {
				const id = await createTag(tag)
				const createdTag: TagWithId = { ...tag, id }
				this.$emit('tag:created', createdTag)
				showSuccess(t('systemtags', 'Created tag'))
				this.reset()
			} catch (error) {
				this.errorMessage = t('systemtags', 'Failed to create tag')
			}
			this.loading = false
		},

		async update() {
			if (this.selectedTag === null) {
				return
			}
			const tag: TagWithId = { ...this.selectedTag, ...this.tagProperties }
			this.loading = true
			try {
				await updateTag(tag)
				this.selectedTag = tag
				this.$emit('tag:updated', tag)
				showSuccess(t('systemtags', 'Updated tag'))
				this.$refs.tagNameInput?.focus()
			} catch (error) {
				this.errorMessage = t('systemtags', 'Failed to update tag')
			}
			this.loading = false
		},

		async handleDelete() {
			if (this.selectedTag === null) {
				return
			}
			this.loading = true
			try {
				await deleteTag(this.selectedTag)
				this.$emit('tag:deleted', this.selectedTag)
				showSuccess(t('systemtags', 'Deleted tag'))
				this.reset()
			} catch (error) {
				this.errorMessage = t('systemtags', 'Failed to delete tag')
			}
			this.loading = false
		},

		reset() {
			this.selectedTag = null
			this.errorMessage = ''
			this.tagName = ''
			this.tagLevel = TagLevel.Public
			this.$refs.tagNameInput?.focus()
		},
	},
})
</script>

<style lang="scss" scoped>
.system-tag-form {
	display: flex;
	flex-direction: column;
	max-width: 400px;
	gap: 8px 0;

	&__group {
		display: flex;
		flex-direction: column;
	}

	&__row {
		margin-top: 8px;
		display: flex;
		gap: 0 4px;
	}
}
</style>
