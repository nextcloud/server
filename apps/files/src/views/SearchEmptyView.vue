<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import { getPinia } from '../store/index.ts'
import { useSearchStore } from '../store/search.ts'
import { mdiMagnifyClose } from '@mdi/js'

const searchStore = useSearchStore(getPinia())
const query = computed(() => searchStore.query)
</script>

<template>
	<NcEmptyContent :name="t('files', 'No search results for “{query}”', { query })">
		<template #icon>
			<NcIconSvgWrapper :path="mdiMagnifyClose" />
		</template>
		<template #action>
			<NcInputField v-model="searchStore.query"
				class="search-empty-view__input"
				:label="t('files', 'Search for files')"
				type="search" />
			<NcButton v-if="searchStore.scope === 'locally'" @click="searchStore.scope = 'globally'">
				{{ t('files', 'Search globally') }}
			</NcButton>
		</template>
	</NcEmptyContent>
</template>

<style scoped lang="scss">
.search-empty-view__input {
	min-width: min(400px, 50vw);
}
</style>
