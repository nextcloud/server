<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<!-- Apps list -->
	<NcAppContent
		class="app-settings-content"
		:page-heading="pageHeading"
		:page-title="pageTitle">
		<h2 class="app-settings-content__label" v-text="viewLabel" />

		<AppStoreDiscoverSection v-if="currentCategory === 'discover'" />
		<NcEmptyContent
			v-else-if="isLoading"
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
import { computed, getCurrentInstance, onBeforeMount, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router/composables'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import AppList from '../components/AppList.vue'
import AppStoreDiscoverSection from '../components/AppStoreDiscover/AppStoreDiscoverSection.vue'
import { APPSTORE_CATEGORY_NAMES } from '../constants.ts'
import { useAppApiStore } from '../store/app-api-store.ts'
import { useAppsStore } from '../store/apps-store.ts'

const route = useRoute()
const store = useAppsStore()
const appApiStore = useAppApiStore()

/**
 * ID of the current active category, default is `discover`
 */
const currentCategory = computed(() => route.params?.category ?? 'discover')

const viewLabel = computed<string>(() => APPSTORE_CATEGORY_NAMES[currentCategory.value] ?? store.getCategoryById(currentCategory.value)?.displayName)

const pageHeading = t('settings', 'App Store')
const pageTitle = computed(() => `${viewLabel.value} - ${pageHeading}`) // NcAppContent automatically appends the instance name

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
