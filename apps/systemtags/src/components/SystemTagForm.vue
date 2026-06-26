<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Tag, TagWithId } from '../types.ts'

import { showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { computed, ref, useTemplateRef, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSelectTags from '@nextcloud/vue/components/NcSelectTags'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { createTag, deleteTag, updateTag } from '../services/api.ts'
import { defaultBaseTag } from '../utils.ts'

const props = defineProps<{
	tags: TagWithId[]
}>()

const emit = defineEmits<{
	'tag:created': [tag: TagWithId]
	'tag:updated': [tag: TagWithId]
	'tag:deleted': [tag: TagWithId]
}>()

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

const tagNameInputElement = useTemplateRef('tagNameInput')

const loading = ref(false)
const errorMessage = ref('')
const tagName = ref('')
const tagLevel = ref(TagLevel.Public)

const selectedTag = ref<null | TagWithId>(null)
watch(selectedTag, (tag: null | TagWithId) => {
	tagName.value = tag ? tag.displayName : ''
	tagLevel.value = tag ? getTagLevel(tag.userVisible, tag.userAssignable) : TagLevel.Public
})

const isCreating = computed(() => selectedTag.value === null)
const isCreateDisabled = computed(() => tagName.value === '')

const isUpdateDisabled = computed(() => (
	tagName.value === ''
	|| (
		selectedTag.value?.displayName === tagName.value
		&& getTagLevel(selectedTag.value?.userVisible, selectedTag.value?.userAssignable) === tagLevel.value
	)
))

const isResetDisabled = computed(() => {
	if (isCreating.value) {
		return tagName.value === '' && tagLevel.value === TagLevel.Public
	}
	return selectedTag.value === null
})

const userVisible = computed((): boolean => {
	const matchLevel: Record<TagLevel, boolean> = {
		[TagLevel.Public]: true,
		[TagLevel.Restricted]: true,
		[TagLevel.Invisible]: false,
	}
	return matchLevel[tagLevel.value]
})

const userAssignable = computed(() => {
	const matchLevel: Record<TagLevel, boolean> = {
		[TagLevel.Public]: true,
		[TagLevel.Restricted]: false,
		[TagLevel.Invisible]: false,
	}
	return matchLevel[tagLevel.value]
})

const tagProperties = computed((): Omit<Tag, 'id' | 'canAssign'> => {
	return {
		displayName: tagName.value,
		userVisible: userVisible.value,
		userAssignable: userAssignable.value,
	}
})

/**
 * Handle tag selection
 *
 * @param tagId - The selected tag ID
 */
function onSelectTag(tagId: number | null) {
	const tag = props.tags.find((search) => search.id === tagId) || null
	selectedTag.value = tag
}

/**
 * Handle form submission
 */
async function handleSubmit() {
	if (isCreating.value) {
		await create()
		return
	}
	await update()
}

/**
 * Create a new tag
 */
async function create() {
	const tag: Tag = { ...defaultBaseTag, ...tagProperties.value }
	loading.value = true
	try {
		const id = await createTag(tag)
		const createdTag: TagWithId = { ...tag, id }
		emit('tag:created', createdTag)
		showSuccess(t('systemtags', 'Created tag'))
		reset()
	} catch {
		errorMessage.value = t('systemtags', 'Failed to create tag')
	}
	loading.value = false
}

/**
 * Update the selected tag
 */
async function update() {
	if (selectedTag.value === null) {
		return
	}
	const tag: TagWithId = { ...selectedTag.value, ...tagProperties.value }
	loading.value = true
	try {
		await updateTag(tag)
		selectedTag.value = tag
		emit('tag:updated', tag)
		showSuccess(t('systemtags', 'Updated tag'))
		tagNameInputElement.value?.focus()
	} catch {
		errorMessage.value = t('systemtags', 'Failed to update tag')
	}
	loading.value = false
}

/**
 * Delete the selected tag
 */
async function handleDelete() {
	if (selectedTag.value === null) {
		return
	}
	loading.value = true
	try {
		await deleteTag(selectedTag.value)
		emit('tag:deleted', selectedTag.value)
		showSuccess(t('systemtags', 'Deleted tag'))
		reset()
	} catch {
		errorMessage.value = t('systemtags', 'Failed to delete tag')
	}
	loading.value = false
}

/**
 * Reset the form
 */
function reset() {
	selectedTag.value = null
	errorMessage.value = ''
	tagName.value = ''
	tagLevel.value = TagLevel.Public
	tagNameInputElement.value?.focus()
}

/**
 * Get tag level based on visibility and assignability
 *
 * @param userVisible - Whether the tag is visible to users
 * @param userAssignable - Whether the tag is assignable by users
 */
function getTagLevel(userVisible: boolean, userAssignable: boolean): TagLevel {
	const matchLevel: Record<string, TagLevel> = {
		[[true, true].join(',')]: TagLevel.Public,
		[[true, false].join(',')]: TagLevel.Restricted,
		[[false, false].join(',')]: TagLevel.Invisible,
	}
	return matchLevel[[userVisible, userAssignable].join(',')]!
}
</script>

<template>
	<form
		class="system-tag-form"
		:disabled="loading"
		aria-labelledby="system-tag-form-heading"
		@submit.prevent="handleSubmit"
		@reset="reset">
		<h4 id="system-tag-form-heading">
			{{ t('systemtags', 'Create or edit tags') }}
		</h4>

		<div class="system-tag-form__group">
			<label for="system-tags-input">{{ t('systemtags', 'Search for a tag to edit') }}</label>
			<NcSelectTags
				:modelValue="selectedTag"
				inputId="system-tags-input"
				:placeholder="t('systemtags', 'Collaborative tags …')"
				:fetchTags="false"
				:options="tags"
				:multiple="false"
				labelOutside
				@update:modelValue="onSelectTag">
				<template #no-options>
					{{ t('systemtags', 'No tags to select') }}
				</template>
			</NcSelectTags>
		</div>

		<div class="system-tag-form__group">
			<label for="system-tag-name">{{ t('systemtags', 'Tag name') }}</label>
			<NcTextField
				id="system-tag-name"
				ref="tagNameInput"
				v-model="tagName"
				:error="Boolean(errorMessage)"
				:helperText="errorMessage"
				labelOutside />
		</div>

		<div class="system-tag-form__group">
			<label for="system-tag-level">{{ t('systemtags', 'Tag level') }}</label>
			<NcSelect
				v-model="tagLevel"
				inputId="system-tag-level"
				:options="tagLevelOptions"
				:reduce="level => level.id"
				:clearable="false"
				:disabled="loading"
				labelOutside />
		</div>

		<div class="system-tag-form__row">
			<NcButton
				v-if="isCreating"
				type="submit"
				:disabled="isCreateDisabled || loading">
				{{ t('systemtags', 'Create') }}
			</NcButton>
			<template v-else>
				<NcButton
					type="submit"
					:disabled="isUpdateDisabled || loading">
					{{ t('systemtags', 'Update') }}
				</NcButton>
				<NcButton
					:disabled="loading"
					@click="handleDelete">
					{{ t('systemtags', 'Delete') }}
				</NcButton>
			</template>
			<NcButton
				type="reset"
				:disabled="isResetDisabled || loading">
				{{ t('systemtags', 'Reset') }}
			</NcButton>
			<NcLoadingIcon
				v-if="loading"
				:name="t('systemtags', 'Loading …')"
				:size="32" />
		</div>
	</form>
</template>

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
