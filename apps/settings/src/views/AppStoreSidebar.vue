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
	<!-- Selected app details -->
	<NcAppSidebar v-if="showSidebar"
		class="app-sidebar"
		:class="{ 'app-sidebar--with-screenshot': hasScreenshot }"
		:background="hasScreenshot ? app.screenshot : undefined"
		:compact="!hasScreenshot"
		:name="app.name"
		:title="app.name"
		:subname="licenseText"
		:subtitle="licenseText"
		@close="hideAppDetails">
		<!-- Fallback icon incase no app icon is available -->
		<template v-if="!hasScreenshot" #header>
			<NcIconSvgWrapper class="app-sidebar__fallback-icon"
				:svg="appIcon ?? ''"
				:size="64" />
		</template>

		<template #description>
			<!-- Featured/Supported badges -->
			<div class="app-sidebar__badges">
				<AppLevelBadge :level="app.level" />
				<AppScore v-if="hasRating" :score="rating" />
			</div>
		</template>

		<!-- Tab content -->
		<AppDescriptionTab :app="app" />
		<AppDetailsTab :app="app" />
		<AppReleasesTab :app="app" />
	</NcAppSidebar>
</template>

<script setup lang="ts">
import { translate as t } from '@nextcloud/l10n'
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router/composables'
import { useAppsStore } from '../store/apps-store'

import NcAppSidebar from '@nextcloud/vue/dist/Components/NcAppSidebar.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import AppScore from '../components/AppList/AppScore.vue'
import AppDescriptionTab from '../components/AppStoreSidebar/AppDescriptionTab.vue'
import AppDetailsTab from '../components/AppStoreSidebar/AppDetailsTab.vue'
import AppReleasesTab from '../components/AppStoreSidebar/AppReleasesTab.vue'
import AppLevelBadge from '../components/AppList/AppLevelBadge.vue'
import { useAppIcon } from '../composables/useAppIcon.ts'

const route = useRoute()
const router = useRouter()
const store = useAppsStore()

const appId = computed(() => route.params.id ?? '')
const app = computed(() => store.getAppById(appId.value)!)
const hasRating = computed(() => app.value.appstoreData?.ratingNumOverall > 5)
const rating = computed(() => app.value.appstoreData?.ratingNumRecent > 5
	? app.value.appstoreData.ratingRecent
	: (app.value.appstoreData?.ratingOverall ?? 0.5))
const showSidebar = computed(() => app.value)

const { appIcon } = useAppIcon(app)

/**
 * The second text line shown on the sidebar
 */
const licenseText = computed(() => app.value ? t('settings', 'Version {version}, {license}-licensed', { version: app.value.version, license: app.value.licence.toString().toUpperCase() }) : '')

/**
 * Hide the details sidebar by pushing a new route
 */
const hideAppDetails = () => router.push({
	name: 'apps-category',
	params: { category: route.params.category },
})

/**
 * Whether the app screenshot is loaded
 */
const screenshotLoaded = ref(false)
const hasScreenshot = computed(() => app.value?.screenshot && screenshotLoaded.value)
/**
 * Preload the app screenshot
 */
const loadScreenshot = () => {
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
