<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiAlertCircleOutline, mdiMagnify } from '@mdi/js'
import { computed, ref } from 'vue'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import NcTextField from '@nextcloud/vue/components/NcTextField'

/** An entry of the list, e.g. a contact */
export interface SearchableListItem {
	id: string
	displayName: string
	user?: string
	isUser?: boolean
}

const props = withDefaults(defineProps<{
	searchList: SearchableListItem[]
	emptyContentText: string
	labelText?: string
}>(), {
	labelText: 'this is a label',
})

const emit = defineEmits<{
	itemSelected: [item: SearchableListItem]
	searchTermChange: [term: string]
}>()

const opened = ref(false)
const searchTerm = ref('')

const filteredList = computed(() => {
	const term = searchTerm.value.toLowerCase()
	return props.searchList.filter((element) => element.displayName.toLowerCase().includes(term))
})

/**
 * Pick an item and close the list.
 *
 * @param element - The picked item
 */
function itemSelected(element: SearchableListItem) {
	emit('itemSelected', element)
	searchTerm.value = ''
	opened.value = false
}

/**
 * Let the parent update the list for the new search term.
 *
 * @param term - The search term
 */
function searchTermChanged(term: string | number) {
	emit('searchTermChange', String(term))
}
</script>

<template>
	<NcPopover v-model:shown="opened">
		<template #trigger>
			<slot name="trigger" />
		</template>
		<div class="searchable-list__wrapper">
			<NcTextField
				v-model="searchTerm"
				:label="labelText"
				trailingButtonIcon="close"
				:showTrailingButton="searchTerm !== ''"
				@update:modelValue="searchTermChanged"
				@trailingButtonClick="searchTerm = ''">
				<NcIconSvgWrapper :path="mdiMagnify" />
			</NcTextField>
			<ul v-if="filteredList.length > 0" class="searchable-list__list">
				<li
					v-for="element in filteredList"
					:key="element.id"
					:title="element.displayName"
					role="button">
					<NcButton
						alignment="start"
						variant="tertiary"
						wide
						@click="itemSelected(element)">
						<template #icon>
							<NcAvatar v-if="element.isUser" :user="element.user" hideStatus />
							<NcAvatar
								v-else
								isNoUser
								:displayName="element.displayName"
								hideStatus />
						</template>
						{{ element.displayName }}
					</NcButton>
				</li>
			</ul>
			<div v-else class="searchable-list__empty-content">
				<NcEmptyContent :name="emptyContentText">
					<template #icon>
						<NcIconSvgWrapper :path="mdiAlertCircleOutline" />
					</template>
				</NcEmptyContent>
			</div>
		</div>
	</NcPopover>
</template>

<style lang="scss" scoped>
.searchable-list {
	&__wrapper {
		padding: calc(var(--default-grid-baseline) * 3);
		display: flex;
		flex-direction: column;
		align-items: center;
		width: 250px;
	}

	&__list {
		width: 100%;
		max-height: 284px;
		overflow-y: auto;
		margin-top: var(--default-grid-baseline);
		padding: var(--default-grid-baseline);

		:deep(.button-vue) {
			border-radius: var(--border-radius-large) !important;
			span {
				font-weight: initial;
			}
		}
	}

	&__empty-content {
		margin-top: calc(var(--default-grid-baseline) * 3);
	}
}
</style>
