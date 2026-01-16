<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<!-- Categories & filters -->
	<NcAppNavigation :aria-label="t('settings', 'Apps')">
		<template #list>
			<NcAppNavigationItem
				v-if="appstoreEnabled"
				id="app-category-discover"
				:to="{ name: 'apps-discover' }"
				:name="APPSTORE_CATEGORY_NAMES.discover">
				<template #icon>
					<NcIconSvgWrapper :path="APPSTORE_CATEGORY_ICONS.discover" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem
				id="app-category-installed"
				:to="{ name: 'apps-category', params: { category: 'installed' } }"
				:name="APPSTORE_CATEGORY_NAMES.installed">
				<template #icon>
					<NcIconSvgWrapper :path="APPSTORE_CATEGORY_ICONS.installed" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem
				id="app-category-enabled"
				:to="{ name: 'apps-category', params: { category: 'enabled' } }"
				:name="APPSTORE_CATEGORY_NAMES.enabled">
				<template #icon>
					<NcIconSvgWrapper :path="APPSTORE_CATEGORY_ICONS.enabled" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem
				id="app-category-disabled"
				:to="{ name: 'apps-category', params: { category: 'disabled' } }"
				:name="APPSTORE_CATEGORY_NAMES.disabled">
				<template #icon>
					<NcIconSvgWrapper :path="APPSTORE_CATEGORY_ICONS.disabled" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem
				v-if="updateStore.updateCount > 0"
				id="app-category-updates"
				:to="{ name: 'apps-category', params: { category: 'updates' } }"
				:name="APPSTORE_CATEGORY_NAMES.updates">
				<template #counter>
					<NcCounterBubble :count="updateStore.updateCount" />
				</template>
				<template #icon>
					<NcIconSvgWrapper :path="APPSTORE_CATEGORY_ICONS.updates" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem
				id="app-category-your-bundles"
				:to="{ name: 'apps-category', params: { category: 'app-bundles' } }"
				:name="APPSTORE_CATEGORY_NAMES['app-bundles']">
				<template #icon>
					<NcIconSvgWrapper :path="APPSTORE_CATEGORY_ICONS.bundles" />
				</template>
			</NcAppNavigationItem>

			<NcAppNavigationSpacer />

			<!-- App store categories -->
			<li v-if="appstoreEnabled && categoriesLoading" class="categories--loading">
				<NcLoadingIcon :size="20" :aria-label="t('settings', 'Loading categories')" />
			</li>
			<template v-else-if="appstoreEnabled && !categoriesLoading">
				<NcAppNavigationItem
					v-if="isSubscribed"
					id="app-category-supported"
					:to="{ name: 'apps-category', params: { category: 'supported' } }"
					:name="APPSTORE_CATEGORY_NAMES.supported">
					<template #icon>
						<NcIconSvgWrapper :path="APPSTORE_CATEGORY_ICONS.supported" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem
					id="app-category-featured"
					:to="{ name: 'apps-category', params: { category: 'featured' } }"
					:name="APPSTORE_CATEGORY_NAMES.featured">
					<template #icon>
						<NcIconSvgWrapper :path="APPSTORE_CATEGORY_ICONS.featured" />
					</template>
				</NcAppNavigationItem>

				<NcAppNavigationItem
					v-for="category in categories"
					:id="`app-category-${category.id}`"
					:key="category.id"
					:name="category.displayName"
					:to="{
						name: 'apps-category',
						params: { category: category.id },
					}">
					<template #icon>
						<NcIconSvgWrapper :path="category.icon" />
					</template>
				</NcAppNavigationItem>
			</template>

			<NcAppNavigationItem
				id="app-developer-docs"
				:name="t('settings', 'Developer documentation ↗')"
				:href="developerDocsUrl" />
		</template>
	</NcAppNavigation>
</template>

<script setup lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcAppNavigationSpacer from '@nextcloud/vue/components/NcAppNavigationSpacer'
import NcCounterBubble from '@nextcloud/vue/components/NcCounterBubble'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import { APPSTORE_CATEGORY_ICONS, APPSTORE_CATEGORY_NAMES } from '../constants.ts'
import { useAppsStore } from '../store/apps.ts'
import { useUpdatesStore } from '../store/updates.ts'

const appstoreEnabled = loadState<boolean>('settings', 'appstoreEnabled', true)
const developerDocsUrl = loadState<string>('settings', 'appstoreDeveloperDocs', '')

const store = useAppsStore()
const updateStore = useUpdatesStore()
const categories = computed(() => store.categories)
const categoriesLoading = computed(() => store.isLoadingCategories)

/**
 * Check if the current instance has a support subscription from the Nextcloud GmbH
 *
 * For customers of the Nextcloud GmbH the app level will be set to `300` for apps that are supported in their subscription
 */
const isSubscribed = computed(() => store.apps.find(({ level }) => level === 300) !== undefined)
</script>

<style scoped>
/* The categories-loading indicator */
.categories--loading {
	flex: 1;
	display: flex;
	align-items: center;
	justify-content: center;
}
</style>
