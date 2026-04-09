<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiMagnifyClose } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import debounce from 'debounce'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import { getPinia } from '../store/index.ts'
import { useSearchStore } from '../store/search.ts'

const searchStore = useSearchStore(getPinia())
const debouncedUpdate = debounce((value: string) => {
	searchStore.query = value
}, 500)
</script>

<template>
	<NcEmptyContent :name="t('files', 'No search results for “{query}”', { query: searchStore.query })">
		<template #icon>
			<NcIconSvgWrapper :path="mdiMagnifyClose" />
		</template>
		<template #action>
			<div class="search-empty-view__wrapper">
				<NcInputField
					class="search-empty-view__input"
					:label="t('files', 'Search for files')"
					:model-value="searchStore.query"
					type="search"
					@update:model-value="debouncedUpdate" />
			</div>
		</template>
	</NcEmptyContent>
</template>

<style scoped lang="scss">
.search-empty-view {
	&__input {
		flex: 0 1;
		min-width: min(400px, 50vw);
	}

	&__wrapper {
		display: flex;
		flex-wrap: wrap;
		gap: 10px;
		align-items: baseline;
	}
}
</style>
