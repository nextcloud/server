<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiFilterVariant, mdiSizeL, mdiSizeM, mdiSizeS, mdiViewGrid } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActionButtonGroup from '@nextcloud/vue/components/NcActionButtonGroup'
import NcActionCheckbox from '@nextcloud/vue/components/NcActionCheckbox'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import { useUserSettingsStore } from '../store/userSettings.ts'

const route = useRoute()
const router = useRouter()
const userSettingsStore = useUserSettingsStore()

watch(() => userSettingsStore.isGridView, (enabled: boolean) => {
	router.replace({
		...route,
		query: {
			...route.query,
			grid: enabled ? null : undefined,
		},
	})
})

watch(() => userSettingsStore.defaultGridSize, (newSize) => {
	if (userSettingsStore.isGridView) {
		router.replace({
			...route,
			query: {
				...route.query,
				grid: newSize || null,
			},
		})
	}
})

watch(() => userSettingsStore.showIncompatible, (showIncompatible) => {
	if (showIncompatible) {
		router.replace({
			...route,
			query: {
				...route.query,
				compatible: undefined,
			},
		})
	} else {
		router.replace({
			...route,
			query: {
				...route.query,
				compatible: null,
			},
		})
	}
})
</script>

<template>
	<div :class="$style.appToolbar">
		<NcActions :class="$style.appToolbar__filterButton" :aria-label="t('appstore', 'Filter view')" forceMenu>
			<template #icon>
				<NcIconSvgWrapper :path="mdiFilterVariant" />
			</template>
			<NcActionButtonGroup v-if="userSettingsStore.isGridView" :name="t('appstore', 'Grid size')">
				<NcActionButton
					:aria-label="t('appstore', 'Small grid size')"
					:modelValue="userSettingsStore.defaultGridSize === ''"
					type="radio"
					value=""
					@update:modelValue="userSettingsStore.defaultGridSize = ''">
					<template #icon>
						<NcIconSvgWrapper :path="mdiSizeS" />
					</template>
				</NcActionButton>
				<NcActionButton
					:aria-label="t('appstore', 'Medium grid size')"
					:modelValue="userSettingsStore.defaultGridSize === 'm'"
					type="radio"
					value="m"
					@update:modelValue="userSettingsStore.defaultGridSize = 'm'">
					<template #icon>
						<NcIconSvgWrapper :path="mdiSizeM" />
					</template>
				</NcActionButton>
				<NcActionButton
					:aria-label="t('appstore', 'Large grid size')"
					:modelValue="userSettingsStore.defaultGridSize === 'l'"
					type="radio"
					value="l"
					@update:modelValue="userSettingsStore.defaultGridSize = 'l'">
					<template #icon>
						<NcIconSvgWrapper :path="mdiSizeL" />
					</template>
				</NcActionButton>
			</NcActionButtonGroup>

			<NcActionCheckbox v-model="userSettingsStore.showIncompatible">
				{{ t('appstore', 'Show incompatible') }}
			</NcActionCheckbox>
		</NcActions>

		<NcButton
			v-model:pressed="userSettingsStore.isGridView"
			:aria-label="t('appstore', 'Grid view')"
			variant="tertiary">
			<template #icon>
				<NcIconSvgWrapper :path="mdiViewGrid" />
			</template>
		</NcButton>
	</div>
</template>

<style module>
.appToolbar {
	display: flex;
	flex-direction: row;
	gap: calc(2 * var(--default-grid-baseline));
	position: absolute;
	inset-block-start: var(--app-navigation-padding);
	inset-inline-end: var(--app-sidebar-padding);

	z-index: 999;

	button:not([aria-pressed="true"]):not(:hover) {
		background-color: var(--color-main-background) !important;
	}
}
</style>
