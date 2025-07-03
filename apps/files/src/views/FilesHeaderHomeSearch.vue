<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="files-list__header-home-search-wrapper">
		<NcTextField ref="searchInput"
			:value="searchText"
			class="files-list__header-home-search-input"
			:label="t('files', 'Search files and folders')"
			minlength="3"
			:pill="true"
			trailing-button-icon="close"
			:trailing-button-label="t('files', 'Clear search')"
			:show-trailing-button="searchText.trim() !== ''"
			type="search"
			@trailing-button-click="emit('update:searchText', '')"
			@update:model-value="onSearch">
			<template #icon>
				<Magnify :size="20" />
			</template>
		</NcTextField>
	</div>
</template>

<script setup lang="ts">
import type { View } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { defineProps, withDefaults, defineEmits, ref, onMounted } from 'vue'
import { subscribe } from '@nextcloud/event-bus'

import NcTextField from '@nextcloud/vue/components/NcTextField'
import Magnify from 'vue-material-design-icons/Magnify.vue'

import { VIEW_ID } from './search'
import logger from '../logger'

interface Props {
	searchText?: string
}

withDefaults(defineProps<Props>(), {
	searchText: '',
})

const emit = defineEmits<{
	(e: 'update:searchText', query: string): void
}>()

const searchInput = ref(null) as NcTextField
const onSearch = (text: string) => {
	const input = searchInput?.value?.$refs?.inputField?.$refs?.input as HTMLInputElement
	input?.reportValidity?.()

	// Emit the search text to the parent component
	emit('update:searchText', text)
}

onMounted(() => {
	const input = searchInput?.value?.$refs?.inputField?.$refs?.input as HTMLInputElement
	input?.focus?.()
})

// Subscribing here to ensure we have mounted already and all views are registered
subscribe('files:navigation:changed', (view: View) => {
	if (view.id !== VIEW_ID) {
		return
	}

	// Reset search text when navigating away
	logger.info('Resetting search on navigation away from home view')
	emit('update:searchText', '')
})
</script>
