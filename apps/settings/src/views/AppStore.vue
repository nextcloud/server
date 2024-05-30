<!--
	- @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
	-
	- @author Julius Härtl <jus@bitgrid.net>
	- @author Ferdinand Thiessen <opensource@fthiessen.de>
	-
	- @license AGPL-3.0-or-later
	-
	- This program is free software: you can redistribute it and/or modify
	- it under the terms of the GNU Affero General Public License as
	- published by the Free Software Foundation, either version 3 of the
	- License, or (at your option) any later version.
	-
	- This program is distributed in the hope that it will be useful,
	- but WITHOUT ANY WARRANTY; without even the implied warranty of
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
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
import { computed, getCurrentInstance, onBeforeMount, watchEffect } from 'vue'
import { useRoute } from 'vue-router/composables'

import { useAppsStore } from '../store/apps-store'
import { APPS_SECTION_ENUM } from '../constants/AppsConstants'

import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import AppList from '../components/AppList.vue'
import AppStoreDiscoverSection from '../components/AppStoreDiscover/AppStoreDiscoverSection.vue'

const route = useRoute()
const store = useAppsStore()

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
