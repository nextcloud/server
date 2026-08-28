<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { INavigationEntry } from '../types/navigation.d.ts'

import { t } from '@nextcloud/l10n'
import { computed, ref, watch } from 'vue'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import IconDotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import AppActionIcon from './AppActionIcon.vue'
import AppMenuAction from './AppMenuAction.vue'
import AppMenuItem from './AppMenuItem.vue'

type ItemRef = { $el: HTMLElement }

const props = defineProps<{
	/** Navigation actions to render (INavigationManager::TYPE_ACTION entries). */
	actions: INavigationEntry[]
}>()

const emit = defineEmits<{
	/** Emitted when an action was activated, so the app menu can close. */
	(event: 'click'): void
}>()

/**
 * Number of cells in the actions row, matching the column count of the app
 * grid. Actions that do not fit move into the trailing "More actions" cell.
 */
const ACTIONS_PER_ROW = 4

// Rendered by AppMenuItem instead of AppMenuAction: it opens the submenu
// rather than triggering an action. The icon comes from the template slot, so
// no asset is needed here.
const overflowEntry: INavigationEntry = {
	id: 'more-actions',
	active: false,
	order: Number.MAX_SAFE_INTEGER,
	href: '',
	icon: '',
	type: 'action',
	name: t('core', 'More actions'),
	unread: 0,
}

// Roving tabindex within the row; `rowActions.length` is the overflow cell, so
// the whole row is a single Tab stop.
const focusedIndex = ref(0)
// Roving tabindex within the overflow submenu.
const overflowFocusedIndex = ref(0)
const overflowOpened = ref(false)

const rowItems = ref<ItemRef[]>([])
const overflowItems = ref<ItemRef[]>([])
const overflowTrigger = ref<ItemRef | null>(null)

const hasOverflow = computed(() => props.actions.length > ACTIONS_PER_ROW)

// The last cell is taken by the overflow trigger as soon as the actions do not
// fit into a single row.
const rowActions = computed(() => hasOverflow.value
	? props.actions.slice(0, ACTIONS_PER_ROW - 1)
	: props.actions)

const overflowActions = computed(() => hasOverflow.value
	? props.actions.slice(ACTIONS_PER_ROW - 1)
	: [])

/** Number of roving stops in the row, including the overflow cell. */
const rowLength = computed(() => rowActions.value.length + (hasOverflow.value ? 1 : 0))

// Every opening starts a fresh roving context on the first entry.
watch(overflowOpened, (isOpen) => {
	if (isOpen) {
		overflowFocusedIndex.value = 0
		trySubmenuFocus(5)
	}
})

watch(() => props.actions, () => {
	if (focusedIndex.value >= rowLength.value) {
		focusedIndex.value = 0
	}
})

/**
 * focus-trap deactivation target of the submenu: the cell that opened it,
 * inside the still open app menu popover.
 */
function returnFocusTarget(): HTMLElement | null {
	return overflowTrigger.value?.$el ?? null
}

/**
 * Focus the first submenu entry once it is rendered. NcPopover renders its
 * content asynchronously, so poll for the items - bounded, so a missing ref
 * can never leak frames.
 *
 * @param retries - Remaining animation frames to wait for the items
 */
function trySubmenuFocus(retries: number): void {
	if (!overflowOpened.value || retries <= 0) {
		return
	}
	if (overflowItems.value.length === 0) {
		requestAnimationFrame(() => trySubmenuFocus(retries - 1))
		return
	}
	overflowItems.value[0].$el.focus()
}

/**
 * Roving-tabindex contract of the actions row. Arrow keys clamp at both ends
 * (no wrap); Tab is not handled so the browser moves focus out of the row -
 * which is also how the row is reached from the app grid above.
 *
 * @param event - The keydown event
 */
function onRowKeydown(event: KeyboardEvent): void {
	// The submenu has its own handler; do not act on bubbled keys.
	if ((event.target as HTMLElement | null)?.closest('.app-menu-actions__submenu')) {
		return
	}
	const next = nextIndex(event, focusedIndex.value, rowLength.value, 'horizontal')
	if (next === null) {
		return
	}
	focusedIndex.value = next
	focusRowItem(next)
}

/**
 * Roving-tabindex contract of the overflow submenu.
 *
 * @param event - The keydown event
 */
function onSubmenuKeydown(event: KeyboardEvent): void {
	if (event.key === 'Escape') {
		// Close only the submenu, not the whole app menu. NcPopover restores
		// focus to the trigger via setReturnFocus.
		event.preventDefault()
		event.stopPropagation()
		overflowOpened.value = false
		return
	}
	const next = nextIndex(event, overflowFocusedIndex.value, overflowActions.value.length, 'vertical')
	if (next === null) {
		return
	}
	overflowFocusedIndex.value = next
	overflowItems.value[next]?.$el?.focus()
}

/**
 * Move the DOM focus to a cell of the row.
 *
 * @param index - Index of the roving stop
 */
function focusRowItem(index: number): void {
	if (hasOverflow.value && index === rowActions.value.length) {
		overflowTrigger.value?.$el?.focus()
		return
	}
	rowItems.value[index]?.$el?.focus()
}

/**
 * Shared arrow-key handling for both roving contexts.
 *
 * @param event - The keydown event
 * @param current - Index of the currently focused item
 * @param total - Number of items in this context
 * @param orientation - Whether the items are laid out in a row or a column
 * @return The next index, or null if the key was not handled and has to keep
 *         bubbling. Enter and Space activate the current item and return null.
 */
function nextIndex(event: KeyboardEvent, current: number, total: number, orientation: 'horizontal' | 'vertical'): number | null {
	// Let modifier-bearing key combos fall through to the browser. Shift is
	// included so Shift+Enter opens the link in a new tab via the browser's
	// native modifier-aware <a> activation.
	if (event.ctrlKey || event.metaKey || event.altKey || event.shiftKey) {
		return null
	}
	if (total === 0) {
		return null
	}

	const forward = orientation === 'horizontal' ? 'ArrowRight' : 'ArrowDown'
	const backward = orientation === 'horizontal' ? 'ArrowLeft' : 'ArrowUp'
	let next: number

	switch (event.key) {
		case forward:
			next = Math.min(current + 1, total - 1)
			break
		case backward:
			next = Math.max(current - 1, 0)
			break
		case 'Home':
			next = 0
			break
		case 'End':
			next = total - 1
			break
		case 'Enter':
		case ' ':
			// Space's default scrolls the popover, so intercept and click
			// programmatically. preventDefault also suppresses the native
			// activation of the <button> flavour, so this fires exactly once.
			event.preventDefault()
			event.stopPropagation()
			activate(current, orientation)
			return null
		default:
			// Tab, Escape and every other key falls through untouched.
			return null
	}

	// Stop bubbling to document-level handlers (e.g. the Files app's keyboard
	// shortcuts) that would also act on arrow keys.
	event.preventDefault()
	event.stopPropagation()
	return next
}

/**
 * Activate an item of either roving context by clicking it.
 *
 * @param index - Index of the item to activate
 * @param orientation - Which of the two contexts the index belongs to
 */
function activate(index: number, orientation: 'horizontal' | 'vertical'): void {
	if (orientation === 'vertical') {
		overflowItems.value[index]?.$el?.click()
		return
	}
	if (hasOverflow.value && index === rowActions.value.length) {
		// Toggles the submenu; the app menu itself stays open.
		overflowTrigger.value?.$el?.click()
		return
	}
	rowItems.value[index]?.$el?.click()
}

/** Collapse the submenu and let the app menu close as well. */
function onOverflowActionClick(): void {
	overflowOpened.value = false
	emit('click')
}
</script>

<template>
	<div
		class="app-menu-actions"
		role="group"
		:aria-label="t('core', 'Actions')"
		@keydown="onRowKeydown">
		<AppMenuAction
			v-for="(action, i) in rowActions"
			:key="action.id"
			ref="rowItems"
			:action="action"
			:tabindex="i === focusedIndex ? 0 : -1"
			@click="emit('click')" />

		<NcPopover
			v-if="hasOverflow"
			:shown="overflowOpened"
			:triggers="[]"
			placement="auto-end"
			popover-base-class="app-menu-actions__popover-base"
			popup-role="menu"
			:set-return-focus="returnFocusTarget"
			@update:shown="overflowOpened = $event">
			<template #trigger>
				<AppMenuItem
					ref="overflowTrigger"
					:app="overflowEntry"
					:tabindex="focusedIndex === rowActions.length ? 0 : -1"
					aria-haspopup="menu"
					:aria-expanded="overflowOpened ? 'true' : 'false'"
					@click="overflowOpened = !overflowOpened">
					<template #icon>
						<!-- Inline icon: the core dots asset ships a black fill,
							which AppActionIcon would paint unchanged. -->
						<AppActionIcon>
							<IconDotsHorizontal />
						</AppActionIcon>
					</template>
				</AppMenuItem>
			</template>

			<div
				class="app-menu-actions__submenu"
				role="menu"
				:aria-label="overflowEntry.name"
				@keydown="onSubmenuKeydown">
				<AppMenuAction
					v-for="(action, i) in overflowActions"
					:key="action.id"
					ref="overflowItems"
					compact
					:action="action"
					:tabindex="i === overflowFocusedIndex ? 0 : -1"
					@click="onOverflowActionClick" />
			</div>
		</NcPopover>
	</div>
</template>

<style scoped lang="scss">
.app-menu-actions {
	// The app grid is the scroll container, but this also keeps the row pinned
	// if the popover itself scrolls.
	position: sticky;
	inset-block-end: 0;
	z-index: 1;
	flex-shrink: 0;
	box-sizing: border-box;
	display: grid;
	// Same columns as .app-menu__grid so the items align with the apps above.
	grid-template-columns: repeat(4, var(--app-item-col-width, 69px));
	padding: calc(var(--default-grid-baseline) * 2);
	border-block-start: 1px solid var(--color-border);
	background-color: var(--color-main-background);

	// NcPopover's positioning element wraps the overflow trigger and has to
	// fill its grid cell to line the item up with the other columns.
	:deep(.v-popper) {
		display: flex;
	}

	:deep(.app-item) {
		width: 100%;
	}
}
</style>

<!-- Teleported content; scoped styles can't reach it. -->
<style lang="scss">
.app-menu-actions__popover-base .app-menu-actions__submenu {
	display: flex;
	flex-direction: column;
	gap: var(--default-grid-baseline);
	padding: var(--default-grid-baseline);
	min-width: 200px;
	max-width: min(320px, calc(100vw - var(--default-grid-baseline) * 4));
	background-color: var(--color-main-background);
}
</style>
