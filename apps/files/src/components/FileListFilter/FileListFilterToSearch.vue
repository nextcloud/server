<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcButton v-show="isVisible" @click="onClick">
		{{ t('files', 'Search everywhere') }}
	</NcButton>
</template>

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import { getPinia } from '../../store/index.ts'
import { useSearchStore } from '../../store/search.ts'

const isVisible = ref(false)

defineExpose({
	hideButton,
	showButton,
})

/**
 * Hide the button - called by the filter class
 */
function hideButton() {
	isVisible.value = false
}

/**
 * Show the button - called by the filter class
 */
function showButton() {
	isVisible.value = true
}

/**
 * Button click handler to make the filtering a global search.
 */
function onClick() {
	const searchStore = useSearchStore(getPinia())
	searchStore.scope = 'globally'
}
</script>
