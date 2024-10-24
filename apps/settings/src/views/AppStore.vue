<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<!-- Apps list -->
	<NcAppContent class="app-settings-content"
		:page-heading="appStoreLabel">
		<h2 class="app-settings-content__label" v-text="viewLabel" />

		<AppStoreDiscoverSection v-if="currentCategory === 'discover'" />
		<NcEmptyContent v-else-if="isLoading"
			class="empty-content__loading"
			:name="t('settings', 'Loading app list')">
			<template #icon>
				<NcLoadingIcon :size="64" />
			</template>
		</NcEmptyContent>
		<AppList v-else :category="currentCategory" />
	</NcAppContent>
</template>

<script setup lang="ts">
import { translate as t } from '@nextcloud/l10n'
import { computed, getCurrentInstance, onBeforeMount, onBeforeUnmount, watchEffect } from 'vue'
import { useRoute } from 'vue-router/composables'

import { useAppsStore } from '../store/apps-store'
import { APPS_SECTION_ENUM } from '../constants/AppsConstants'

import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import AppList from '../components/AppList.vue'
import AppStoreDiscoverSection from '../components/AppStoreDiscover/AppStoreDiscoverSection.vue'
import { useAppApiStore } from '../store/app-api-store.ts'

const route = useRoute()
const store = useAppsStore()
const appApiStore = useAppApiStore()

/**
 * ID of the current active category, default is `discover`
 */
const currentCategory = computed(() => route.params?.category ?? 'discover')

const appStoreLabel = t('settings', 'App Store')
const viewLabel = computed(() => APPS_SECTION_ENUM[currentCategory.value] ?? store.getCategoryById(currentCategory.value)?.displayName ?? appStoreLabel)

watchEffect(() => {
	window.document.title = `${viewLabel.value} - ${appStoreLabel} - Nextcloud`
})

// TODO this part should be migrated to pinia
const instance = getCurrentInstance()
/** Is the app list loading */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const isLoading = computed(() => (instance?.proxy as any).$store.getters.loading('list'))
onBeforeMount(() => {
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	(instance?.proxy as any).$store.dispatch('getCategories', { shouldRefetchCategories: true });
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	(instance?.proxy as any).$store.dispatch('getAllApps')
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	if ((instance?.proxy as any).$store.getters.isAppApiEnabled) {
		appApiStore.fetchAllApps()
		appApiStore.updateAppsStatus()
	}
})
onBeforeUnmount(() => {
	clearInterval(appApiStore.getStatusUpdater)
})
</script>

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
