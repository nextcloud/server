<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IAppstoreApp, IAppstoreExApp } from '../apps.ts'

import { mdiInformationOutline } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { useRoute } from 'vue-router'
import { computed } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActionRouter from '@nextcloud/vue/components/NcActionRouter'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import AppIcon from './AppIcon.vue'
import AppLevelBadge from './AppLevelBadge.vue'
import AppDaemonBadge from './AppDaemonBadge.vue'
import { useActions } from '../composables/useActions.ts'

const { app, isNarrow } = defineProps<{
	app: IAppstoreApp | IAppstoreExApp,
	isNarrow?: boolean
}>()

const actions = useActions(() => app)
const inlineActions = computed(() => !isNarrow || actions.value.length === 1
	? actions.value.slice(0, 1)
	: [])
const menuActions = computed(() => actions.value.slice(inlineActions.value.length))

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
		<td>
			<NcButton
				alignment="start"
				:title="t('appstore', 'Show details')"
				:to="detailsRoute"
				variant="tertiary-no-background"
				wide>
				<template #icon>
					<AppIcon :app :size="24" />
				</template>
				{{ app.name }}
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
				<NcButton v-for="action in inlineActions"
					:key="action.id"
					:variant="action.variant"
					@click="action.callback(app)">
					{{ action.label(app) }}
				</NcButton>
				<NcActions force-menu>
					<NcActionButton
						v-for="action in menuActions"
						:key="action.id"
						closeAfterClick
						@click="action.callback(app)">
						<template #icon>
							<NcIconSvgWrapper :path="action.icon" />
						</template>
						{{ action.label(app) }}
					</NcActionButton>
					<NcActionRouter closeAfterClick :to="detailsRoute">
						<template #icon>
							<NcIconSvgWrapper :path="mdiInformationOutline" />
						</template>
						{{ t('appstore', 'Show details') }}
					</NcActionRouter>
				</NcActions>
			</div>
		</td>
	</tr>
</template>

<style module>
.appTableRow {
	height: calc(var(--default-clickable-area) + var(--default-grid-baseline));
}

.appTableRow td {
	padding-block: calc(var(--default-grid-baseline) / 2);
	vertical-align: middle;
}

.appTableRow__nameCell {
	display: flex;
	align-items: center;
	gap: var(--default-grid-baseline)
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
</style>
