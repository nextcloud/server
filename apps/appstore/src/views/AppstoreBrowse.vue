<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import AppGrid from '../components/AppGrid/AppGrid.vue'
import OfficeSuiteSwitcher from '../components/AppstoreBrowse/OfficeSuiteSwitcher.vue'
import AppTable from '../components/AppTable/AppTable.vue'
import AppToolbar from '../components/AppToolbar.vue'
import { useFilteredApps } from '../composables/useFilteredApps.ts'
import { useAppsStore } from '../store/apps.ts'
import { useUserSettingsStore } from '../store/userSettings.ts'

const route = useRoute()
const store = useAppsStore()
const userSettings = useUserSettingsStore()

const currentCategory = computed(() => route.params!.category as string)
const apps = computed(() => {
	if (currentCategory.value === 'featured') {
		return store.apps.filter((app) => app.level === 200)
	} else if (currentCategory.value === 'supported') {
		return store.apps.filter((app) => app.level === 300)
	}
	return store.getAppsByCategory(currentCategory.value)
})

const visibleApps = useFilteredApps(apps)
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

	<template v-else>
		<OfficeSuiteSwitcher v-if="currentCategory === 'office'" />

		<component
			:is="userSettings.isGridView ? AppGrid : AppTable"
			:class="$style.appstoreBrowse"
			:apps="visibleApps" />
	</template>
</template>

<style module>
.appstoreBrowse {
	margin-bottom: var(--body-container-margin);
}
</style>
