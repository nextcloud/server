<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<nav class="app-menu" :aria-label="t('core', 'Applications')">
		<NcPopover
			ref="popover"
			:shown="opened"
			:triggers="[]"
			placement="bottom-start"
			:skidding="popoverSkidding"
			:setReturnFocus="returnFocusTarget"
			popoverBaseClass="app-menu__popover-base"
			popupRole="menu"
			@update:shown="opened = $event">
			<template #trigger>
				<NcButton
					class="app-menu__waffle"
					variant="tertiary-no-background"
					:aria-label="t('core', 'Open apps menu')"
					aria-haspopup="menu"
					:aria-expanded="opened ? 'true' : 'false'"
					@click="onTriggerClick('waffle')">
					<template #icon>
						<IconDotsGrid :size="20" />
					</template>
				</NcButton>
			</template>

			<div
				class="app-menu__popover"
				role="menu"
				:aria-label="t('core', 'Apps')">
				<div class="app-menu__grid" @keydown="onGridKeydown">
					<AppItem
						v-for="(item, i) in gridItems"
						:key="item.id"
						ref="items"
						:app="item"
						:newTab="openInNewTab"
						:outlined="item.id === 'more-apps'"
						:tabindex="i === focusedIndex ? 0 : -1" />
				</div>
			</div>
		</NcPopover>
		<button
			v-if="currentApp"
			class="app-menu__current-app"
			type="button"
			:aria-label="t('core', 'Open apps menu')"
			aria-haspopup="menu"
			:aria-expanded="opened ? 'true' : 'false'"
			@click="onTriggerClick('currentApp')">
			<img
				class="app-menu__current-app-icon"
				:src="currentApp.icon"
				alt=""
				aria-hidden="true">
			<span class="app-menu__current-app-name">
				{{ currentApp.name }}
			</span>
		</button>
	</nav>
</template>

<script lang="ts">
import type { INavigationEntry } from '../types/navigation.d.ts'

import { getCurrentUser } from '@nextcloud/auth'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { isRTL, n, t } from '@nextcloud/l10n'
import { generateFilePath, generateUrl } from '@nextcloud/router'
import { defineComponent, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import IconDotsGrid from 'vue-material-design-icons/DotsGrid.vue'
import AppItem from './AppItem.vue'
import logger from '../logger.js'

export default defineComponent({
	name: 'AppMenu',

	components: {
		AppItem,
		IconDotsGrid,
		NcButton,
		NcPopover,
	},

	setup() {
		const opened = ref(false)
		return {
			t,
			n,
			opened,
		}
	},

	data() {
		const appList = loadState<INavigationEntry[]>('core', 'apps', [])
		return {
			appList,
			isAdmin: getCurrentUser()?.isAdmin ?? false,
			// Roving tabindex: only this tile has tabindex=0; arrow keys move it.
			focusedIndex: 0,
			// NcPopover's focus-trap only knows the slot trigger (waffle).
			// The current-app button lives outside the slot, so we track the
			// source and restore focus manually via setReturnFocus.
			openedFrom: null as 'waffle' | 'currentApp' | null,
			// Synthetic admin-only tile linking to the app store; not a real nav entry.
			moreAppsEntry: {
				id: 'more-apps',
				active: false,
				order: Number.MAX_SAFE_INTEGER,
				href: generateUrl('/settings/apps'),
				icon: generateFilePath('settings', 'img', 'apps.svg'),
				type: 'link',
				name: t('core', 'More apps'),
				unread: 0,
			} as INavigationEntry,

			// `placement: bottom-start` swaps the anchor edge under RTL but the
			// skidding sign isn't auto-mirrored, so we flip it here. Snapshot
			// at init: Nextcloud's language doesn't change at runtime.
			popoverSkidding: isRTL() ? 82 : -82,
		}
	},

	computed: {
		currentApp(): INavigationEntry | undefined {
			return this.appList.find((app) => app.active)
		},

		openInNewTab(): boolean {
			return this.currentApp?.id !== 'dashboard'
		},

		// Stable-ordered list that focusedIndex indexes into; adds "More apps" for admins.
		gridItems(): INavigationEntry[] {
			return this.isAdmin ? [...this.appList, this.moreAppsEntry] : [...this.appList]
		},
	},

	watch: {
		// On open, land the roving stop on the active app rather than index 0.
		opened(isOpen: boolean) {
			if (isOpen) {
				this.focusedIndex = this.activeGridIndex()
			}
		},
	},

	mounted() {
		subscribe('nextcloud:app-menu.refresh', this.setApps)
		// Pre-seed so the correct tile has tabindex=0 before first open.
		this.focusedIndex = this.activeGridIndex()
		// Use $on instead of a template listener: the codebase lint rule forbids
		// hyphenated v-on names, and Vue 2 doesn't normalize kebab-case to
		// camelCase, so @afterHide on a NcPopover v8 event never fires.
		;(this.$refs.popover as { $on: (e: string, fn: () => void) => void }).$on('after-hide', this.onPopoverAfterHide)
	},

	beforeUnmount() {
		unsubscribe('nextcloud:app-menu.refresh', this.setApps)
		;(this.$refs.popover as { $off: (e: string, fn: () => void) => void } | undefined)?.$off('after-hide', this.onPopoverAfterHide)
	},

	methods: {
		// focus-trap calls this on deactivation. NcPopover defaults to the
		// slot trigger (waffle); we override so current-app opens return
		// there instead. Waffle is the fallback since current-app only
		// renders when an app is active.
		returnFocusTarget(): HTMLElement | null {
			return this.openedFrom === 'currentApp'
				? this.$el.querySelector('.app-menu__current-app')
				: this.$el.querySelector('.app-menu__waffle')
		},

		onPopoverAfterHide() {
			this.openedFrom = null
		},

		onTriggerClick(source: 'waffle' | 'currentApp') {
			this.openedFrom = source
			this.opened = !this.opened
		},

		setNavigationCounter(id: string, counter: number) {
			const app = this.appList.find(({ app }) => app === id)
			if (app) {
				app.unread = counter
			} else {
				logger.warn(`Could not find app "${id}" for setting navigation count`)
			}
		},

		setApps({ apps }: { apps: INavigationEntry[] }) {
			this.appList = apps
			if (this.focusedIndex >= this.gridItems.length) {
				this.focusedIndex = this.activeGridIndex()
			}
		},

		// Index of the active app within `gridItems`, or 0 if none is active.
		activeGridIndex(): number {
			const idx = this.gridItems.findIndex((app) => app.active)
			return idx === -1 ? 0 : idx
		},

		// Roving-tabindex keyboard contract for the launcher grid.
		// Arrow keys clamp at edges (no wrap), matching the WAI-ARIA grid
		// pattern. Tab is intentionally NOT handled so the browser's native
		// focus order moves out of the grid.
		async onGridKeydown(event: KeyboardEvent) {
			// Let modifier-bearing key combos fall through to the browser.
			// Shift is included so Shift+Enter opens the link in a new tab
			// via the browser's native modifier-aware <a> activation.
			if (event.ctrlKey || event.metaKey || event.altKey || event.shiftKey) {
				return
			}

			if (this.gridItems.length === 0) {
				return
			}

			const cols = 4
			const total = this.gridItems.length
			const i = this.focusedIndex
			let next = i

			switch (event.key) {
				case 'ArrowRight': {
					// Clamp at the row's right edge; never wrap to the next row.
					const atRowEnd = (i % cols) === cols - 1
					if (!atRowEnd && i + 1 < total) {
						next = i + 1
					}
					break
				}
				case 'ArrowLeft': {
					const atRowStart = (i % cols) === 0
					if (!atRowStart) {
						next = i - 1
					}
					break
				}
				case 'ArrowDown': {
					if (i + cols < total) {
						next = i + cols
					}
					break
				}
				case 'ArrowUp': {
					if (i - cols >= 0) {
						next = i - cols
					}
					break
				}
				case 'Home':
					next = 0
					break
				case 'End':
					next = total - 1
					break
				case 'Enter':
				case ' ': {
					// Space's default scrolls the nearest scrollable ancestor (the
					// popover); intercept and click programmatically. Enter gets
					// the same treatment so we can close the popover uniformly.
					const items = this.$refs.items as Array<{ $el: HTMLElement }> | undefined
					items?.[this.focusedIndex]?.$el?.click()
					this.opened = false
					event.preventDefault()
					event.stopPropagation()
					return
				}
				default:
					// Tab and every other key falls through untouched.
					return
			}

			// Stop bubbling to document-level handlers (e.g. the Files app's
			// keyboard shortcuts) that would also act on arrow keys.
			event.preventDefault()
			event.stopPropagation()
			if (next !== i) {
				this.focusedIndex = next
			}

			await this.$nextTick()
			const items = this.$refs.items as Array<{ $el: HTMLElement }> | undefined
			items?.[this.focusedIndex]?.$el?.focus()
		},
	},
})
</script>

<style scoped lang="scss">
.app-menu {
	display: flex;
	align-items: center;

	&__waffle {
		// NcButton's tertiary-no-background variant uses --color-main-text,
		// which is dark on light themes. The header sits on the theme primary
		// background, so override to use the matching plain-text color.
		--color-main-text: var(--color-background-plain-text);
		color: var(--color-background-plain-text);

		// Class merges onto NcButton's root <button>; style directly, no :deep().
		// !important: v8 NcButton's legacy bundle sets focus-visible
		// outline/box-shadow with !important, same as the current-app :active rule.
		&:hover:not(:disabled) {
			background-color: rgba(0, 0, 0, 0.1) !important;
		}

		&:active:not(:disabled) {
			background-color: rgba(0, 0, 0, 0.15) !important;
		}

		&:focus-visible {
			background-color: rgba(0, 0, 0, 0.1) !important;
			outline: none !important;
			box-shadow: inset 0 0 0 2px var(--color-background-plain-text) !important;
		}
	}

	&__current-app {
		display: flex;
		align-items: center;
		gap: var(--default-grid-baseline);
		height: var(--default-clickable-area);
		padding-inline: calc(var(--default-grid-baseline) * 2);
		background: transparent;
		border: none;
		border-radius: var(--border-radius-element);
		color: var(--color-background-plain-text);
		cursor: pointer;
		// Suppress the mobile-Safari grey tap rectangle that briefly flashes on press.
		-webkit-tap-highlight-color: transparent;

		// The header sits on the theme-primary background with white text, so
		// --color-background-hover (white-ish) collapses contrast. A translucent
		// black overlay reads on any header tint.
		&:hover {
			background: rgba(0, 0, 0, 0.1);
		}

		// core/css/inputs.scss:89 sets :active background to --color-main-background
		// (white on light themes), which makes the masked icon read as white-on-white.
		// !important: the global rule's :not() chain is hard to out-specify.
		&:active {
			background-color: rgba(0, 0, 0, 0.15) !important;
			color: var(--color-background-plain-text) !important;
		}

		&:focus-visible {
			background: rgba(0, 0, 0, 0.1);
			outline: none;
			box-shadow: inset 0 0 0 2px var(--color-background-plain-text);
		}
	}

	&__current-app-icon {
		width: calc(var(--default-grid-baseline) * 5);
		height: calc(var(--default-grid-baseline) * 5);
		// Theme-aware inversion + vertical alpha fade via --header-menu-icon-mask.
		filter: var(--background-image-invert-if-bright);
		mask: var(--header-menu-icon-mask);
	}

	&__current-app-name {
		font-size: var(--default-font-size);
		font-weight: 500;
		white-space: nowrap;
		letter-spacing: -0.5px;
	}

	&__popover {
		max-width: calc(100vw - var(--default-grid-baseline) * 4);
		background-color: var(--color-main-background);
	}

	&__grid {
		--app-item-col-width: 69px;
		--app-item-row-height: 64px;
		--app-menu-rows-visible: 6;
		padding: calc(var(--default-grid-baseline) * 3) calc(var(--default-grid-baseline) * 2);
		display: grid;
		grid-template-columns: repeat(4, var(--app-item-col-width));
		grid-auto-rows: minmax(var(--app-item-row-height), max-content);
		max-height: calc(var(--app-item-row-height) * var(--app-menu-rows-visible) + var(--default-grid-baseline) * 5);
		overflow-y: auto;

		// WebKit equivalents are in the unscoped block below: scoped CSS
		// data-attrs don't reach ::-webkit-scrollbar pseudo-elements in Chrome.
		scrollbar-width: thin;
		scrollbar-color: var(--color-scrollbar) transparent;
	}
}
</style>

<!-- Teleported content; scoped styles can't reach it. NcPopover v8 reads
     --border-radius-large; v9 reads --border-radius-element. Set both for forward-compat. -->
<style lang="scss">
.app-menu__popover-base {
	--border-radius-large: var(--border-radius-container-large);
	--border-radius-element: var(--border-radius-container-large);
}

// Gap between the trigger and the popover. Floating-ui positions
// .v-popper__popper, so margin on its inner .v-popper__wrapper isn't
// recomputed. Used instead of NcPopover's :distance prop, which isn't
// exposed in the released @nextcloud/vue yet.
.app-menu__popover-base .v-popper__wrapper {
	margin-block-start: -1px;
}

// Without this reset the override above cascades into AppItem and inflates
// its hover radius. Restores the system default from apps/theming/css/default.css.
.app-menu__popover-base .app-menu__popover {
	--border-radius-element: 8px;
}

// Outside the scoped block: ::-webkit-scrollbar pseudo-elements need unscoped
// CSS to bind in Chrome. !important: core/css/styles.scss forces a 12 px thumb.
.app-menu__popover-base .app-menu__grid {
	scrollbar-width: thin !important;
	scrollbar-color: var(--color-scrollbar) transparent !important;

	&::-webkit-scrollbar {
		width: 6px !important;
		height: 6px !important;
	}

	&::-webkit-scrollbar-track {
		background: transparent !important;
	}

	&::-webkit-scrollbar-thumb {
		background-color: var(--color-scrollbar) !important;
		border: none !important;
		border-radius: 3px !important;
		background-clip: padding-box !important;
	}
}
</style>
