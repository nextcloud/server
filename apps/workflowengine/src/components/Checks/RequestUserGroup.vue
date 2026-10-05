<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div>
		<NcSelect
			:aria-label-combobox="t('workflowengine', 'Select groups')"
			:aria-label-listbox="t('workflowengine', 'Groups')"
			:class="$style.select"
			:clearable="false"
			:loading="status.isLoading && groups.length === 0"
			:modelValue="currentValue"
			:options="groups"
			:placeholder="t('workflowengine', 'Type to search for group …')"
			label="displayname"
			@search="searchAsync"
			@update:modelValue="update" />
	</div>
</template>

<script setup lang="ts">
import axios from '@nextcloud/axios'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { computed, onMounted, reactive, watch } from 'vue'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { useCheckValue } from '../../composables/useCheckValue.ts'
import { logger } from '../../logger.ts'

interface Group {
	id: string
	displayname: string
}

const props = withDefaults(defineProps<{ modelValue?: string }>(), { modelValue: '' })
const emit = defineEmits<{ 'update:modelValue': [value: string] }>()
// shared across every instance on the page: the group list is the same for all
// of them and is expensive to fetch again per check
const groups = reactive<Group[]>([])
const wantedGroups = reactive<string[]>([])
const status = reactive({ isLoading: false })

const { newValue, emitValue } = useCheckValue(
	() => props.modelValue,
	(value) => emit('update:modelValue', value),
)

const currentValue = computed(() => groups.find((group) => group.id === newValue.value) ?? null)

/**
 * @param group - The group to remember
 */
function addGroup(group: Group): void {
	if (!groups.some((item) => item.id === group.id)) {
		groups.push(group)
	}
}

/**
 * @param groupId - Id of the group to look for
 */
function hasGroup(groupId: string): boolean {
	return groups.some((item) => item.id === groupId)
}

/**
 * @param expectedGroupId - Group to look up once the current request is done
 */
function enqueueWantedGroup(expectedGroupId: string): void {
	if (!wantedGroups.includes(expectedGroupId)) {
		wantedGroups.push(expectedGroupId)
	}
}

async function findGroupByQueue(): Promise<void> {
	let nextQuery: string | undefined
	do {
		nextQuery = wantedGroups.shift()
		if (nextQuery !== undefined && hasGroup(nextQuery)) {
			nextQuery = undefined
		}
	} while (!nextQuery && wantedGroups.length > 0)
	if (nextQuery) {
		await searchAsync(nextQuery)
	}
}

/**
 * @param searchQuery - Text to search groups for, empty for the first chunk
 */
async function searchAsync(searchQuery: string): Promise<void> {
	if (status.isLoading) {
		if (searchQuery) {
			// The first 20 groups are loaded up front (indicated by an empty
			// searchQuery parameter), afterwards we may load groups that have
			// not been fetched yet, but are used in existing rules.
			enqueueWantedGroup(searchQuery)
		}
		return
	}

	status.isLoading = true
	try {
		const { data } = await axios.get(generateOcsUrl('cloud/groups/details?limit=20&search={searchQuery}', { searchQuery }))
		data.ocs.data.groups.forEach((group: Group) => {
			addGroup({ id: group.id, displayname: group.displayname })
		})
		status.isLoading = false
		await findGroupByQueue()
	} catch (error) {
		status.isLoading = false
		logger.error('Error while loading group list', { error })
	}
}

/**
 * @param value - The group that was picked
 */
function update(value: Group | null): void {
	if (value !== null) {
		emitValue(value.id)
	}
}

watch(newValue, (value) => {
	if (value && currentValue.value === null) {
		searchAsync(value)
	}
})

onMounted(async () => {
	if (groups.length === 0) {
		await searchAsync('')
	}
	// the group of a stored rule may not be among the ones loaded up front
	if (currentValue.value === null && newValue.value) {
		await searchAsync(newValue.value)
	}
})
</script>

<style module>
.select {
	width: 100%;
}
</style>
