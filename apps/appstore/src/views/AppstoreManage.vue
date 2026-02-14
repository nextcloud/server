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
import AppTable from '../components/AppTable.vue'
import { useAppsStore } from '../store/apps.ts'

const route = useRoute()
const store = useAppsStore()

const currentCategory = computed(() => route.params!.category as 'enabled' | 'installed' | 'disabled' | 'updates')
const apps = computed(() => {
	if (currentCategory.value === 'installed') {
		return store.apps.filter((app) => app.installed)
	} else if (currentCategory.value === 'enabled') {
		return store.apps.filter((app) => app.active)
	} else if (currentCategory.value === 'disabled') {
		return store.apps.filter((app) => app.installed && !app.active)
	} else if (currentCategory.value === 'updates') {
		return store.apps.filter((app) => app.update)
	}
	return []
})
</script>

<template>
	<!-- Apps list -->
	<NcEmptyContent
		v-if="store.isLoadingApps"
		:name="t('appstore', 'Loading app list')">
		<template #icon>
			<NcLoadingIcon :size="64" />
		</template>
	</NcEmptyContent>

	<AppTable v-else :apps />
</template>

<style scoped>
.empty-content__loading {
	height: 100%;
}

.app-settings-content__label {
	margin-block-start: var(--app-navigation-padding);
	margin-inline-start: calc(var(--default-clickable-area) + var(--app-navigation-padding) * 2);
	min-height: var(--default-clickable-area);
	line-height: var(--default-clickable-area);
	vertical-align: center;
}
</style>
