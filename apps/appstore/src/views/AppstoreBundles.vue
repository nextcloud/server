<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IAppBundle, IAppstoreApp } from '../apps.d.ts'

import { mdiDownloadMultiple } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import AppTable from '../components/AppTable/AppTable.vue'
import { useAppsStore } from '../store/apps.ts'
import { canEnable } from '../utils/appStatus.ts'

const store = useAppsStore()

const appBundles = computed(() => store.bundles.map((bundle) => ({
	...bundle,
	apps: bundle.appIdentifiers
		.map((id) => store.apps.find((app) => app.id === id))
		.filter(Boolean) as IAppstoreApp[],
	isEnabling: false,
})))

/**
 * Check if a bundle can be enabled
 *
 * @param bundle - The bundle to check
 */
function canEnableBundle(bundle: IAppBundle): boolean {
	return bundle.appIdentifiers.every((id) => {
		const app = store.apps.find((app) => app.id === id)
		return app && (app.active || canEnable(app))
	})
}

/**
 * Check if a bundle is enabled
 *
 * @param bundle - The bundle to check
 */
function isBundleEnabled(bundle: IAppBundle): boolean {
	return bundle.appIdentifiers.every((id) => {
		const app = store.apps.find((app) => app.id === id)
		return app && app.active
	})
}

/**
 * Enable all apps in a bundle
 *
 * @param bundle - The bundle to enable all apps
 */
async function enableAll(bundle: typeof appBundles.value[number]) {
	bundle.isEnabling = true
	await store.enableBundle(bundle.id)
	bundle.isEnabling = false
}
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

	<template v-else>
		<section v-for="bundle of appBundles" :key="bundle.id">
			<div :class="$style.appstoreBundles__header">
				<h3>{{ bundle.name }}</h3>
				<NcButton
					v-if="!isBundleEnabled(bundle)"
					:disabled="!canEnableBundle(bundle)"
					variant="primary"
					@click="enableAll(bundle)">
					<template #icon>
						<NcIconSvgWrapper :path="mdiDownloadMultiple" />
					</template>
					{{ t('appstore', 'Download and enable all') }}
				</NcButton>
			</div>

			<AppTable
				:class="$style.appstoreBundles__appTable"
				:apps="bundle.apps" />
		</section>
	</template>
</template>

<style module>
.appstoreBundles__header {
	display: flex;
	flex-wrap: wrap;
	align-items: baseline;
	justify-content: space-between;
	gap: var(--default-clickable-area);
	padding-inline: var(--default-grid-baseline);
}

.appstoreBundles__appTable:last-of-type {
	margin-bottom: var(--body-container-margin);
}
</style>
