<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IAppstoreApp, IAppstoreExApp } from '../../apps.d.ts'

import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import AppDaemonBadge from '../AppDaemonBadge.vue'
import AppIcon from '../AppIcon.vue'
import AppLevelBadge from '../AppLevelBadge.vue'
import AppActions from '../AppActions.vue'
import { useActions } from '../../composables/useActions.ts'

const { app, isNarrow } = defineProps<{
	app: IAppstoreApp | IAppstoreExApp
	isNarrow?: boolean
}>()

const actions = useActions(() => app)
const route = useRoute()
const detailsRoute = computed(() => ({
	name: route.name!,
	params: {
		...route.params,
		id: app.id,
	},
}))
</script>

<template>
	<tr :class="$style.appTableRow">
		<td :class="$style.appTableRow__nameCell">
			<NcButton
				alignment="start"
				:title="t('appstore', 'Show details')"
				:to="detailsRoute"
				variant="tertiary-no-background"
				wide>
				<template #icon>
					<NcLoadingIcon v-if="app.loading" :size="24" />
					<AppIcon v-else :app :size="24" />
				</template>
				{{ app.name }}
				<span v-if="app.loading" class="hidden-visually">({{ t('appstore', 'is loading…') }})</span>
				<span class="hidden-visually">({{ t('appstore', 'Show details') }})</span>
			</NcButton>
		</td>
		<td>
			<span :class="$style.appTableRow__versionCell">{{ app.version }}</span>
		</td>
		<td v-if="!isNarrow">
			<div :class="$style.appTableRow__levelCell">
				<AppLevelBadge v-if="app.level" :level="app.level" />
				<AppDaemonBadge v-if="'daemon' in app && app.daemon" :daemon="app.daemon" />
			</div>
		</td>
		<td>
			<div :class="$style.appTableRow__actionsCell">
				<AppActions
					:class="$style.appTableRow__actionsCellActions"
					:app
					:actions
					:iconOnly="isNarrow" />
			</div>
		</td>
	</tr>
</template>

<style module>
.appTableRow {
	height: calc(var(--default-clickable-area) + var(--default-grid-baseline));
}

.appTableRow td {
	padding-block: var(--default-grid-baseline);
	vertical-align: middle;
}

.appTableRow__nameCell {
	/* Padding is needed to have proper focus-visible */
	padding-inline: var(--default-grid-baseline);
}

.appTableRow__levelCell {
	display: flex;
	align-items: center;
	gap: var(--default-grid-baseline)
}

.appTableRow__versionCell {
	color: var(--color-text-maxcontrast);
}

.appTableRow__actionsCell {
	display: flex;
	gap: var(--default-grid-baseline);
	justify-content: end;
}

.appTableRow__actionsCellActions {
	width: 100%;
	justify-content: end;
}
</style>
