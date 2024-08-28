<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<nav ref="appMenu"
		class="app-menu"
		:aria-label="t('core', 'Applications menu')">
		<ul :aria-label="t('core', 'Apps')"
			class="app-menu__list">
			<AppMenuEntry v-for="app in mainAppList"
				:key="app.id"
				:app="app" />
		</ul>
		<NcActions class="app-menu__overflow" :aria-label="t('core', 'More apps')">
			<NcActionLink v-for="app in popoverAppList"
				:key="app.id"
				:aria-current="app.active ? 'page' : false"
				:href="app.href"
				:icon="app.icon"
				class="app-menu__overflow-entry">
				{{ app.name }}
			</NcActionLink>
		</NcActions>
	</nav>
</template>

<script lang="ts">
import type { INavigationEntry } from '../types/navigation'

import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { n, t } from '@nextcloud/l10n'
import { useElementSize } from '@vueuse/core'
import { defineComponent, ref } from 'vue'

import AppMenuEntry from './AppMenuEntry.vue'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionLink from '@nextcloud/vue/dist/Components/NcActionLink.js'
import logger from '../logger'

export default defineComponent({
	name: 'AppMenu',

	components: {
		AppMenuEntry,
		NcActions,
		NcActionLink,
	},

	setup() {
		const appMenu = ref()
		const { width: appMenuWidth } = useElementSize(appMenu)
		return {
			t,
			n,
			appMenu,
			appMenuWidth,
		}
	},

	data() {
		const appList = loadState<INavigationEntry[]>('core', 'apps', [])
		return {
			appList,
		}
	},

	computed: {
		appLimit() {
			const maxApps = Math.floor(this.appMenuWidth / 50)
			if (maxApps < this.appList.length) {
				// Ensure there is space for the overflow menu
				return Math.max(maxApps - 1, 0)
			}
			return maxApps
		},

		mainAppList() {
			return this.appList.slice(0, this.appLimit)
		},

		popoverAppList() {
			return this.appList.slice(this.appLimit)
		},
	},

	mounted() {
		subscribe('nextcloud:app-menu.refresh', this.setApps)
	},

	beforeDestroy() {
		unsubscribe('nextcloud:app-menu.refresh', this.setApps)
	},

	methods: {
		setNavigationCounter(id: string, counter: number) {
			const app = this.appList.find(({ app }) => app === id)
			if (app) {
				this.$set(app, 'unread', counter)
			} else {
				logger.warn(`Could not find app "${id}" for setting navigation count`)
			}
		},

		setApps({ apps }: { apps: INavigationEntry[]}) {
			this.appList = apps
		},
	},
})
</script>

<style scoped lang="scss">
.app-menu {
	// The size the currently focussed entry will grow to show the full name
	--app-menu-entry-growth: calc(var(--default-grid-baseline) * 4);
	display: flex;
	flex: 1 1;
	width: 0;

	&__list {
		display: flex;
		flex-wrap: nowrap;
		margin-inline: calc(var(--app-menu-entry-growth) / 2);
	}

	&__overflow {
		margin-block: auto;

		// Adjust the overflow NcActions styles as they are directly rendered on the background
		:deep(.button-vue--vue-tertiary) {
			opacity: .7;
			margin: 3px;
			filter: var(--background-image-invert-if-bright);

			/* Remove all background and align text color if not expanded */
			&:not([aria-expanded="true"]) {
				color: var(--color-background-plain-text);

				&:hover {
					opacity: 1;
					background-color: transparent !important;
				}
			}

			&:focus-visible {
				opacity: 1;
				outline: none !important;
			}
		}
	}

	&__overflow-entry {
		:deep(.action-link__icon) {
			// Icons are bright so invert them if bright color theme == bright background is used
			filter: var(--background-invert-if-bright) !important;
		}
	}
}
</style>
