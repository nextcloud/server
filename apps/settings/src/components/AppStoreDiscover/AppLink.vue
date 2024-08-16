<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<a v-if="linkProps" v-bind="linkProps">
		<slot />
	</a>
	<RouterLink v-else-if="routerProps" v-bind="routerProps">
		<slot />
	</RouterLink>
</template>

<script lang="ts">
import type { RouterLinkProps } from 'vue-router/types/router.js'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import { defineComponent } from 'vue'
import { RouterLink } from 'vue-router'
import type { INavigationEntry } from '../../../../../core/src/types/navigation'

const apps = loadState<INavigationEntry[]>('core', 'apps')
const knownRoutes = Object.fromEntries(apps.map((app) => [app.app ?? app.id, app.href]))

/**
 * This component either shows a native link to the installed app or external size - or a router link to the appstore page of the app if not installed
 */
export default defineComponent({
	name: 'AppLink',

	components: { RouterLink },

	props: {
		href: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
			routerProps: undefined as RouterLinkProps|undefined,
			linkProps: undefined as Record<string, string>|undefined,
		}
	},

	watch: {
		href: {
			immediate: true,
			handler() {
				const match = this.href.match(/^app:\/\/([^/]+)(\/.+)?$/)
				this.routerProps = undefined
				this.linkProps = undefined

				// not an app url
				if (match === null) {
					this.linkProps = {
						href: this.href,
						target: '_blank',
						rel: 'noreferrer noopener',
					}
					return
				}

				const appId = match[1]
				// Check if specific route was requested
				if (match[2]) {
					// we do no know anything about app internal path so we only allow generic app paths
					this.linkProps = {
						href: generateUrl(`/apps/${appId}${match[2]}`),
					}
					return
				}

				// If we know any route for that app we open it
				if (appId in knownRoutes) {
					this.linkProps = {
						href: knownRoutes[appId],
					}
					return
				}

				// Fallback to show the app store entry
				this.routerProps = {
					to: {
						name: 'apps-details',
						params: {
							category: this.$route.params?.category ?? 'discover',
							id: appId,
						},
					},
				}
			},
		},
	},
})
</script>
