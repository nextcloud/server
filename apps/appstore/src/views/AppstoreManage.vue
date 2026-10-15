<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiUpdate } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { NcIconSvgWrapper, spawnDialog } from '@nextcloud/vue'
import { computed, defineAsyncComponent } from 'vue'
import { useRoute } from 'vue-router'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import AppGrid from '../components/AppGrid/AppGrid.vue'
import AppTable from '../components/AppTable/AppTable.vue'
import AppToolbar from '../components/AppToolbar.vue'
import { useFilteredApps } from '../composables/useFilteredApps.ts'
import { useAppsStore } from '../store/apps.ts'
import { useUpdatesStore } from '../store/updates.ts'
import { useUserSettingsStore } from '../store/userSettings.ts'

const UpdateAllDialog = defineAsyncComponent(() => import('../components/UpdateAllDialog.vue'))

const route = useRoute()
const store = useAppsStore()
const updatesStore = useUpdatesStore()
const userSettings = useUserSettingsStore()

const currentCategory = computed(() => route.params!.category as 'enabled' | 'installed' | 'disabled' | 'updates')
const apps = computed(() => {
	if (currentCategory.value === 'installed') {
		return store.apps.filter((app) => app.installed)
	} else if (currentCategory.value === 'enabled') {
		return store.apps.filter((app) => app.active)
	} else if (currentCategory.value === 'disabled') {
		return store.apps.filter((app) => app.installed && !app.active)
	} else if (currentCategory.value === 'updates') {
		return store.apps.filter((app) => app.active && app.update)
	}
	return []
})
const visibleApps = useFilteredApps(apps)

/**
 * Handle update all apps
 */
async function onUpdateAll() {
	await spawnDialog(UpdateAllDialog, {
		apps: visibleApps.value,
	})
}
</script>

<template>
	<AppToolbar />

	<NcButton
		v-if="currentCategory === 'updates' && updatesStore.updateCount > 0"
		:class="$style.appstoreManage__updateAllButton"
		variant="primary"
		@click="onUpdateAll">
		<template #icon>
			<NcIconSvgWrapper :path="mdiUpdate" />
		</template>
		{{ t('appstore', 'Update all applications') }}
	</NcButton>

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
		v-else-if="visibleApps.length"
		:class="$style.appstoreManage"
		:apps="visibleApps" />
	<NcEmptyContent
		v-else
		:name="t('appstore', 'No matching apps found')">
		<template #action>
			<NcButton variant="primary" @click="$router.push({ query: $route.query, name: 'apps-search' })">
				{{ t('appstore', 'Search everywhere') }}
			</NcButton>
		</template>
	</NcEmptyContent>
</template>

<style module>
.appstoreManage {
	margin-bottom: var(--body-container-margin);
}

.appstoreManage__updateAllButton {
	margin-inline: var(--app-navigation-padding);
	margin-block: calc(3 * var(--default-grid-baseline));
}
</style>
