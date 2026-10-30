<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { AppAction } from '../actions/index.ts'
import type { IAppstoreApp, IAppstoreExApp } from '../apps.d.ts'

import { computed } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActionLink from '@nextcloud/vue/components/NcActionLink'
import NcActionRouter from '@nextcloud/vue/components/NcActionRouter'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'

const { actions, maxInlineActions = 1 } = defineProps<{
	app: IAppstoreApp | IAppstoreExApp
	actions: AppAction[]
	maxInlineActions?: number
	iconOnly?: boolean
}>()

const inlineActions = computed(() => {
	if (actions.length <= maxInlineActions) {
		return actions
	}
	return actions
		.filter((action) => action.inline !== false)
		.slice(0, maxInlineActions)
})

const menuActions = computed(() => actions
	.filter((action) => !inlineActions.value.includes(action)))
</script>

<template>
	<div :class="$style.appActions">
		<NcButton
			v-for="action in inlineActions"
			:key="action.id"
			:ariaLabel="iconOnly ? action.label(app) : undefined"
			:title="iconOnly ? action.label(app) : undefined"
			:variant="action.variant"
			:href="'href' in action ? action.href(app) : undefined"
			:to="'to' in action ? action.to(app) : undefined"
			:target="'href' in action ? '_blank' : undefined"
			@click="'callback' in action && action.callback(app)">
			<template #icon>
				<NcIconSvgWrapper :path="action.icon" />
			</template>
			<template v-if="!iconOnly" #default>
				{{ action.label(app) }}
			</template>
		</NcButton>
		<NcActions forceMenu>
			<template v-for="action in menuActions">
				<NcActionButton
					v-if="'callback' in action"
					:key="'callback-' + action.id"
					closeAfterClick
					:variant="action.variant"
					@click="action.callback(app)">
					<template #icon>
						<NcIconSvgWrapper :path="action.icon" />
					</template>
					{{ action.label(app) }}
				</NcActionButton>
				<NcActionLink
					v-else-if="'href' in action"
					:key="'link-' + action.id"
					closeAfterClick
					:variant="action.variant"
					:href="action.href(app)">
					<template #icon>
						<NcIconSvgWrapper :path="action.icon" />
					</template>
					{{ action.label(app) }}
				</NcActionLink>
				<NcActionRouter
					v-else
					:key="'route-' + action.id"
					closeAfterClick
					:variant="action.variant"
					:to="action.to(app)">
					<template #icon>
						<NcIconSvgWrapper :path="action.icon" />
					</template>
					{{ action.label(app) }}
				</NcActionRouter>
			</template>
		</NcActions>
	</div>
</template>

<style module>
.appActions {
	display: flex;
	flex-direction: row;
	gap: calc(2 * var(--default-grid-baseline));
}
</style>
