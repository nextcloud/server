<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<li
		:class="[$style.appListItem, {
			[$style.appListItem_selected]: isSelected,
		}]">
		<div class="app-image app-image-icon">
			<div v-if="!app?.app_api && !props.app.preview" class="icon-settings-dark" />
			<NcIconSvgWrapper
				v-else-if="app.app_api && !props.app.preview"
				:path="mdiCogOutline"
				:size="24"
				style="min-width: auto; min-height: auto; height: 100%;" />

			<svg
				v-else-if="app.preview && !app.app_api"
				width="32"
				height="32"
				viewBox="0 0 32 32">
				<image
					x="0"
					y="0"
					width="32"
					height="32"
					preserveAspectRatio="xMinYMin meet"
					:xlink:href="app.preview"
					class="app-icon" />
			</svg>
		</div>
		<div class="app-name">
			<router-link
				class="app-name--link"
				:to="{
					name: 'apps-details',
					params: {
						category: category,
						id: app.id,
					},
				}"
				:aria-label="t('settings', 'Show details for {appName} app', { appName: app.name })">
				{{ app.name }}
			</router-link>
		</div>
		<AppListVersion :app />
		<div class="app-level">
			<AppLevelBadge :level="app.level" />
		</div>
	</li>
</template>

<script setup lang="ts">
import type { IAppstoreApp } from '../../apps.d.ts'

import { mdiCogOutline } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { ref, watchEffect } from 'vue'
import { useRoute } from 'vue-router'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import AppLevelBadge from './AppLevelBadge.vue'
import AppListVersion from './AppListVersion.vue'

const props = defineProps<{
	app: IAppstoreApp
	category: string
}>()

const route = useRoute()
const isSelected = ref(false)
watchEffect(() => {
	isSelected.value = props.app.id === route.params.id
})

const screenshotLoaded = ref(false)
watchEffect(() => {
	if (props.app.screenshot) {
		const image = new Image()
		image.onload = () => {
			screenshotLoaded.value = true
		}
		image.src = props.app.screenshot
	}
})
</script>

<style module>
.appListItem {
	--app-item-padding: calc(var(--default-grid-baseline) * 2);
	--app-item-height: calc(var(--default-clickable-area) + var(--app-item-padding) * 2);

	> * {
		vertical-align: middle;
		border-bottom: 1px solid var(--color-border);
		padding: var(--app-item-padding);
		height: var(--app-item-height);
	}
}

.appListItem:hover {
	background-color: var(--color-background-dark);
}

.appListItem_selected {
	background-color: var(--color-background-dark);
}
</style>
