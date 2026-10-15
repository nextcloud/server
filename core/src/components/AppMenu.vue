<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { INavigationEntry } from '../types/navigation.d.ts'

import { mdiCog, mdiDotsGrid } from '@mdi/js'
import { getCurrentUser } from '@nextcloud/auth'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { isRTL, t } from '@nextcloud/l10n'
import { generateUrl, imagePath } from '@nextcloud/router'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useTemplateRef, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import AppMenuActions from './AppMenuActions.vue'
import AppMenuItem from './AppMenuItem.vue'
import { logger } from '../utils/logger.ts'

// Settings IDs that represent actions, not navigable pages.
const SETTINGS_ACTION_IDS = new Set(['logout'])

const SETTINGS_SECTION_IDS = new Set(['settings_personal', 'settings_administration', 'accessibility_settings'])

// The profile entry is named "View profile" for the account menu, which is an
// action rather than a page name. The header shows where you are instead.
const PROFILE_ID = 'profile'

// Entry of the app management page, the target of the "More apps" tile.
const APP_MANAGEMENT_ID = 'appstore'

// Pause before hover opens the menu: the trigger sits in the corner, which
// cursors cross on the way elsewhere.
const HOVER_OPEN_DELAY = 300
const HOVER_CLOSE_DELAY = 180
// Ignore a trigger click this long after a hover-open, so it does not close again.
const HOVER_CLICK_GRACE = 500

// Synthetic tile appended to the grid: admins jump to the local app management
// page; everyone else lands on apps.nextcloud.com (external, opens in a new tab
// via the per-tile newTab flag).
const moreAppsEntry: INavigationEntry = {
	id: 'more-apps',
	active: false,
	order: Number.MAX_SAFE_INTEGER,
	href: generateUrl('/settings/apps'),
	icon: imagePath('core', 'actions/add.svg'),
	type: 'link',
	name: t('core', 'More apps'),
	unread: 0,
}

const appStoreEntry: INavigationEntry = {
	id: 'app-store',
	active: false,
	order: Number.MAX_SAFE_INTEGER,
	href: 'https://apps.nextcloud.com/',
	icon: imagePath('core', 'actions/add.svg'),
	type: 'link',
	name: t('core', 'App store'),
	unread: 0,
}

// `placement: bottom-start` swaps the anchor edge under RTL but the skidding
// sign isn't auto-mirrored, so it is flipped here. Nextcloud's language
// doesn't change at runtime.
const popoverSkidding = isRTL() ? 82 : -82 // the width of the product logo + main container margin

const navigationActions = loadState<INavigationEntry[]>('core', 'navigationActions', [])
const settingsList = loadState<Record<string, INavigationEntry>>('core', 'settingsNavEntries', {})
// Fail closed: a missing state must not leak the link.
const appStoreLinkShown = loadState<boolean>('core', 'appStoreLinkShown', false)
const isAdmin = getCurrentUser()?.isAdmin ?? false

const root = useTemplateRef('root')
const grid = useTemplateRef('grid')

const appList = ref(loadState<INavigationEntry[]>('core', 'apps', []))
const opened = ref(false)
// Roving tabindex: only this tile has tabindex=0; arrow keys move it.
const focusedIndex = ref(0)
// Which button opened the menu, so focus returns to it.
const openedFrom = ref<'waffle' | 'currentApp' | null>(null)
// Opened by hover: run without the focus trap, so focus is not stolen.
const hoverOpen = ref(false)
// Grace window where a trigger click does not close the menu.
const suppressCloseClick = ref(false)

// Hover intent timers (see HOVER_OPEN_DELAY / HOVER_CLOSE_DELAY).
let openTimer: ReturnType<typeof setTimeout> | null = null
let closeTimer: ReturnType<typeof setTimeout> | null = null
let suppressClickTimer: ReturnType<typeof setTimeout> | null = null
let clicks = 0
let clickTimestamp = 0

/**
 * The tile at a position of the grid, whose children are the tiles in order.
 *
 * @param index - Position of the tile
 */
function gridItem(index: number): HTMLElement | undefined {
	return grid.value?.children[index] as HTMLElement | undefined
}

// Only pass noFocusTrap for hover opens; clicks and keyboard keep the trap.
const popoverAttrs = computed(() => hoverOpen.value
	? { autoHide: autoHideCheck, noFocusTrap: true }
	: { autoHide: autoHideCheck })

// Fall back to the active settings entry on admin pages where no app is active.
const currentApp = computed<INavigationEntry | undefined>(() => appList.value.find((app) => app.active)
	?? Object.values(settingsList).find((entry) => entry.active && !SETTINGS_ACTION_IDS.has(entry.id)))

const isSettingsSection = computed(() => currentApp.value !== undefined && SETTINGS_SECTION_IDS.has(currentApp.value.id))

const displayName = computed(() => {
	if (!currentApp.value) {
		return ''
	}
	if (isSettingsSection.value) {
		return t('core', 'Settings')
	}
	return currentApp.value.id === PROFILE_ID
		? t('core', 'Profile')
		: currentApp.value.name
})

// The profile entry ships no icon of its own, so use the generic one.
const currentAppIcon = computed(() => currentApp.value?.id === PROFILE_ID
	? imagePath('core', 'actions/user.svg')
	: currentApp.value?.icon ?? '')

// Masked like AppIcon.vue, so dark icons stay legible on the header.
// Escaped so a crafted path cannot break out of the url() token.
const currentAppIconStyle = computed(() => ({
	'--app-icon-url': `url("${currentAppIcon.value.replace(/["\\]/g, '\\$&')}")`,
}))

// aria-label overrides the inner span text, so the displayed name has to be
// duplicated here for screen readers.
const currentAppLabel = computed(() => currentApp.value
	? t('core', 'Open apps menu, currently in {app}', { app: displayName.value })
	: t('core', 'Open apps menu'))

// Stable-ordered list that focusedIndex indexes into. The trailing utility tile
// is "More apps" (local app management) for admins and "App store"
// (apps.nextcloud.com) for everyone else when appstore_link_shown allows it.
const gridItems = computed(() => {
	const tail: INavigationEntry[] = []
	if (isAdmin) {
		tail.push({ ...moreAppsEntry, active: currentApp.value?.id === APP_MANAGEMENT_ID })
	} else if (appStoreLinkShown) {
		tail.push(appStoreEntry)
	}
	return [...appList.value, ...tail]
})

// On open, land the roving stop on the active app rather than index 0 and
// measure the grid as soon as it mounts (before the open transition finishes,
// so the cap is set without a flash).
watch(opened, (isOpen) => {
	if (isOpen) {
		focusedIndex.value = activeGridIndex()
		tryRecomputeGridMaxHeight(5)
	} else {
		// Closed again: end any pending click-grace window.
		clearSuppressClickTimer()
		suppressCloseClick.value = false
	}
})

onMounted(() => {
	subscribe('nextcloud:app-menu.refresh', setApps)
	// Pre-seed so the correct tile has tabindex=0 before first open.
	focusedIndex.value = activeGridIndex()
})

onBeforeUnmount(() => {
	clearOpenTimer()
	clearCloseTimer()
	clearSuppressClickTimer()
	unsubscribe('nextcloud:app-menu.refresh', setApps)
})

defineExpose({ setNavigationCounter })

/**
 * focus-trap calls this on deactivation. NcPopover defaults to the slot trigger
 * (waffle); this override makes current-app opens return there instead. Waffle
 * is the fallback since current-app only renders when an app is active.
 */
function returnFocusTarget(): HTMLElement | null {
	return openedFrom.value === 'currentApp'
		? root.value?.querySelector('.app-menu__current-app') ?? null
		: root.value?.querySelector('.app-menu__waffle') ?? null
}

/**
 * Blocks the popover's outside-click close during the grace window.
 */
function autoHideCheck(): boolean {
	return !suppressCloseClick.value
}

/**
 * Reset the state of the last opening once the popover is hidden.
 */
function onPopoverAfterHide() {
	openedFrom.value = null
	hoverOpen.value = false
}

/**
 * @param source - The trigger button that was clicked
 */
function onTriggerClick(source: 'waffle' | 'currentApp') {
	// Drop pending hover timers so they don't undo this toggle.
	clearOpenTimer()
	clearCloseTimer()
	// Ignore the click that would close what hover just opened.
	if (opened.value && suppressCloseClick.value) {
		return
	}
	// Explicit click: keep the focus trap.
	hoverOpen.value = false
	openedFrom.value = source
	opened.value = !opened.value

	const now = Date.now()
	if (clickTimestamp !== 0 && now - clickTimestamp > 10000) {
		clicks = 0
	}
	clickTimestamp = now
	clicks++
	if (clicks > 20 && !matchMedia('(prefers-reduced-motion: reduce)').matches && !document.body.hasAttribute('data-theme-reduced-motion')) {
		const styleTag = document.createElement('style')
		styleTag.innerHTML = '@keyframes bodyAnimation { 0% {transform: rotate3d(0,0,0,0);} 25% {transform: rotate3d(' + (Math.floor(Math.random() * 3) - 1) + ',' + (Math.floor(Math.random() * 3) - 1) + ',' + (Math.floor(Math.random() * 3) - 1) + ',' + Math.floor(Math.random() * 360) + 'deg);} 50% {transform: rotate3d(' + (Math.floor(Math.random() * 3) - 1) + ',' + (Math.floor(Math.random() * 3) - 1) + ',' + (Math.floor(Math.random() * 3) - 1) + ',' + Math.floor(Math.random() * 360) + 'deg);} 75% {transform: rotate3d(0,0,0,0);} 100% {transform: rotate3d(0,0,0,0);}}'
		document.body.appendChild(styleTag)
		document.body.setAttribute('style', 'animation-name:bodyAnimation;animation-duration:10s;animation-iteration-count:infinite;animation-direction:alternate')
	} else if (clicks > 15) {
		document.body.setAttribute('style', 'filter:sepia(' + Math.floor(Math.random() * 100) + '%) invert(' + Math.floor(Math.random() * 2) * 100 + '%) hue-rotate(' + (Math.floor(Math.random() * 360)) + 'deg) blur(' + Math.floor(clicks / 30) + 'px);transition:filter 1s !important')
	}
}

/**
 * Hover-to-open, mouse only so keyboard focus never triggers it.
 *
 * @param source - The trigger the cursor entered
 */
function onTriggerPointerEnter(source: 'waffle' | 'currentApp' = 'waffle') {
	if (!canHoverOpen()) {
		return
	}
	clearCloseTimer()
	if (opened.value) {
		return
	}
	clearOpenTimer()
	openTimer = setTimeout(() => {
		openTimer = null
		openedFrom.value = source
		hoverOpen.value = true
		opened.value = true
		// Start the grace window in which a habitual click won't close it.
		suppressCloseClick.value = true
		clearSuppressClickTimer()
		suppressClickTimer = setTimeout(() => {
			suppressClickTimer = null
			suppressCloseClick.value = false
		}, HOVER_CLICK_GRACE)
	}, HOVER_OPEN_DELAY)
}

/**
 * Only real pointers open on hover: on touch a tap fires mouseenter too, and an
 * unfocused window should not pop the menu when the cursor rests there.
 */
function canHoverOpen(): boolean {
	const pointer = window.matchMedia?.('(hover: hover) and (pointer: fine)')
	return (pointer?.matches ?? true) && document.hasFocus()
}

/**
 * Cursor left: cancel a pending open, schedule the close.
 */
function onPointerLeave() {
	clearOpenTimer()
	scheduleClose()
	clicks = 0
}

/**
 * Cursor moved into the open popover: keep it open.
 */
function onPopoverPointerEnter() {
	clearCloseTimer()
}

/**
 * Close the menu after the grace delay of a leaving cursor.
 */
function scheduleClose() {
	clearCloseTimer()
	closeTimer = setTimeout(() => {
		closeTimer = null
		opened.value = false
	}, HOVER_CLOSE_DELAY)
}

/**
 * Cancel a pending hover-open.
 */
function clearOpenTimer() {
	if (openTimer !== null) {
		clearTimeout(openTimer)
		openTimer = null
	}
}

/**
 * Cancel a pending hover-close.
 */
function clearCloseTimer() {
	if (closeTimer !== null) {
		clearTimeout(closeTimer)
		closeTimer = null
	}
}

/**
 * End the click-grace window early.
 */
function clearSuppressClickTimer() {
	if (suppressClickTimer !== null) {
		clearTimeout(suppressClickTimer)
		suppressClickTimer = null
	}
}

/**
 * Show the number of unread notifications of an app on its tile.
 *
 * @param id - The app id
 * @param counter - The number of unread notifications
 */
function setNavigationCounter(id: string, counter: number) {
	const app = appList.value.find(({ app }) => app === id)
	if (app) {
		app.unread = counter
	} else {
		logger.warn(`Could not find app "${id}" for setting navigation count`)
	}
}

/**
 * Replace the apps of the menu, e.g. after an app was enabled.
 *
 * @param payload - The event payload
 * @param payload.apps - The new list of apps
 */
function setApps({ apps }: { apps: INavigationEntry[] }) {
	appList.value = apps
	if (focusedIndex.value >= gridItems.value.length) {
		focusedIndex.value = activeGridIndex()
	}
}

/**
 * Poll briefly for the grid (NcPopover renders the slot async) then measure
 * once. Bounded so a missing grid can never leak frames.
 *
 * @param retries - Remaining animation frames to wait for the grid
 */
function tryRecomputeGridMaxHeight(retries: number) {
	if (!opened.value || retries <= 0) {
		return
	}
	if (!grid.value) {
		requestAnimationFrame(() => tryRecomputeGridMaxHeight(retries - 1))
		return
	}
	recomputeGridMaxHeight()
}

/**
 * Cap = sum of first 6 row heights + baseline × 6, so the peek of row 7 stays
 * constant when wraps grow rows.
 */
function recomputeGridMaxHeight() {
	if (!grid.value) {
		return
	}
	const VISIBLE_CELLS = 24 // 4 cols × 6 visible rows
	const cells = grid.value.children
	if (cells.length <= VISIBLE_CELLS) {
		grid.value.style.maxHeight = ''
		return
	}
	const firstHidden = cells[VISIBLE_CELLS] as HTMLElement | undefined
	const firstCell = cells[0] as HTMLElement | undefined
	if (!firstHidden || !firstCell) {
		return
	}
	const sumOfFirstRows = firstHidden.getBoundingClientRect().top
		- firstCell.getBoundingClientRect().top
	const baseline = parseFloat(getComputedStyle(grid.value).getPropertyValue('--default-grid-baseline')) || 4
	grid.value.style.maxHeight = `${sumOfFirstRows + baseline * 6}px`
}

/**
 * Index of the active app within `gridItems`, or 0 if none is active.
 */
function activeGridIndex(): number {
	const index = gridItems.value.findIndex((app) => app.active)
	return index === -1 ? 0 : index
}

/**
 * Roving-tabindex keyboard contract for the launcher grid. Arrow keys clamp at
 * edges (no wrap), matching the WAI-ARIA grid pattern. Tab is intentionally not
 * handled so the browser's native focus order moves out of the grid.
 *
 * @param event - The keydown event
 */
async function onGridKeydown(event: KeyboardEvent) {
	// Let modifier-bearing key combos fall through to the browser. Shift is
	// included so Shift+Enter opens the link in a new tab via the browser's
	// native modifier-aware <a> activation.
	if (event.ctrlKey || event.metaKey || event.altKey || event.shiftKey) {
		return
	}

	if (gridItems.value.length === 0) {
		return
	}

	const cols = 4
	const total = gridItems.value.length
	const i = focusedIndex.value
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
			// popover); intercept and click programmatically. Enter gets the
			// same treatment so the popover closes uniformly.
			gridItem(focusedIndex.value)?.click()
			opened.value = false
			event.preventDefault()
			event.stopPropagation()
			return
		}
		default:
			// Tab and every other key falls through untouched.
			return
	}

	// Stop bubbling to document-level handlers (e.g. the Files app's keyboard
	// shortcuts) that would also act on arrow keys.
	event.preventDefault()
	event.stopPropagation()
	if (next !== i) {
		focusedIndex.value = next
	}

	await nextTick()
	gridItem(focusedIndex.value)?.focus()
}
</script>

<template>
	<nav ref="root" class="app-menu" :aria-label="t('core', 'Applications')">
		<NcPopover
			v-model:shown="opened"
			:triggers="[]"
			v-bind="popoverAttrs"
			placement="bottom-start"
			:skidding="popoverSkidding"
			:setReturnFocus="returnFocusTarget"
			popoverBaseClass="app-menu__popover-base"
			popupRole="menu"
			@afterHide="onPopoverAfterHide">
			<!-- Both buttons are the trigger, so they share one highlight and either
				one opens the menu. On narrow screens only the waffle shows. -->
			<template #trigger>
				<div
					class="app-menu__trigger"
					:class="{ 'app-menu__trigger--open': opened }"
					@mouseenter="onTriggerPointerEnter()"
					@mouseleave="onPointerLeave">
					<NcButton
						class="app-menu__waffle"
						variant="tertiary-no-background"
						:aria-label="t('core', 'Open apps menu')"
						aria-haspopup="menu"
						:aria-expanded="opened ? 'true' : 'false'"
						@click="onTriggerClick('waffle')">
						<template #icon>
							<NcIconSvgWrapper :path="mdiDotsGrid" :size="20" />
						</template>
					</NcButton>
					<NcButton
						v-if="currentApp"
						class="app-menu__current-app"
						variant="tertiary-no-background"
						:aria-label="currentAppLabel"
						aria-haspopup="menu"
						:aria-expanded="opened ? 'true' : 'false'"
						@click="onTriggerClick('currentApp')">
						<template #icon>
							<!-- Settings sections and entries without an icon show a generic cog. -->
							<NcIconSvgWrapper
								v-if="isSettingsSection || !currentAppIcon"
								class="app-menu__current-app-cog"
								:path="mdiCog"
								:size="20" />
							<!-- Outer element carries the header fade, inner one the icon shape. -->
							<span v-else class="app-menu__current-app-icon">
								<span
									class="app-menu__current-app-glyph"
									:style="currentAppIconStyle" />
							</span>
						</template>
						<span class="app-menu__current-app-name">
							{{ displayName }}
						</span>
					</NcButton>
				</div>
			</template>

			<div
				class="app-menu__popover"
				role="menu"
				:aria-label="t('core', 'Apps')"
				@mouseenter="onPopoverPointerEnter"
				@mouseleave="onPointerLeave">
				<div ref="grid" class="app-menu__grid" @keydown="onGridKeydown">
					<AppMenuItem
						v-for="(item, i) in gridItems"
						:key="item.id"
						:app="item"
						:outlined="item.id === 'more-apps' || item.id === 'app-store'"
						:newTab="item.id === 'app-store'"
						:tabindex="i === focusedIndex ? 0 : -1" />
				</div>
				<AppMenuActions
					v-if="navigationActions.length > 0"
					:actions="navigationActions"
					@click="opened = false" />
			</div>
		</NcPopover>
	</nav>
</template>

<style scoped lang="scss">
.app-menu {
	display: flex;
	align-items: center;

	// Wrapper for both triggers: full header height for the click area, with one
	// shared highlight spanning the waffle and the current app.
	&__trigger {
		position: relative;
		display: flex;
		align-items: center;
		height: var(--header-height);
		// Own stacking context so the highlight can sit behind the buttons.
		isolation: isolate;

		// The shared highlight, inset from top/bottom so it stays smaller than the
		// header. inset-inline: 0 spans both triggers; collapses to the waffle when
		// the current-app button is hidden.
		&::before {
			content: '';
			position: absolute;
			inset-block: calc((var(--header-height) - var(--default-clickable-area)) / 2);
			inset-inline: 0;
			border-radius: var(--border-radius-element);
			// Behind the buttons (whose backgrounds stay transparent).
			z-index: -1;
			pointer-events: none;
		}

		// Translucent black: --color-background-hover has too little contrast on the
		// header tint. Keep the highlight while the menu is open.
		&:hover::before,
		&--open::before {
			background-color: rgba(0, 0, 0, 0.1);
		}

		&:active::before {
			background-color: rgba(0, 0, 0, 0.15);
		}
	}

	&__waffle,
	&__current-app {
		// Full header height for the click area; the highlight is on __trigger, so
		// the buttons stay transparent. !important beats NcButton's scoped rules.
		height: var(--header-height) !important;
		// Anchor the per-button focus ring below to the button, not __trigger.
		position: relative;

		// NcButton's tertiary-no-background variant uses --color-main-text,
		// which is dark on light themes. The header sits on the theme primary
		// background, so override to use the matching plain-text color.
		--color-main-text: var(--color-background-plain-text);
		color: var(--color-background-plain-text);

		// Hide NcButton's own hover/active fill so only the __trigger highlight
		// shows. The extra .button-vue makes this win over NcButton's rule.
		&.button-vue:hover:not(:disabled),
		&.button-vue:active:not(:disabled) {
			background-color: transparent !important;
		}

		// Per-button keyboard focus ring, matched to the highlight pill. Hide
		// NcButton's own ring (outline + halo); the extra .button-vue makes our
		// override win over it.
		&.button-vue:focus-visible {
			outline: none !important;
			box-shadow: none !important;
		}

		&.button-vue:focus-visible::before {
			content: '';
			position: absolute;
			inset-block: calc((var(--header-height) - var(--default-clickable-area)) / 2);
			inset-inline: 0;
			border-radius: var(--border-radius-element);
			box-shadow: inset 0 0 0 2px var(--color-background-plain-text);
			pointer-events: none;
		}
	}

	&__current-app {
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
		display: flex;
		width: calc(var(--default-grid-baseline) * 5);
		height: calc(var(--default-grid-baseline) * 5);
		// Vertical alpha fade, like the cog and the other header icons.
		mask: var(--header-menu-icon-mask);
	}

	&__current-app-glyph {
		width: 100%;
		height: 100%;
		// Masked rather than shown: app icons ship a hardcoded fill, so the
		// color has to come from the background. Matches AppIcon.vue.
		background-color: var(--color-background-plain-text);
		mask: var(--app-icon-url) center / contain no-repeat;
	}

	// Masked backgrounds are not force-adjusted the way <img> is.
	@media (forced-colors: active) {
		&__current-app-glyph {
			background-color: CanvasText;
		}
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
		// Shared by the app grid and the actions row below it, so both use the
		// same column raster.
		--app-item-col-width: 69px;
		--app-item-row-height: calc(15 * var(--default-grid-baseline) + 1.5 * var(--default-font-size)); // 12x for icon + 3x for padding + text
		// Column flex so the actions row keeps its height and the grid owns
		// the remaining space (and the scrolling).
		display: flex;
		flex-direction: column;
		max-height: calc(100vh - var(--header-height) - var(--default-grid-baseline));
		max-width: calc(100vw - var(--default-grid-baseline) * 4);
		background-color: var(--color-main-background);
	}

	&__grid {
		// border-box: the JS-set max-height (see recomputeGridMaxHeight)
		// needs to include padding for the peek math to hold.
		box-sizing: border-box;
		padding: calc(var(--default-grid-baseline) * 2);
		display: grid;
		grid-template-columns: repeat(4, var(--app-item-col-width));
		grid-auto-rows: minmax(var(--app-item-row-height), max-content);
		// Allows the grid to shrink below its content height inside the
		// column flex parent, so the scroll cap always applies.
		min-height: 0;
		// max-height set inline by recomputeGridMaxHeight(); CSS just owns the scroll.
		overflow-y: auto;
		overflow-x: hidden;

		// Extra top padding on first-row tiles so the hover bg reads
		// concentric with the popover's rounded top corner. !important
		// because AppItem's scoped rule has the same specificity.
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

<!-- Teleported content; scoped styles can't reach it. -->
<style lang="scss">
.app-menu__popover-base {
	--border-radius-element: var(--border-radius-container-large);
}

// No arrow: the menu reads as a panel below the header, not a tooltip.
.app-menu__popover-base .v-popper__arrow-container {
	display: none;
}

// Cancel NcPopover's 4px distance so the menu starts right below the header.
.app-menu__popover-base .v-popper__wrapper {
	margin-block-start: -4px;
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
