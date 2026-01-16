<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import NcAppSidebar from '@nextcloud/vue/components/NcAppSidebar'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import AppDaemonBadge from '../components/AppDaemonBadge.vue'
import AppLevelBadge from '../components/AppLevelBadge.vue'
import AppScore from '../components/AppScore.vue'
// import AppDeployDaemonTab from '../components/AppStoreSidebar/AppDeployDaemonTab.vue'
// import AppDescriptionTab from '../components/AppStoreSidebar/AppDescriptionTab.vue'
// import AppDetailsTab from '../components/AppStoreSidebar/AppDetailsTab.vue'
// import AppReleasesTab from '../components/AppStoreSidebar/AppReleasesTab.vue'
import { useAppIcon } from '../composables/useAppIcon.ts'
import { useAppsStore } from '../store/apps.ts'

const route = useRoute()
const router = useRouter()
const store = useAppsStore()

const appId = computed<string>(() => [route.params.id].flat()[0]!)
const app = computed(() => store.getAppById(appId.value) ?? null)

const hasRating = computed(() => app.value && app.value.appstoreData?.ratingNumOverall && app.value.appstoreData?.ratingNumOverall > 5)
const rating = computed(() => hasRating.value
	? app.value!.appstoreData!.ratingRecent
	: (app.value!.appstoreData?.ratingOverall ?? 0.5))

const { appIcon } = useAppIcon(app)

/**
 * The second text line shown on the sidebar
 */
const licenseText = computed(() => {
	if (!app.value) {
		return ''
	}
	if (app.value.licence !== '') {
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

<template>
	<!-- Selected app details -->
	<NcAppSidebar
		v-model:active="activeTab"
		class="app-sidebar"
		:class="{ 'app-sidebar--with-screenshot': hasScreenshot }"
		:background="hasScreenshot ? app!.screenshot : undefined"
		:compact="!hasScreenshot"
		:name="app?.name ?? appId"
		:title="app?.name ?? appId"
		:subname="licenseText"
		:subtitle="licenseText"
		@close="hideAppDetails">
		<!-- Fallback icon in case no app icon is available -->
		<template v-if="!hasScreenshot" #header>
			<NcIconSvgWrapper
				class="app-sidebar__fallback-icon"
				:svg="appIcon ?? ''"
				:size="64" />
		</template>

		<template v-if="app" #description>
			<!-- Featured/Supported badges -->
			<div class="app-sidebar__badges">
				<AppLevelBadge :level="app.level" />
				<AppDaemonBadge v-if="app.app_api && app.daemon" :daemon="app.daemon" />
				<AppScore v-if="hasRating" :score="rating" />
			</div>
		</template>

		<!-- Tab content -->
		<NcEmptyContent v-if="!app" name="No such app" />
		<template v-else>
			<!-- <AppDescriptionTab :app="app" />
			<AppDetailsTab :app="app" />
			<AppReleasesTab :app="app" />
			<AppDeployDaemonTab :app="app" /> -->
		</template>
	</NcAppSidebar>
</template>

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
