<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcButton v-if="isVisible" size="small" @click="onClick">
		{{ t('files', 'Search everywhere') }}
	</NcButton>
</template>

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import { getPinia } from '../../store/index.ts'
import { useSearchStore } from '../../store/search.ts'

const searchStore = useSearchStore(getPinia())

const isVisible = computed(() => searchStore.query.length >= 3 && searchStore.scope === 'filter')

/**
 * Button click handler to make the filtering a global search.
 */
function onClick() {
	searchStore.scope = 'globally'
}
</script>
