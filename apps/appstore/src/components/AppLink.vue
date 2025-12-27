<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
/**
 * This component either shows a native link to the installed app or external size
 * or a router link to the appstore page of the app if not installed
 */

import type { RouterLinkProps } from 'vue-router'
import type { INavigationEntry } from '../../../../core/src/types/navigation.d.ts'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import { ref, watchEffect } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

const props = defineProps<{
	href: string
}>()

const route = useRoute()
const knownRoutes = Object.fromEntries(loadState<INavigationEntry[]>('core', 'apps').map((app) => [app.app ?? app.id, app.href]))

const routerProps = ref<RouterLinkProps>()
const linkProps = ref<Record<string, string>>()

watchEffect(() => {
	const match = props.href.match(/^app:(\/\/)?([^/]+)(\/.+)?$/)
	routerProps.value = undefined
	linkProps.value = undefined

	// not an app url
	if (match === null) {
		linkProps.value = {
			href: props.href,
			target: '_blank',
			rel: 'noreferrer noopener',
		}
		return
	}

	const appId = match[2]!
	// Check if specific route was requested
	if (match[3]) {
		// we do no know anything about app internal path so we only allow generic app paths
		linkProps.value = {
			href: generateUrl(`/apps/${appId}${match[3]}`),
		}
		return
	}

	// If we know any route for that app we open it
	if (appId in knownRoutes) {
		linkProps.value = {
			href: knownRoutes[appId]!,
		}
		return
	}

	// Fallback to show the app store entry
	routerProps.value = {
		to: {
			name: 'apps-details',
			params: {
				category: route.params?.category ?? 'discover',
				id: appId,
			},
		},
	}
})
</script>

<template>
	<a v-if="linkProps" v-bind="linkProps">
		<slot />
	</a>
	<RouterLink v-else-if="routerProps" v-bind="routerProps">
		<slot />
	</RouterLink>
</template>
