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
			:set-return-focus="returnFocusTarget"
			popover-base-class="app-menu__popover-base"
			popup-role="menu"
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
				<div ref="grid" class="app-menu__grid" @keydown="onGridKeydown">
					<AppMenuItem
						v-for="(item, i) in gridItems"
						:key="item.id"
						ref="items"
						:app="item"
						:outlined="item.id === 'more-apps' || item.id === 'app-store'"
						:new-tab="item.id === 'app-store'"
						:tabindex="i === focusedIndex ? 0 : -1" />
				</div>
			</div>
		</NcPopover>
		<NcButton
			v-if="currentApp"
			class="app-menu__current-app"
			variant="tertiary-no-background"
			:aria-label="currentAppLabel"
			aria-haspopup="menu"
			:aria-expanded="opened ? 'true' : 'false'"
			@click="onTriggerClick('currentApp')">
			<template #icon>
				<!-- Settings sub-sections share one generic cog. An inline MDI icon
					inherits the button's currentColor (--color-background-plain-text),
					so it stays legible on both bright and dark headers without a filter. -->
				<IconCog
					v-if="currentApp.type === 'settings'"
					class="app-menu__current-app-cog"
					:size="20" />
				<img
					v-else
					class="app-menu__current-app-icon"
					:src="currentApp.icon"
					alt=""
					aria-hidden="true">
			</template>
			<span class="app-menu__current-app-name">
				{{ displayName }}
			</span>
		</NcButton>
	</nav>
</template>

<script lang="ts">
import type { INavigationEntry } from '../../types/navigation.d.ts'

import { getCurrentUser } from '@nextcloud/auth'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { isRTL, n, t } from '@nextcloud/l10n'
import { generateUrl, imagePath } from '@nextcloud/router'
import { defineComponent, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import IconCog from 'vue-material-design-icons/Cog.vue'
import IconDotsGrid from 'vue-material-design-icons/DotsGrid.vue'
import AppMenuItem from './AppMenuItem.vue'
import logger from '../../logger.js'

// Settings IDs that represent actions, not navigable pages.
const SETTINGS_ACTION_IDS = new Set(['logout'])

export default defineComponent({
	name: 'AppMenu',

	components: {
		AppMenuItem,
		IconCog,
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
		// Record<id, entry>, not an array: PHP ships getAll('settings') without
		// array_values(). Matches AccountMenu.vue's usage.
		const settingsList = loadState<Record<string, INavigationEntry>>('core', 'settingsNavEntries', {})
		return {
			appList,
			settingsList,
			isAdmin: getCurrentUser()?.isAdmin ?? false,
			// Roving tabindex: only this tile has tabindex=0; arrow keys move it.
			focusedIndex: 0,
			// NcPopover's focus-trap only knows the slot trigger (waffle).
			// The current-app button lives outside the slot, so we track the
			// source and restore focus manually via setReturnFocus.
			openedFrom: null as 'waffle' | 'currentApp' | null,
			// Synthetic tile appended to the grid: admins jump to the local
			// app management page; everyone else lands on apps.nextcloud.com
			// (external, opens in a new tab via the per-tile newTab flag).
			moreAppsEntry: {
				id: 'more-apps',
				active: false,
				order: Number.MAX_SAFE_INTEGER,
				href: generateUrl('/settings/apps'),
				icon: imagePath('core', 'actions/add.svg'),
				type: 'link',
				name: t('core', 'More apps'),
				unread: 0,
			} as INavigationEntry,

			appStoreEntry: {
				id: 'app-store',
				active: false,
				order: Number.MAX_SAFE_INTEGER,
				href: 'https://apps.nextcloud.com/',
				icon: imagePath('core', 'actions/add.svg'),
				type: 'link',
				name: t('core', 'App store'),
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
			// Fall back to the active settings entry on admin pages where no
			// app is active.
			return this.appList.find((app) => app.active)
				?? Object.values(this.settingsList).find((entry) => entry.active && !SETTINGS_ACTION_IDS.has(entry.id))
		},

		// Trigger label. Settings sub-section names ("Personal info",
		// "Appearance and accessibility", ...) are too long and varied to
		// surface in the header; collapse them all to a single "Settings".
		displayName(): string {
			if (!this.currentApp) {
				return ''
			}
			return this.currentApp.type === 'settings'
				? t('core', 'Settings')
				: this.currentApp.name
		},

		// aria-label overrides the inner span text, so the displayed name
		// has to be duplicated here for screen readers.
		currentAppLabel(): string {
			return this.currentApp
				? t('core', 'Open apps menu, currently in {app}', { app: this.displayName })
				: t('core', 'Open apps menu')
		},

		// Stable-ordered list that focusedIndex indexes into. The trailing
		// utility tile is "More apps" (local app management) for admins and
		// "App store" (apps.nextcloud.com) for everyone else.
		gridItems(): INavigationEntry[] {
			const tail = this.isAdmin ? this.moreAppsEntry : this.appStoreEntry
			return [...this.appList, tail]
		},
	},

	watch: {
		// On open, land the roving stop on the active app rather than index 0
		// and measure the grid as soon as it mounts (before the open
		// transition finishes, so the cap is set without a flash).
		opened(isOpen: boolean) {
			if (isOpen) {
				this.focusedIndex = this.activeGridIndex()
				this.tryRecomputeGridMaxHeight(5)
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

		// Poll briefly for the grid ref (NcPopover renders the slot async)
		// then measure once. Bounded so a missing ref can never leak frames.
		tryRecomputeGridMaxHeight(retries: number) {
			if (!this.opened || retries <= 0) {
				return
			}
			if (!this.$refs.grid) {
				requestAnimationFrame(() => this.tryRecomputeGridMaxHeight(retries - 1))
				return
			}
			this.recomputeGridMaxHeight()
		},

		// Cap = sum of first 6 row heights + baseline × 6, so the peek of
		// row 7 stays constant when wraps grow rows.
		recomputeGridMaxHeight() {
			const grid = this.$refs.grid as HTMLElement | undefined
			if (!grid) {
				return
			}
			const VISIBLE_CELLS = 24 // 4 cols × 6 visible rows
			const cells = grid.children
			if (cells.length <= VISIBLE_CELLS) {
				grid.style.maxHeight = ''
				return
			}
			const firstHidden = cells[VISIBLE_CELLS] as HTMLElement | undefined
			const firstCell = cells[0] as HTMLElement | undefined
			if (!firstHidden || !firstCell) {
				return
			}
			const sumOfFirstRows = firstHidden.getBoundingClientRect().top
				- firstCell.getBoundingClientRect().top
			const baseline = parseFloat(getComputedStyle(grid).getPropertyValue('--default-grid-baseline')) || 4
			grid.style.maxHeight = `${sumOfFirstRows + baseline * 6}px`
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
		// NcButton's tertiary-no-background variant uses --color-main-text,
		// which is dark on light themes. The header sits on the theme primary
		// background, so override to use the matching plain-text color.
		--color-main-text: var(--color-background-plain-text);
		color: var(--color-background-plain-text);

		// !important: v8 NcButton's legacy bundle sets focus-visible
		// outline/box-shadow with !important. Same translucent-black hover/
		// active overlays as the waffle: --color-background-hover collapses
		// contrast against the theme-primary header tint.
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

		// Lets the inner label shrink to its max-width and ellipsize instead of
		// pushing the button wider than the inline-flex text slot.
		:deep(.button-vue__text) {
			min-width: 0;
		}

		@media only screen and (max-width: 1024px) {
			display: none !important;
		}
	}

	&__current-app-icon {
		width: calc(var(--default-grid-baseline) * 5);
		height: calc(var(--default-grid-baseline) * 5);
		// Theme-aware inversion + vertical alpha fade via --header-menu-icon-mask.
		filter: var(--background-image-invert-if-bright);
		mask: var(--header-menu-icon-mask);
	}

	&__current-app-cog {
		mask: var(--header-menu-icon-mask);
	}

	&__current-app-name {
		// inline-block: inline elements ignore max-width + overflow.
		display: inline-block;
		vertical-align: middle;
		font-size: var(--default-font-size);
		font-weight: 500;
		white-space: nowrap;
		letter-spacing: -0.5px;
		overflow: hidden;
		text-overflow: ellipsis;
		// Cap width so long localized labels ellipsize instead of pushing
		// the header icons off-screen (.header-start doesn't shrink).
		max-width: clamp(80px, 22vw, 320px);
	}

	&__popover {
		max-width: calc(100vw - var(--default-grid-baseline) * 4);
		background-color: var(--color-main-background);
	}

	&__grid {
		--app-item-col-width: 69px;
		--app-item-row-height: 64px;
		// border-box: the JS-set max-height (see recomputeGridMaxHeight)
		// needs to include padding for the peek math to hold.
		box-sizing: border-box;
		padding: calc(var(--default-grid-baseline) * 2);
		display: grid;
		grid-template-columns: repeat(4, var(--app-item-col-width));
		grid-auto-rows: minmax(var(--app-item-row-height), max-content);
		// max-height set inline by recomputeGridMaxHeight(); CSS just owns the scroll.
		overflow-y: auto;

		// Extra top padding on first-row tiles so the hover bg reads
		// concentric with the popover's rounded top corner. !important
		// because AppMenuItem's scoped rule has the same specificity.
		> :nth-child(-n+4) {
			padding-block-start: calc(var(--default-grid-baseline) * 2) !important;
		}

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

// Without this reset the override above cascades into AppMenuItem and inflates
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
