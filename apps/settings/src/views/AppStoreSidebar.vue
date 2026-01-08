<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<!-- Selected app details -->
	<NcAppSidebar
		v-if="showSidebar"
		class="app-sidebar"
		:class="{ 'app-sidebar--with-screenshot': hasScreenshot }"
		:active.sync="activeTab"
		:background="hasScreenshot ? app.screenshot : undefined"
		:compact="!hasScreenshot"
		:name="app.name"
		:title="app.name"
		:subname="licenseText"
		:subtitle="licenseText"
		@close="hideAppDetails">
		<!-- Fallback icon incase no app icon is available -->
		<template v-if="!hasScreenshot" #header>
			<NcIconSvgWrapper
				class="app-sidebar__fallback-icon"
				:svg="appIcon ?? ''"
				:size="64" />
		</template>

		<template #description>
			<!-- Featured/Supported badges -->
			<div class="app-sidebar__badges">
				<AppLevelBadge :level="app.level" />
				<AppDaemonBadge v-if="app.app_api && app.daemon" :daemon="app.daemon" />
				<AppScore v-if="hasRating" :score="rating" />
			</div>
		</template>

		<!-- Tab content -->
		<AppDescriptionTab :app="app" />
		<AppDetailsTab :app="app" :key="app.id" />
		<AppReleasesTab :app="app" />
		<AppDeployDaemonTab :app="app" />
	</NcAppSidebar>
</template>

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router/composables'
import NcAppSidebar from '@nextcloud/vue/components/NcAppSidebar'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import AppDaemonBadge from '../components/AppList/AppDaemonBadge.vue'
import AppLevelBadge from '../components/AppList/AppLevelBadge.vue'
import AppScore from '../components/AppList/AppScore.vue'
import AppDeployDaemonTab from '../components/AppStoreSidebar/AppDeployDaemonTab.vue'
import AppDescriptionTab from '../components/AppStoreSidebar/AppDescriptionTab.vue'
import AppDetailsTab from '../components/AppStoreSidebar/AppDetailsTab.vue'
import AppReleasesTab from '../components/AppStoreSidebar/AppReleasesTab.vue'
import { useAppIcon } from '../composables/useAppIcon.ts'
import { useAppApiStore } from '../store/app-api-store.ts'
import { useAppsStore } from '../store/apps-store.ts'
import { useStore } from '../store/index.js'

const route = useRoute()
const router = useRouter()
const store = useAppsStore()
const appApiStore = useAppApiStore()
const legacyStore = useStore()

const appId = computed(() => route.params.id ?? '')
const app = computed(() => {
	if (legacyStore.getters.isAppApiEnabled) {
		const exApp = appApiStore.getAllApps
			.find((app) => app.id === appId.value) ?? null
		if (exApp) {
			return exApp
		}
	}
	return store.getAppById(appId.value)!
})
const hasRating = computed(() => app.value.appstoreData?.ratingNumOverall > 5)
const rating = computed(() => app.value.appstoreData?.ratingNumRecent > 5
	? app.value.appstoreData.ratingRecent
	: (app.value.appstoreData?.ratingOverall ?? 0.5))
const showSidebar = computed(() => app.value !== null)

const { appIcon } = useAppIcon(app)

/**
 * The second text line shown on the sidebar
 */
const licenseText = computed(() => {
	if (!app.value) {
		return ''
	}
	if (app.value.license !== '') {
		return t('settings', 'Version {version}, {license}-licensed', { version: app.value.version, license: app.value.licence.toString().toUpperCase() })
	}
	return t('settings', 'Version {version}', { version: app.value.version })
})

const activeTab = ref('details')
watch([app], () => {
	activeTab.value = 'details'
})

/**
 * Hide the details sidebar by pushing a new route
 */
function hideAppDetails() {
	router.push({
		name: 'apps-category',
		params: { category: route.params.category },
	})
}

/**
 * Whether the app screenshot is loaded
 */
const screenshotLoaded = ref(false)
const hasScreenshot = computed(() => app.value?.screenshot && screenshotLoaded.value)
/**
 * Preload the app screenshot
 */
function loadScreenshot() {
	if (app.value?.releases && app.value?.screenshot) {
		const image = new Image()
		image.onload = () => {
			screenshotLoaded.value = true
		}
		image.src = app.value.screenshot
	}
}
// Watch app and set screenshot loaded when
watch([app], loadScreenshot)
onMounted(loadScreenshot)
</script>

<style scoped lang="scss">
.app-sidebar {
	// If a screenshot is available it should cover the whole figure
	&--with-screenshot {
		:deep(.app-sidebar-header__figure) {
			background-size: cover;
		}
	}

	&__fallback-icon {
		// both 100% to center the icon
		width: 100%;
		height: 100%;
	}

	&__badges {
		display: flex;
		flex-direction: row;
		gap: 12px;
	}

	&__version {
		color: var(--color-text-maxcontrast);
	}
}
</style>
