<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { watchDebounced } from '@vueuse/core'
import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import AppGrid from '../components/AppGrid/AppGrid.vue'
import AppTable from '../components/AppTable/AppTable.vue'
import AppToolbar from '../components/AppToolbar.vue'
import { useFilteredApps } from '../composables/useFilteredApps.ts'
import { useAppsStore } from '../store/apps.ts'
import { useUserSettingsStore } from '../store/userSettings.ts'

const route = useRoute()
const router = useRouter()
const store = useAppsStore()
const userSettings = useUserSettingsStore()

const visibleApps = useFilteredApps(() => store.apps)
const search = ref('')

watch(() => route.query.q, (newQuery) => {
	search.value = [newQuery || ''].flat()[0]!
}, { immediate: true })

watchDebounced(search, (newValue) => {
	router.replace({
		...route,
		query: {
			...route.query,
			q: newValue.trim(),
		},
	})
}, { debounce: 500 })
</script>

<template>
	<AppToolbar />

	<!-- Apps list -->
	<NcEmptyContent
		v-if="store.isLoadingApps"
		:name="t('appstore', 'Loading app list')">
		<template #icon>
			<NcLoadingIcon :size="64" />
		</template>
	</NcEmptyContent>

	<component
		:is="userSettings.isGridView ? AppGrid : AppTable"
		v-else-if="visibleApps.length && search.trim().length > 2"
		:class="$style.appstoreSearch"
		:apps="visibleApps" />
	<NcEmptyContent
		v-else
		:name="t('appstore', 'No matching apps found')"
		:description="search.trim().length <= 2 ? t('appstore', 'Please enter more characters to search.') : undefined">
		<template #action>
			<NcInputField v-model="search" type="search" :label="t('appstore', 'Search apps')" />
		</template>
	</NcEmptyContent>
</template>

<style module>
.appstoreSearch {
	margin-bottom: var(--body-container-margin);
}
</style>
