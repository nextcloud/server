<!--
  - @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
  -
  - @author Julius Härtl <jus@bitgrid.net>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<nav class="app-menu">
		<ul class="app-menu-main">
			<li v-for="app in mainAppList"
				:key="app.id"
				:data-app-id="app.id"
				class="app-menu-entry"
				:class="{ 'app-menu-entry__active': app.active }">
				<a :href="app.href"
					:class="{ 'has-unread': app.unread > 0 }"
					:aria-label="appLabel(app)"
					:title="app.name"
					:aria-current="app.active ? 'page' : false"
					:target="app.target ? '_blank' : undefined"
					:rel="app.target ? 'noopener noreferrer' : undefined">
					<img :src="app.icon" alt="">
					<div class="app-menu-entry--label">
						{{ app.name }}
						<span v-if="app.unread > 0" class="hidden-visually unread-counter">{{ app.unread }}</span>
					</div>
				</a>
			</li>
		</ul>
		<NcActions class="app-menu-more" :aria-label="t('core', 'More apps')">
			<NcActionLink v-for="app in popoverAppList"
				:key="app.id"
				:aria-label="appLabel(app)"
				:aria-current="app.active ? 'page' : false"
				:href="app.href"
				class="app-menu-popover-entry">
				<template #icon>
					<div class="app-icon" :class="{ 'has-unread': app.unread > 0 }">
						<img :src="app.icon" alt="">
					</div>
				</template>
				{{ app.name }}
				<span v-if="app.unread > 0" class="hidden-visually unread-counter">{{ app.unread }}</span>
			</NcActionLink>
		</NcActions>
	</nav>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionLink from '@nextcloud/vue/dist/Components/NcActionLink.js'

export default {
	name: 'AppMenu',
	components: {
		NcActions, NcActionLink,
	},
	data() {
		return {
			apps: loadState('core', 'apps', {}),
			appLimit: 0,
			observer: null,
		}
	},
	computed: {
		appList() {
			return Object.values(this.apps)
		},
		mainAppList() {
			return this.appList.slice(0, this.appLimit)
		},
		popoverAppList() {
			return this.appList.slice(this.appLimit)
		},
		appLabel() {
			return (app) => app.name
				+ (app.active ? ' (' + t('core', 'Currently open') + ')' : '')
				+ (app.unread > 0 ? ' (' + n('core', '{count} notification', '{count} notifications', app.unread, { count: app.unread }) + ')' : '')
		},
	},
	mounted() {
		this.observer = new ResizeObserver(this.resize)
		this.observer.observe(this.$el)
		this.resize()
		subscribe('nextcloud:app-menu.refresh', this.setApps)
	},
	beforeDestroy() {
		this.observer.disconnect()
		unsubscribe('nextcloud:app-menu.refresh', this.setApps)
	},
	methods: {
		setNavigationCounter(id, counter) {
			this.$set(this.apps[id], 'unread', counter)
		},
		setApps({ apps }) {
			this.apps = apps
		},
		resize() {
			const availableWidth = this.$el.offsetWidth
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
}
</script>

<style lang="scss" scoped>
$header-icon-size: 20px;

.app-menu {
	width: 100%;
	display: flex;
	flex-shrink: 1;
	flex-wrap: wrap;
}
.app-menu-main {
	display: flex;
	flex-wrap: nowrap;

	.app-menu-entry {
		width: 50px;
		height: 50px;
		position: relative;
		display: flex;

		&.app-menu-entry__active {
			opacity: 1;

			&::before {
				content: " ";
				position: absolute;
				pointer-events: none;
				border-bottom-color: var(--color-main-background);
				transform: translateX(-50%);
				width: 12px;
				height: 5px;
				border-radius: 3px;
				background-color: var(--color-primary-element-text);
				left: 50%;
				bottom: 6px;
				display: block;
				transition: all 0.1s ease-in-out;
				opacity: 1;
			}

			.app-menu-entry--label {
				font-weight: bold;
			}
		}

		a {
			width: calc(100% - 4px);
			height: calc(100% - 4px);
			margin: 2px;
			color: var(--color-primary-element-text);
			position: relative;
		}

		img {
			transition: margin 0.1s ease-in-out;
			width: $header-icon-size;
			height: $header-icon-size;
			padding: calc((100% - $header-icon-size) / 2);
			box-sizing: content-box;
			filter: var(--background-image-invert-if-bright);
		}

		.app-menu-entry--label {
			opacity: 0;
			position: absolute;
			font-size: 12px;
			color: var(--color-primary-element-text);
			text-align: center;
			left: 50%;
			top: 45%;
			display: block;
			min-width: 100%;
			transform: translateX(-50%);
			transition: all 0.1s ease-in-out;
			width: 100%;
			text-overflow: ellipsis;
			overflow: hidden;
			letter-spacing: -0.5px;
		}

		&:hover,
		&:focus-within {
			opacity: 1;
			.app-menu-entry--label {
				opacity: 1;
				font-weight: bolder;
				bottom: 0;
				width: 100%;
				text-overflow: ellipsis;
				overflow: hidden;
			}
		}

	}

	// Show labels
	&:hover,
	&:focus-within,
	.app-menu-entry:hover,
	.app-menu-entry:focus {
		opacity: 1;

		img {
			margin-top: -8px;
		}

		.app-menu-entry--label {
			opacity: 1;
			bottom: 0;
		}

		&::before, .app-menu-entry::before {
			opacity: 0;
		}
	}
}

::v-deep .app-menu-more .button-vue--vue-tertiary {
	opacity: .7;
	margin: 3px;
	filter: var(--background-image-invert-if-bright);

	/* Remove all background and align text color if not expanded */
	&:not([aria-expanded="true"]) {
		color: var(--color-primary-element-text);

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

.app-menu-popover-entry {
	.app-icon {
		position: relative;
		height: 44px;
		/* Icons are bright so invert them if bright color theme == bright background is used */
		filter: var(--background-invert-if-bright);

		&.has-unread::after {
			background-color: var(--color-main-text);
		}

		img {
			width: $header-icon-size;
			height: $header-icon-size;
			padding: calc((50px - $header-icon-size) / 2);
		}
	}
}

.has-unread::after {
	content: "";
	width: 8px;
	height: 8px;
	background-color: var(--color-primary-element-text);
	border-radius: 50%;
	position: absolute;
	display: block;
	top: 10px;
	right: 10px;
}

.unread-counter {
	display: none;
}
</style>
