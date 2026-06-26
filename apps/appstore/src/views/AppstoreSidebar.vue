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
import AppActions from '../components/AppActions.vue'
import AppDeployDaemonTab from '../components/AppstoreSidebar/AppDeployDaemonTab.vue'
import AppDescriptionTab from '../components/AppstoreSidebar/AppDescriptionTab.vue'
import AppDetailsTab from '../components/AppstoreSidebar/AppDetailsTab.vue'
import AppReleasesTab from '../components/AppstoreSidebar/AppReleasesTab.vue'
import { useActions } from '../composables/useActions.ts'
import { useAppIcon } from '../composables/useAppIcon.ts'
import { useAppsStore } from '../store/apps.ts'
import { useUserSettingsStore } from '../store/userSettings.ts'

const route = useRoute()
const router = useRouter()
const store = useAppsStore()
const userSettings = useUserSettingsStore()

const appId = computed<string>(() => [route.params.id].flat()[0]!)
const app = computed(() => store.getAppById(appId.value) ?? null)
const { appIcon } = useAppIcon(app)

/**
 * The second text line shown on the sidebar
 */
const licenseText = computed(() => {
	if (!app.value) {
		return ''
	}

	if (app.value.license) {
		return t('appstore', 'Version {version}, {license}-licensed', { version: app.value.version, license: String(app.value.license).toUpperCase() })
	}
	return t('appstore', 'Version {version}', { version: app.value.version })
})

const activeTab = ref('details')
watch([app], () => {
	activeTab.value = 'details'
})

/**
 * Hide the details sidebar by pushing a new route
 */
function hideAppDetails() {
	router.replace({
		name: route.name!,
		params: {
			...route.params,
			id: undefined,
		},
		query: userSettings.getQuery(),
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

const actions = useActions(() => app.value)
</script>

<template>
	<!-- Selected app details -->
	<NcAppSidebar
		v-model:active="activeTab"
		:class="[$style.appstoreSidebar, { [$style.appstoreSidebar_withScreenshot]: hasScreenshot }]"
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
				:class="$style.appstoreSidebar__fallbackIcon"
				:svg="appIcon ?? ''"
				:size="64" />
		</template>

		<template v-if="app" #description>
			<AppActions
				:app
				:actions
				iconOnly
				:maxInlineActions="6" />
		</template>

		<!-- Tab content -->
		<NcEmptyContent v-if="!app" name="No such app" />
		<template v-else>
			<AppDescriptionTab :app />
			<AppReleasesTab :app />
			<AppDetailsTab :app />
			<AppDeployDaemonTab v-if="app.app_api" :app />
		</template>
	</NcAppSidebar>
</template>

<style module>
/* If a screenshot is available it should cover the whole figure */
.appstoreSidebar_withScreenshot {
	:global(.app-sidebar-header__figure) {
		background-size: cover;
	}
}

.appstoreSidebar__fallbackIcon {
	width: 100%;
	height: 100%;
}
</style>
