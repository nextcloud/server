<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<nav class="app-menu"
		:aria-label="t('core', 'Applications menu')">
		<ul class="app-menu__list">
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
				:name="app.name"
				class="app-menu__overflow-entry" />
		</NcActions>
	</nav>
</template>

<script lang="ts">
import type { INavigationEntry } from '../types/navigation'

import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { n, t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'

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
		return {
			t,
			n,
		}
	},

	data() {
		const appList = loadState<INavigationEntry[]>('core', 'apps', [])

		return {
			appList,
			appLimit: 0,
			observer: null as ResizeObserver | null,
		}
	},

	computed: {
		mainAppList() {
			return this.appList.slice(0, this.appLimit)
		},
		popoverAppList() {
			return this.appList.slice(this.appLimit)
		},
	},

	mounted() {
		this.observer = new ResizeObserver(this.resize)
		this.observer.observe(this.$el)
		this.resize()
		subscribe('nextcloud:app-menu.refresh', this.setApps)
	},

	beforeDestroy() {
		this.observer!.disconnect()
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

		resize() {
			const availableWidth = (this.$el as HTMLElement).offsetWidth
			let appCount = Math.floor(availableWidth / 50) - 1
			const popoverAppCount = this.appList.length - appCount
			if (popoverAppCount === 1) {
				appCount--
			}
			if (appCount < 1) {
				appCount = 0
			}
			this.appLimit = appCount
		},
	},
})
</script>

<style scoped lang="scss">
.app-menu {
	width: 100%;
	display: flex;
	flex-shrink: 1;
	flex-wrap: wrap;

	&__list {
		display: flex;
		flex-wrap: nowrap;
	}

	// Adjust the overflow NcActions styles as they are directly rendered on the background
	&__overflow :deep(.button-vue--vue-tertiary) {
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

	&__overflow-entry {
		:deep(.action-link__icon) {
			// Icons are bright so invert them if bright color theme == bright background is used
			filter: var(--background-invert-if-bright) !important;
		}
	}
}
</style>
